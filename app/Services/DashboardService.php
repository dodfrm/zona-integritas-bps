<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\AssignmentStatus;
use App\Enums\VerificationStatus;
use App\Models\ActionPlan;
use App\Models\Area;
use App\Models\Assignment;
use App\Models\Evidence;
use App\Models\Period;
use App\Models\User;
use App\Models\WorkProgram;
use App\Support\Present;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard figures, always computed from the database for one period inside the user's scope.
 */
class DashboardService
{
    public function __construct(private readonly OrganizationScope $scope) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Period $period): array
    {
        $plans = ActionPlan::query()->where('period_id', $period->id)->notArchived();

        $byStatus = (clone $plans)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $evidenceByStatus = Evidence::query()->where('period_id', $period->id)->notArchived()
            ->select('verification_status', DB::raw('count(*) as total'))->groupBy('verification_status')->pluck('total', 'verification_status');

        return [
            'programs' => WorkProgram::query()->where('period_id', $period->id)->notArchived()->count(),
            'action_plans' => [
                'total' => (int) $byStatus->sum(),
                'overdue' => (clone $plans)->overdue()->count(),
                'by_status' => Present::enumCounts(ActionPlanStatus::cases(), $byStatus),
            ],
            'evidence' => [
                'total' => (int) $evidenceByStatus->sum(),
                'by_status' => Present::enumCounts(VerificationStatus::cases(), $evidenceByStatus),
            ],
        ];
    }

    /**
     * Per area: action plan completion (completed ÷ non-cancelled) and evidence acceptance (accepted ÷ all).
     *
     * @return list<array<string, mixed>>
     */
    public function areaProgress(Period $period): array
    {
        $plans = ActionPlan::query()
            ->join('work_programs', 'work_programs.id', '=', 'action_plans.work_program_id')
            ->where('action_plans.period_id', $period->id)
            ->whereNull('action_plans.archived_at')
            ->where('action_plans.status', '!=', ActionPlanStatus::Cancelled->value)
            ->groupBy('work_programs.area_id')
            ->select('work_programs.area_id', DB::raw('count(*) as total'), DB::raw("sum(case when action_plans.status = 'completed' then 1 else 0 end) as completed"))
            ->get()
            ->keyBy('area_id');

        // Evidence is attributed to the indicator's area, falling back to the linked program's area.
        $evidence = Evidence::query()
            ->leftJoin('indicators', 'indicators.id', '=', 'evidence.indicator_id')
            ->leftJoin('work_programs', 'work_programs.id', '=', 'evidence.work_program_id')
            ->where('evidence.period_id', $period->id)
            ->whereNull('evidence.archived_at')
            ->groupBy(DB::raw('coalesce(indicators.area_id, work_programs.area_id)'))
            ->select(DB::raw('coalesce(indicators.area_id, work_programs.area_id) as area_id'), DB::raw('count(*) as total'), DB::raw("sum(case when evidence.verification_status = 'accepted' then 1 else 0 end) as accepted"))
            ->get()
            ->keyBy('area_id');

        $programs = WorkProgram::query()->where('period_id', $period->id)->notArchived()
            ->groupBy('area_id')->select('area_id', DB::raw('count(*) as total'))->pluck('total', 'area_id');

        $rows = [];

        foreach (Area::query()->where('guideline_version_id', $period->guideline_version_id)->orderBy('sort_order')->orderBy('code')->get() as $area) {
            $rows[] = $this->areaRow($area->id, $area->label(), $programs, $plans, $evidence);
        }

        $unassigned = $this->areaRow(null, 'Tanpa area', $programs, $plans, $evidence);

        if ($unassigned['programs'] + $unassigned['action_plans_total'] + $unassigned['evidence_total'] > 0) {
            $rows[] = $unassigned;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function attention(Period $period): array
    {
        return [
            'overdue' => ActionPlan::query()->where('period_id', $period->id)->notArchived()->overdue()
                ->with('pic:id,name')->orderBy('due_date')->limit(6)->get()
                ->map(fn (ActionPlan $plan) => [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'due_date' => Present::date($plan->due_date),
                    'pic' => Present::user($plan->pic),
                    'status' => Present::enum($plan->status),
                    'obstacle_notes' => $plan->obstacle_notes,
                ])->values(),
            'revisions' => Evidence::query()->where('period_id', $period->id)->notArchived()
                ->where('verification_status', VerificationStatus::RevisionRequired->value)
                ->with('uploader:id,name')->latest('updated_at')->limit(6)->get()
                ->map(fn (Evidence $evidence) => [
                    'id' => $evidence->id,
                    'title' => $evidence->title,
                    'uploader' => Present::user($evidence->uploader),
                    'updated_at' => Present::datetime($evidence->updated_at),
                ])->values(),
        ];
    }

    /**
     * Work of the current user (across all periods in scope).
     *
     * @return array<string, mixed>
     */
    public function myWork(User $user, int $limit = 6): array
    {
        $plans = $this->scope->apply(ActionPlan::query(), $user)->notArchived()
            ->where(fn ($query) => $query->where('pic_user_id', $user->id)
                ->orWhereHas('assignments', fn ($query) => $query->where('assignee_id', $user->id)->where('status', AssignmentStatus::Active->value)))
            ->whereIn('status', ActionPlanStatus::openValues())
            ->with('workProgram:id,name')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(fn (ActionPlan $plan) => [
                'id' => $plan->id,
                'title' => $plan->title,
                'program' => $plan->workProgram->name,
                'due_date' => Present::date($plan->due_date),
                'is_overdue' => $plan->isOverdue(),
                'status' => Present::enum($plan->status),
                'progress_percent' => $plan->progress_percent,
                'is_pic' => $plan->pic_user_id === $user->id,
            ])->values();

        $myPrograms = $this->scope->apply(WorkProgram::query(), $user)->notArchived()
            ->where('pic_user_id', $user->id)
            ->withCount([
                'actionPlans as action_plans_count' => fn ($query) => $query->notArchived()->where('status', '!=', ActionPlanStatus::Cancelled->value),
                'actionPlans as completed_action_plans_count' => fn ($query) => $query->notArchived()->where('status', ActionPlanStatus::Completed->value),
            ])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (WorkProgram $program) => [
                'id' => $program->id,
                'name' => $program->name,
                'status' => Present::enum($program->status),
                'total' => $program->action_plans_count,
                'completed' => $program->completed_action_plans_count,
                'target_date' => Present::date($program->target_date),
            ])->values();

        $revisions = $this->scope->apply(Evidence::query(), $user)->notArchived()
            ->where('uploaded_by', $user->id)
            ->whereIn('verification_status', [VerificationStatus::RevisionRequired->value, VerificationStatus::Rejected->value])
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'verification_status', 'updated_at'])
            ->map(fn (Evidence $evidence) => [
                'id' => $evidence->id,
                'title' => $evidence->title,
                'status' => Present::enum($evidence->verification_status),
                'updated_at' => Present::datetime($evidence->updated_at),
            ])->values();

        $assignments = $this->scope->apply(Assignment::query(), $user)
            ->where('assignee_id', $user->id)
            ->where('status', AssignmentStatus::Active->value)
            ->with(['workProgram:id,name', 'actionPlan:id,title', 'evidence:id,title', 'assigner:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(fn (Assignment $assignment) => self::presentAssignment($assignment))
            ->values();

        return compact('plans', 'myPrograms', 'revisions', 'assignments');
    }

    /**
     * @return array<string, mixed>
     */
    public static function presentAssignment(Assignment $assignment): array
    {
        [$label, $route, $id] = match (true) {
            $assignment->workProgram !== null => [$assignment->workProgram->name, 'programs.show', $assignment->work_program_id],
            $assignment->actionPlan !== null => [$assignment->actionPlan->title, 'action-plans.show', $assignment->action_plan_id],
            default => [$assignment->evidence->title ?? '-', 'evidence.show', $assignment->evidence_id],
        };

        return [
            'id' => $assignment->id,
            'type' => Present::enum($assignment->type),
            'status' => Present::enum($assignment->status),
            'target' => $label,
            'url' => $id ? route($route, $id, false) : null,
            'assigner' => Present::user($assignment->assigner),
            'due_date' => Present::date($assignment->due_date),
            'is_overdue' => $assignment->due_date !== null && $assignment->due_date->lt(today()),
            'notes' => $assignment->notes,
            'created_at' => Present::datetime($assignment->created_at),
        ];
    }

    /**
     * @param  Collection<array-key, mixed>  $programs
     * @param  Collection<array-key, mixed>  $plans
     * @param  Collection<array-key, mixed>  $evidence
     * @return array<string, mixed>
     */
    private function areaRow(?int $areaId, string $label, $programs, $plans, $evidence): array
    {
        $key = $areaId ?? '';
        $plan = $plans->get($key);
        $item = $evidence->get($key);

        return [
            'area_id' => $areaId,
            'label' => $label,
            'programs' => (int) ($programs[$key] ?? 0),
            'action_plans_total' => (int) ($plan->total ?? 0),
            'action_plans_completed' => (int) ($plan->completed ?? 0),
            'evidence_total' => (int) ($item->total ?? 0),
            'evidence_accepted' => (int) ($item->accepted ?? 0),
        ];
    }
}
