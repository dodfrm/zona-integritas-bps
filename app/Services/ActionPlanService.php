<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Models\ActionPlan;
use App\Models\Indicator;
use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActionPlanService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AssignmentService $assignments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, WorkProgram $program, array $data): ActionPlan
    {
        return DB::transaction(function () use ($user, $program, $data) {
            $plan = new ActionPlan(Arr::except($data, ['pic_user_id', 'work_program_id']));
            $plan->work_program_id = $program->id;
            $plan->organization_id = $program->organization_id;
            $plan->period_id = $program->period_id;
            $plan->status = ActionPlanStatus::NotStarted;
            $plan->created_by = $user->id;
            $plan->setRelation('period', $program->period);

            $this->ensureIndicatorMatchesPeriod($plan);
            $plan->save();

            $plan->statusHistories()->create([
                'to_status' => ActionPlanStatus::NotStarted,
                'note' => 'Rencana aksi dibuat',
                'changed_by' => $user->id,
            ]);

            $this->setPic($plan, $data['pic_user_id'] ?? null);

            $this->audit->record('action_plan.created', "Membuat rencana aksi \"{$plan->title}\"", $plan, properties: [
                'work_program_id' => $program->id,
            ]);

            return $plan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ActionPlan $plan, array $data): void
    {
        DB::transaction(function () use ($plan, $data) {
            $plan->fill(Arr::except($data, ['pic_user_id', 'work_program_id']));
            $this->ensureIndicatorMatchesPeriod($plan);
            $plan->save();

            $changes = $this->audit->changesOf($plan);
            $this->setPic($plan, $data['pic_user_id'] ?? null);

            if ($changes) {
                $this->audit->record('action_plan.updated', "Mengubah rencana aksi \"{$plan->title}\"", $plan, properties: $changes);
            }
        });
    }

    /**
     * Status / progress / obstacles, always recorded in the status history.
     */
    public function updateProgress(User $user, ActionPlan $plan, ActionPlanStatus $status, ?int $progress, ?string $obstacles, ?string $note): void
    {
        DB::transaction(function () use ($user, $plan, $status, $progress, $obstacles, $note) {
            $from = $plan->status;

            $plan->forceFill([
                'status' => $status,
                'progress_percent' => $progress,
                'obstacle_notes' => $obstacles,
                'completed_at' => $status === ActionPlanStatus::Completed ? ($plan->completed_at ?? now()) : null,
            ])->save();

            $changes = $this->audit->changesOf($plan);

            if (! $changes) {
                return;
            }

            $plan->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $status,
                'progress_percent' => $progress,
                'note' => $note,
                'changed_by' => $user->id,
            ]);

            $this->audit->record('action_plan.progress_updated', "Memperbarui progres rencana aksi \"{$plan->title}\": {$status->label()}", $plan, properties: [
                ...$changes,
                'note' => $note,
            ]);
        });
    }

    public function archive(ActionPlan $plan): void
    {
        $plan->forceFill(['archived_at' => now()])->save();
        $this->audit->record('action_plan.archived', "Mengarsipkan rencana aksi \"{$plan->title}\"", $plan);
    }

    private function setPic(ActionPlan $plan, mixed $picUserId): void
    {
        $previous = $plan->pic_user_id;
        $pic = $picUserId ? User::query()->findOrFail((int) $picUserId) : null;

        if ($pic) {
            $this->assignments->ensureAssignable($pic, $plan->organization_id);
        }

        $plan->forceFill(['pic_user_id' => $pic?->id])->save();

        if ($previous !== $pic?->id) {
            $this->assignments->syncPic($plan, $pic);
        }
    }

    private function ensureIndicatorMatchesPeriod(ActionPlan $plan): void
    {
        if (! $plan->indicator_id) {
            return;
        }

        $valid = Indicator::query()
            ->whereKey($plan->indicator_id)
            ->whereHas('area', fn ($query) => $query->where('guideline_version_id', $plan->period->guideline_version_id))
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['indicator_id' => 'Indikator tidak termasuk pedoman yang digunakan periode ini.']);
        }
    }
}
