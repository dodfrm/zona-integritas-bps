<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Models\ActionPlan;
use App\Models\Assignment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Records who asked whom to do what. An assignment never grants extra permissions or scope:
 * the assignee must already be allowed to access the target organization.
 */
class AssignmentService
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
    ) {}

    public function assign(
        WorkProgram|ActionPlan|Evidence $target,
        User $assignee,
        AssignmentType $type,
        ?string $dueDate = null,
        ?string $notes = null,
        bool $notify = true,
    ): Assignment {
        $this->ensureAssignable($assignee, $target->organization_id);

        $assignment = Assignment::create([
            'organization_id' => $target->organization_id,
            'period_id' => $target->period_id,
            'type' => $type,
            ...$this->targetColumns($target),
            'assignee_id' => $assignee->id,
            'assigned_by' => auth()->id(),
            'status' => AssignmentStatus::Active,
            'due_date' => $dueDate,
            'notes' => $notes,
        ]);

        $this->audit->record('assignment.created', "Penugasan {$type->label()} kepada {$assignee->name}: {$this->targetLabel($target)}", $assignment, properties: [
            'assignee_id' => $assignee->id,
            'type' => $type->value,
            'target' => $this->targetColumns($target),
        ]);

        if ($notify) {
            $this->notifier->send($assignee, 'Penugasan baru', "{$type->label()}: {$this->targetLabel($target)}", $this->targetUrl($target));
        }

        return $assignment;
    }

    /**
     * Replace the PIC of a program/action plan, keeping the previous assignment as history.
     */
    public function syncPic(WorkProgram|ActionPlan $target, ?User $pic): void
    {
        $current = $this->query($target)
            ->where('type', AssignmentType::Pic->value)
            ->where('status', AssignmentStatus::Active->value)
            ->first();

        if ($current?->assignee_id === $pic?->id) {
            return;
        }

        $current?->update(['status' => AssignmentStatus::Cancelled, 'completed_at' => now()]);

        if ($pic) {
            $this->assign($target, $pic, AssignmentType::Pic);
        }
    }

    /**
     * Mark the user's open assignments of the given type on a target as done.
     */
    public function completeFor(WorkProgram|ActionPlan|Evidence $target, User $user, AssignmentType $type): void
    {
        $this->query($target)
            ->where('assignee_id', $user->id)
            ->where('type', $type->value)
            ->where('status', AssignmentStatus::Active->value)
            ->update(['status' => AssignmentStatus::Completed->value, 'completed_at' => now()]);
    }

    public function changeStatus(Assignment $assignment, AssignmentStatus $status): void
    {
        $assignment->update([
            'status' => $status,
            'completed_at' => $status === AssignmentStatus::Active ? null : now(),
        ]);

        $this->audit->record('assignment.status_changed', "Status penugasan #{$assignment->id} menjadi {$status->label()}", $assignment, properties: [
            'status' => $status->value,
        ]);
    }

    public function ensureAssignable(User $assignee, int $organizationId): void
    {
        if (! $assignee->is_active || ! $this->scope->canAccess($assignee, $organizationId)) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Pengguna tidak aktif atau berada di luar cakupan organisasi data ini.',
            ]);
        }
    }

    /**
     * @return array{work_program_id: int|null, action_plan_id: int|null, evidence_id: int|null}
     */
    private function targetColumns(WorkProgram|ActionPlan|Evidence $target): array
    {
        return [
            'work_program_id' => $target instanceof WorkProgram ? $target->id : null,
            'action_plan_id' => $target instanceof ActionPlan ? $target->id : null,
            'evidence_id' => $target instanceof Evidence ? $target->id : null,
        ];
    }

    /**
     * @return Builder<Assignment>
     */
    private function query(WorkProgram|ActionPlan|Evidence $target): Builder
    {
        return Assignment::query()->where(array_filter($this->targetColumns($target)));
    }

    private function targetLabel(WorkProgram|ActionPlan|Evidence $target): string
    {
        return $target instanceof WorkProgram ? $target->name : $target->title;
    }

    private function targetUrl(WorkProgram|ActionPlan|Evidence $target): string
    {
        return match (true) {
            $target instanceof WorkProgram => route('programs.show', $target, false),
            $target instanceof ActionPlan => route('action-plans.show', $target, false),
            default => route('evidence.show', $target, false),
        };
    }
}
