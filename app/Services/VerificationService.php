<?php

namespace App\Services;

use App\Enums\AssignmentType;
use App\Enums\ExecutorLevel;
use App\Enums\Permission;
use App\Enums\ReviewDecision;
use App\Enums\ReviewStatus;
use App\Enums\VerificationStatus;
use App\Models\Evidence;
use App\Models\EvidenceReview;
use App\Models\EvidenceVersion;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Multi-stage verification of evidence versions.
 *
 * - Each upload (new version) gets one review row per applicable active stage, in sequence.
 * - Only the first stage is open; the next opens when the previous one is accepted.
 * - "Revision required" or "rejected" closes the remaining stages for that version.
 * - A new version supersedes every open review of older versions and restarts from stage 1.
 * - Stage name/sequence/level are copied into the review, so config changes never rewrite history.
 */
class VerificationService
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
        private readonly AssignmentService $assignments,
    ) {}

    /**
     * Must run inside the transaction that created the version.
     */
    public function startFor(Evidence $evidence, EvidenceVersion $version): void
    {
        $evidence->reviews()
            ->whereIn('status', [ReviewStatus::Waiting->value, ...ReviewStatus::openValues()])
            ->update(['status' => ReviewStatus::Superseded->value]);

        $rows = VerificationStage::query()
            ->where('is_active', true)
            ->orderBy('sequence')
            ->get()
            ->filter(fn (VerificationStage $stage) => $stage->appliesTo($evidence))
            ->map(fn (VerificationStage $stage) => [
                'stage' => $stage,
                'organization' => $this->executorOrganization($stage->executor_level, $evidence->organization),
            ])
            ->filter(fn (array $row) => $row['organization'] !== null)
            ->values();

        if ($rows->isEmpty()) {
            // Internal verification must always work, even if no stage is configured or applicable.
            $rows = collect([['stage' => null, 'organization' => $evidence->organization]]);
        }

        $first = null;

        foreach ($rows as $index => $row) {
            /** @var VerificationStage|null $stage */
            $stage = $row['stage'];

            $review = EvidenceReview::create([
                'evidence_id' => $evidence->id,
                'evidence_version_id' => $version->id,
                'verification_stage_id' => $stage?->id,
                'stage_name' => $stage->name ?? 'Verifikasi internal',
                'stage_sequence' => $stage->sequence ?? 1,
                'executor_level' => $stage->executor_level ?? ExecutorLevel::Owner,
                'executor_organization_id' => $row['organization']->id,
                'status' => $index === 0 ? ReviewStatus::Pending : ReviewStatus::Waiting,
            ]);

            $first ??= $review;
        }

        DB::afterCommit(fn () => $first && $this->notifyVerifiers($first));
    }

    public function executorOrganization(ExecutorLevel $level, Organization $owner): ?Organization
    {
        $type = $level->ancestorType();

        if ($type === null) {
            return $owner;
        }

        foreach ($owner->ancestors() as $ancestor) {
            if ($ancestor->type === $type) {
                return $ancestor;
            }
        }

        return null;
    }

    /**
     * Whether the user may act on a review at all (start or decide).
     */
    public function canAct(User $user, EvidenceReview $review, bool $ignoreAssignment = false): bool
    {
        if (! $user->is_active || ! $review->status->isOpen()) {
            return false;
        }

        if (! $user->can(Permission::EvidenceVerify->value) && ! $user->can(Permission::EvidenceRequestRevision->value)) {
            return false;
        }

        $evidence = $review->evidence;
        $assignmentOk = $ignoreAssignment || $review->assigned_verifier_id === null || $review->assigned_verifier_id === $user->id;

        // The verifier must belong to the organization executing this stage AND have the evidence owner in scope.
        return $user->organization_id === $review->executor_organization_id
            && $this->scope->canAccess($user, $evidence->organization_id)
            && $assignmentOk
            && $evidence->archived_at === null
            && ! $this->violatesSeparationOfDuties($user, $review);
    }

    public function canDecide(User $user, EvidenceReview $review, ReviewDecision $decision): bool
    {
        $permission = $decision === ReviewDecision::RequestRevision
            ? Permission::EvidenceRequestRevision
            : Permission::EvidenceVerify;

        return $this->canAct($user, $review) && $user->can($permission->value);
    }

    public function violatesSeparationOfDuties(User $user, EvidenceReview $review): bool
    {
        return config('zi.verification.separation_of_duties')
            && in_array($user->id, [$review->evidence->uploaded_by, $review->evidenceVersion->uploaded_by], true);
    }

    public function start(User $user, EvidenceReview $review): void
    {
        DB::transaction(function () use ($user, $review) {
            $review = EvidenceReview::query()->lockForUpdate()->findOrFail($review->id);

            if ($review->status !== ReviewStatus::Pending) {
                throw ValidationException::withMessages(['review' => 'Tahap ini tidak dalam status menunggu verifikasi.']);
            }

            $review->update(['status' => ReviewStatus::InReview, 'started_at' => now(), 'started_by' => $user->id]);
            $review->evidence->forceFill(['verification_status' => VerificationStatus::InReview])->save();

            $this->audit->record('evidence.review_started', "Memulai {$review->stage_name} untuk bukti \"{$review->evidence->title}\"", $review->evidence, properties: [
                'review_id' => $review->id,
                'version_id' => $review->evidence_version_id,
            ]);
        });
    }

    public function decide(User $user, EvidenceReview $review, ReviewDecision $decision, ?string $note): void
    {
        DB::transaction(function () use ($user, $review, $decision, $note) {
            $review = EvidenceReview::query()->lockForUpdate()->findOrFail($review->id);

            if (! $this->canDecide($user, $review, $decision)) {
                throw ValidationException::withMessages(['decision' => 'Tahap ini sudah diputuskan atau Anda tidak berwenang memutuskannya.']);
            }

            $review->update([
                'status' => $decision->reviewStatus(),
                'decided_at' => now(),
                'decided_by' => $user->id,
                'decision_note' => $note,
            ]);

            $evidence = $review->evidence;
            $next = null;

            if ($decision === ReviewDecision::Accept) {
                $next = $evidence->reviews()
                    ->where('evidence_version_id', $review->evidence_version_id)
                    ->where('status', ReviewStatus::Waiting->value)
                    ->orderBy('stage_sequence')
                    ->first();

                $next?->update(['status' => ReviewStatus::Pending]);
                $status = $next ? VerificationStatus::Pending : VerificationStatus::Accepted;
            } else {
                $evidence->reviews()
                    ->where('evidence_version_id', $review->evidence_version_id)
                    ->where('status', ReviewStatus::Waiting->value)
                    ->update(['status' => ReviewStatus::Cancelled->value]);

                $status = $decision === ReviewDecision::RequestRevision
                    ? VerificationStatus::RevisionRequired
                    : VerificationStatus::Rejected;
            }

            $evidence->forceFill(['verification_status' => $status])->save();
            $this->assignments->completeFor($evidence, $user, AssignmentType::Verification);

            // A deactivated or moved uploader cannot receive the follow-up task; the decision itself still stands.
            if ($decision === ReviewDecision::RequestRevision
                && $evidence->uploader->is_active
                && $this->scope->canAccess($evidence->uploader, $evidence->organization_id)) {
                $this->assignments->assign($evidence, $evidence->uploader, AssignmentType::RevisionFollowUp, notes: $note, notify: false);
            }

            $this->audit->record("evidence.{$decision->value}", "{$review->stage_name}: {$decision->label()} bukti \"{$evidence->title}\"", $evidence, properties: [
                'review_id' => $review->id,
                'version_id' => $review->evidence_version_id,
                'decision' => $decision->value,
                'note' => $note,
            ]);

            DB::afterCommit(function () use ($evidence, $review, $decision, $note, $next) {
                $this->notifier->send(
                    $evidence->uploader,
                    "Bukti dukung: {$decision->reviewStatus()->label()}",
                    "{$review->stage_name} untuk \"{$evidence->title}\"".($note ? ": {$note}" : ''),
                    route('evidence.show', $evidence, false),
                );

                if ($next) {
                    $this->notifyVerifiers($next);
                }
            });
        });
    }

    /**
     * Open reviews the user can act on right now.
     *
     * @return Builder<EvidenceReview>
     */
    public function queueFor(User $user): Builder
    {
        $query = EvidenceReview::query()
            ->whereIn('status', ReviewStatus::openValues())
            ->where('executor_organization_id', $user->organization_id ?? 0)
            ->where(fn (Builder $query) => $query->whereNull('assigned_verifier_id')->orWhere('assigned_verifier_id', $user->id))
            ->whereHas('evidence', function (Builder $query) use ($user) {
                $query->whereNull('archived_at');
                $this->scope->apply($query, $user);

                if (config('zi.verification.separation_of_duties')) {
                    $query->where('uploaded_by', '!=', $user->id);
                }
            });

        if (config('zi.verification.separation_of_duties')) {
            $query->whereHas('evidenceVersion', fn (Builder $query) => $query->where('uploaded_by', '!=', $user->id));
        }

        return $query;
    }

    /**
     * Active users who may act on the review (notifications, verifier assignment candidates).
     *
     * @return Collection<int, User>
     */
    public function eligibleVerifiers(EvidenceReview $review, bool $ignoreAssignment = false): Collection
    {
        return User::query()
            ->active()
            ->where('organization_id', $review->executor_organization_id)
            ->permission([Permission::EvidenceVerify->value, Permission::EvidenceRequestRevision->value])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $this->canAct($user, $review, $ignoreAssignment))
            ->values();
    }

    public function assignVerifier(EvidenceReview $review, User $verifier): void
    {
        if (! $this->canAct($verifier, $review, ignoreAssignment: true)) {
            throw ValidationException::withMessages(['assignee_id' => 'Pengguna ini tidak memenuhi syarat untuk memverifikasi tahap ini.']);
        }

        $review->update(['assigned_verifier_id' => $verifier->id]);
        $this->assignments->assign($review->evidence, $verifier, AssignmentType::Verification);
    }

    private function notifyVerifiers(EvidenceReview $review): void
    {
        $this->notifier->send(
            $this->eligibleVerifiers($review),
            'Bukti dukung menunggu verifikasi',
            "{$review->stage_name}: \"{$review->evidence->title}\"",
            route('evidence.show', $review->evidence_id, false),
        );
    }
}
