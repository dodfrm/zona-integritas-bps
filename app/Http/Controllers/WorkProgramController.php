<?php

namespace App\Http\Controllers;

use App\Enums\ActionPlanStatus;
use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Enums\PeriodStatus;
use App\Enums\ProgramStatus;
use App\Http\Requests\Zi\WorkProgramRequest;
use App\Models\ActionPlan;
use App\Models\Assignment;
use App\Models\Evidence;
use App\Models\Indicator;
use App\Models\Period;
use App\Models\WorkProgram;
use App\Services\OrganizationScope;
use App\Services\WorkProgramService;
use App\Support\Options;
use App\Support\PeriodContext;
use App\Support\Present;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkProgramController extends Controller
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly WorkProgramService $programs,
    ) {}

    public function index(Request $request, PeriodContext $context): Response
    {
        Gate::authorize('viewAny', WorkProgram::class);
        $user = $request->user();
        $period = $context->selected;

        $programs = WorkProgram::query()
            ->where('period_id', $period->id ?? 0)
            ->tap(fn ($query) => $this->scope->apply($query, $user))
            ->with(['area:id,code,name', 'pic:id,name'])
            ->withCount([
                'actionPlans as action_plans_count' => fn ($query) => $query->notArchived()->where('status', '!=', ActionPlanStatus::Cancelled->value),
                'actionPlans as completed_action_plans_count' => fn ($query) => $query->notArchived()->where('status', ActionPlanStatus::Completed->value),
                'actionPlans as overdue_action_plans_count' => fn ($query) => $query->notArchived()->overdue(),
            ])
            ->when(! $request->boolean('archived'), fn ($query) => $query->notArchived())
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->when($request->integer('area_id'), fn ($query, $id) => $query->where('area_id', $id))
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('pic_user_id'), fn ($query, $id) => $query->where('pic_user_id', $id))
            ->orderBy('code')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (WorkProgram $program) => [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'area' => $program->area?->label(),
                'pic' => Present::user($program->pic),
                'status' => Present::enum($program->status),
                'target_date' => Present::date($program->target_date),
                'action_plans_count' => $program->action_plans_count,
                'completed_action_plans_count' => $program->completed_action_plans_count,
                'overdue_action_plans_count' => $program->overdue_action_plans_count,
                'archived' => $program->archived_at !== null,
            ]);

        return Inertia::render('programs/index', [
            'programs' => $programs,
            'periods' => $context->options(),
            'selectedPeriodId' => $period?->id,
            'filters' => $request->only(['search', 'area_id', 'status', 'pic_user_id', 'archived']),
            'areaOptions' => $period ? Options::areas($period->guideline_version_id) : [],
            'userOptions' => $period ? Options::usersOf($period->organization_id) : [],
            'statusOptions' => ProgramStatus::options(),
            'canCreate' => $period !== null && $user->can('createInPeriod', [WorkProgram::class, $period]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', WorkProgram::class);

        return Inertia::render('programs/form', $this->formProps($request, null));
    }

    public function store(WorkProgramRequest $request): RedirectResponse
    {
        $period = Period::query()->findOrFail($request->integer('period_id'));
        Gate::authorize('createInPeriod', [WorkProgram::class, $period]);

        $program = $this->programs->create($request->user(), $period, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Program kerja berhasil dibuat.']);

        return to_route('programs.show', $program);
    }

    public function show(Request $request, WorkProgram $program): Response
    {
        Gate::authorize('view', $program);
        $user = $request->user();
        $program->load(['period:id,name,status,organization_id,guideline_version_id', 'organization:id,name,code,type', 'area:id,code,name', 'pic:id,name', 'creator:id,name', 'indicators.area:id,code']);

        $actionPlans = $program->actionPlans()
            ->with('pic:id,name')
            ->withCount(['evidence' => fn ($query) => $query->notArchived()])
            ->orderByRaw('archived_at IS NOT NULL')
            ->orderBy('due_date')
            ->get();

        $countable = $actionPlans->whereNull('archived_at')->where('status', '!=', ActionPlanStatus::Cancelled);

        return Inertia::render('programs/show', [
            'program' => [
                'id' => $program->id,
                'code' => $program->code,
                'name' => $program->name,
                'description' => $program->description,
                'objective' => $program->objective,
                'notes' => $program->notes,
                'status' => Present::enum($program->status),
                'start_date' => Present::date($program->start_date),
                'target_date' => Present::date($program->target_date),
                'archived_at' => Present::datetime($program->archived_at),
                'period' => ['id' => $program->period->id, 'name' => $program->period->name, 'status' => Present::enum($program->period->status)],
                'organization' => Present::organization($program->organization),
                'area' => $program->area?->label(),
                'pic' => Present::user($program->pic),
                'creator' => Present::user($program->creator),
                'indicators' => $program->indicators->map(fn (Indicator $indicator) => ['id' => $indicator->id, 'label' => $indicator->label()])->values(),
            ],
            'progress' => [
                'total' => $countable->count(),
                'completed' => $countable->where('status', ActionPlanStatus::Completed)->count(),
            ],
            'actionPlans' => $actionPlans->map(fn (ActionPlan $plan) => [
                'id' => $plan->id,
                'title' => $plan->title,
                'status' => Present::enum($plan->status),
                'progress_percent' => $plan->progress_percent,
                'due_date' => Present::date($plan->due_date),
                'is_overdue' => $plan->isOverdue(),
                'pic' => Present::user($plan->pic),
                'evidence_count' => $plan->evidence_count,
                'archived' => $plan->archived_at !== null,
            ])->values(),
            'evidence' => Evidence::query()
                ->notArchived()
                ->where(fn ($query) => $query->where('work_program_id', $program->id)
                    ->orWhereIn('action_plan_id', $actionPlans->pluck('id')))
                ->latest()
                ->limit(50)
                ->get(['id', 'title', 'category', 'verification_status', 'created_at'])
                ->map(fn (Evidence $evidence) => [
                    'id' => $evidence->id,
                    'title' => $evidence->title,
                    'category' => $evidence->category->label(),
                    'status' => Present::enum($evidence->verification_status),
                    'created_at' => Present::datetime($evidence->created_at),
                ]),
            'assignments' => $this->assignmentsFor('work_program_id', $program->id),
            'assignmentTypes' => collect(AssignmentType::options())->whereIn('value', [AssignmentType::Implementer->value, AssignmentType::EvidenceCollection->value])->values(),
            'userOptions' => Options::usersOf($program->organization_id),
            'can' => [
                'update' => $user->can('update', $program),
                'archive' => $user->can('archive', $program),
                'assign' => $user->can('assign', $program),
                'createActionPlan' => $user->can('createForProgram', [ActionPlan::class, $program]),
                'uploadEvidence' => $user->can('createInPeriod', [Evidence::class, $program->period]),
            ],
        ]);
    }

    public function edit(Request $request, WorkProgram $program): Response
    {
        Gate::authorize('update', $program);
        $program->load('indicators:id');

        return Inertia::render('programs/form', [
            ...$this->formProps($request, $program->period),
            'program' => [
                'id' => $program->id,
                'period_id' => $program->period_id,
                'area_id' => $program->area_id,
                'indicator_ids' => $program->indicators->pluck('id')->map(fn (int $id) => (string) $id)->values(),
                'code' => $program->code,
                'name' => $program->name,
                'description' => $program->description,
                'objective' => $program->objective,
                'pic_user_id' => $program->pic_user_id,
                'start_date' => Present::date($program->start_date),
                'target_date' => Present::date($program->target_date),
                'status' => $program->status->value,
                'notes' => $program->notes,
            ],
        ]);
    }

    public function update(WorkProgramRequest $request, WorkProgram $program): RedirectResponse
    {
        Gate::authorize('update', $program);

        $this->programs->update($program, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Program kerja berhasil diperbarui.']);

        return to_route('programs.show', $program);
    }

    public function archive(WorkProgram $program): RedirectResponse
    {
        Gate::authorize('archive', $program);

        $this->programs->archive($program);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Program kerja diarsipkan. Riwayatnya tetap tersimpan.']);

        return to_route('programs.show', $program);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Period $fixedPeriod): array
    {
        $user = $request->user();
        $periods = $this->scope->apply(Period::query(), $user)
            ->whereIn('status', [PeriodStatus::Draft->value, PeriodStatus::Active->value])
            ->with('organization:id,name')
            ->orderByDesc('year')
            ->get()
            ->filter(fn (Period $period) => $user->can('createInPeriod', [WorkProgram::class, $period]));

        $period = $fixedPeriod
            ?? $periods->firstWhere('id', $request->integer('period_id'))
            ?? $periods->firstWhere('status', PeriodStatus::Active)
            ?? $periods->first();

        return [
            'periodOptions' => $periods->map(fn (Period $option) => ['value' => (string) $option->id, 'label' => "{$option->name} — {$option->organization->name}"])->values(),
            'selectedPeriodId' => $period?->id,
            'areas' => $period ? Options::areasWithIndicators($period->guideline_version_id) : [],
            'userOptions' => $period ? Options::usersOf($period->organization_id) : [],
            'statusOptions' => ProgramStatus::options(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function assignmentsFor(string $column, int $id): array
    {
        return array_values(Assignment::query()
            ->where($column, $id)
            ->with(['assignee:id,name', 'assigner:id,name'])
            ->orderByRaw('CASE status WHEN ? THEN 0 ELSE 1 END', [AssignmentStatus::Active->value])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Assignment $assignment) => [
                'id' => $assignment->id,
                'type' => Present::enum($assignment->type),
                'status' => Present::enum($assignment->status),
                'assignee' => Present::user($assignment->assignee),
                'assigner' => Present::user($assignment->assigner),
                'due_date' => Present::date($assignment->due_date),
                'notes' => $assignment->notes,
                'created_at' => Present::datetime($assignment->created_at),
                'completed_at' => Present::datetime($assignment->completed_at),
            ])
            ->all());
    }
}
