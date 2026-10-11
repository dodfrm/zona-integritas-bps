<?php

namespace App\Http\Controllers;

use App\Enums\ActionPlanStatus;
use App\Enums\AssignmentType;
use App\Enums\PeriodStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\Zi\ActionPlanRequest;
use App\Models\ActionPlan;
use App\Models\ActionPlanStatusHistory;
use App\Models\Evidence;
use App\Models\Indicator;
use App\Models\WorkProgram;
use App\Services\ActionPlanService;
use App\Services\OrganizationScope;
use App\Support\Options;
use App\Support\PeriodContext;
use App\Support\Present;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActionPlanController extends Controller
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly ActionPlanService $plans,
    ) {}

    public function index(Request $request, PeriodContext $context): Response
    {
        Gate::authorize('viewAny', ActionPlan::class);
        $user = $request->user();
        $period = $context->selected;

        $plans = ActionPlan::query()
            ->where('period_id', $period->id ?? 0)
            ->tap(fn ($query) => $this->scope->apply($query, $user))
            ->with(['workProgram:id,name,code,area_id', 'workProgram.area:id,code,name', 'pic:id,name'])
            ->withCount(['evidence' => fn ($query) => $query->notArchived()])
            ->when(! $request->boolean('archived'), fn ($query) => $query->notArchived())
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->integer('work_program_id'), fn ($query, $id) => $query->where('work_program_id', $id))
            ->when($request->integer('area_id'), fn ($query, $id) => $query->whereHas('workProgram', fn ($query) => $query->where('area_id', $id)))
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('pic_user_id'), fn ($query, $id) => $query->where('pic_user_id', $id))
            ->when($request->boolean('overdue'), fn ($query) => $query->overdue())
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ActionPlan $plan) => [
                'id' => $plan->id,
                'title' => $plan->title,
                'program' => ['id' => $plan->workProgram->id, 'name' => $plan->workProgram->name],
                'area' => $plan->workProgram->area?->label(),
                'pic' => Present::user($plan->pic),
                'status' => Present::enum($plan->status),
                'progress_percent' => $plan->progress_percent,
                'due_date' => Present::date($plan->due_date),
                'is_overdue' => $plan->isOverdue(),
                'evidence_count' => $plan->evidence_count,
                'archived' => $plan->archived_at !== null,
            ]);

        return Inertia::render('action-plans/index', [
            'plans' => $plans,
            'periods' => $context->options(),
            'selectedPeriodId' => $period?->id,
            'filters' => $request->only(['search', 'work_program_id', 'area_id', 'status', 'pic_user_id', 'overdue', 'archived']),
            'programOptions' => $period ? WorkProgram::query()->where('period_id', $period->id)->notArchived()->orderBy('name')->get(['id', 'name'])
                ->map(fn (WorkProgram $program) => ['value' => (string) $program->id, 'label' => $program->name]) : [],
            'areaOptions' => $period ? Options::areas($period->guideline_version_id) : [],
            'userOptions' => $period ? Options::usersOf($period->organization_id) : [],
            'statusOptions' => ActionPlanStatus::options(),
            'canCreate' => $user->can('create', ActionPlan::class) && $period?->isEditable(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', ActionPlan::class);
        $user = $request->user();

        $programs = $this->scope->apply(WorkProgram::query(), $user)
            ->notArchived()
            ->whereHas('period', fn ($query) => $query->whereIn('status', [PeriodStatus::Draft->value, PeriodStatus::Active->value]))
            ->with(['period:id,name,status,organization_id,guideline_version_id', 'indicators:id'])
            ->orderBy('name')
            ->get()
            ->filter(fn (WorkProgram $program) => $user->can('createForProgram', [ActionPlan::class, $program]));

        $program = $programs->firstWhere('id', $request->integer('work_program_id'));

        return Inertia::render('action-plans/form', [
            'programOptions' => $programs->map(fn (WorkProgram $option) => ['value' => (string) $option->id, 'label' => "{$option->name} ({$option->period->name})"])->values(),
            'selectedProgramId' => $program?->id,
            ...($program ? $this->programProps($program) : ['indicatorOptions' => [], 'userOptions' => []]),
        ]);
    }

    public function store(ActionPlanRequest $request): RedirectResponse
    {
        $program = WorkProgram::query()->findOrFail($request->integer('work_program_id'));
        Gate::authorize('createForProgram', [ActionPlan::class, $program]);

        $plan = $this->plans->create($request->user(), $program, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rencana aksi berhasil dibuat.']);

        return to_route('action-plans.show', $plan);
    }

    public function show(Request $request, ActionPlan $actionPlan): Response
    {
        Gate::authorize('view', $actionPlan);
        $user = $request->user();
        $actionPlan->load(['workProgram:id,name,area_id', 'workProgram.area:id,code,name', 'period', 'organization:id,name,code,type', 'indicator', 'pic:id,name', 'statusHistories.changer:id,name']);

        $evidence = $actionPlan->evidence()->notArchived()->with('uploader:id,name')->latest()->get();

        return Inertia::render('action-plans/show', [
            'plan' => [
                'id' => $actionPlan->id,
                'title' => $actionPlan->title,
                'description' => $actionPlan->description,
                'output_target' => $actionPlan->output_target,
                'status' => Present::enum($actionPlan->status),
                'progress_percent' => $actionPlan->progress_percent,
                'obstacle_notes' => $actionPlan->obstacle_notes,
                'start_date' => Present::date($actionPlan->start_date),
                'due_date' => Present::date($actionPlan->due_date),
                'completed_at' => Present::datetime($actionPlan->completed_at),
                'archived_at' => Present::datetime($actionPlan->archived_at),
                'is_overdue' => $actionPlan->isOverdue(),
                'program' => ['id' => $actionPlan->workProgram->id, 'name' => $actionPlan->workProgram->name],
                'area' => $actionPlan->workProgram->area?->label(),
                'indicator' => $actionPlan->indicator?->label(),
                'period' => ['id' => $actionPlan->period->id, 'name' => $actionPlan->period->name, 'status' => Present::enum($actionPlan->period->status)],
                'organization' => Present::organization($actionPlan->organization),
                'pic' => Present::user($actionPlan->pic),
            ],
            'history' => $actionPlan->statusHistories->map(fn (ActionPlanStatusHistory $history) => [
                'id' => $history->id,
                'from' => Present::enum($history->from_status),
                'to' => Present::enum($history->to_status),
                'progress_percent' => $history->progress_percent,
                'note' => $history->note,
                'changer' => Present::user($history->changer),
                'created_at' => Present::datetime($history->created_at),
            ])->values(),
            'evidence' => $evidence->map(fn (Evidence $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category->label(),
                'status' => Present::enum($item->verification_status),
                'uploader' => Present::user($item->uploader),
                'created_at' => Present::datetime($item->created_at),
            ])->values(),
            'evidenceSummary' => array_values(array_filter(
                Present::enumCounts(VerificationStatus::cases(), $evidence->countBy(fn (Evidence $item) => $item->verification_status->value)),
                fn (array $row) => $row['count'] > 0,
            )),
            'assignments' => WorkProgramController::assignmentsFor('action_plan_id', $actionPlan->id),
            'assignmentTypes' => collect(AssignmentType::options())->whereIn('value', [AssignmentType::Implementer->value, AssignmentType::EvidenceCollection->value])->values(),
            'userOptions' => Options::usersOf($actionPlan->organization_id),
            'statusOptions' => ActionPlanStatus::options(),
            'can' => [
                'update' => $user->can('update', $actionPlan),
                'updateProgress' => $user->can('updateProgress', $actionPlan),
                'archive' => $user->can('archive', $actionPlan),
                'assign' => $user->can('assign', $actionPlan),
                'uploadEvidence' => $user->can('createInPeriod', [Evidence::class, $actionPlan->period]),
            ],
        ]);
    }

    public function edit(ActionPlan $actionPlan): Response
    {
        Gate::authorize('update', $actionPlan);

        return Inertia::render('action-plans/form', [
            ...$this->programProps($actionPlan->workProgram),
            'programOptions' => [['value' => (string) $actionPlan->work_program_id, 'label' => $actionPlan->workProgram->name]],
            'selectedProgramId' => $actionPlan->work_program_id,
            'plan' => [
                'id' => $actionPlan->id,
                'work_program_id' => $actionPlan->work_program_id,
                'indicator_id' => $actionPlan->indicator_id,
                'title' => $actionPlan->title,
                'description' => $actionPlan->description,
                'output_target' => $actionPlan->output_target,
                'start_date' => Present::date($actionPlan->start_date),
                'due_date' => Present::date($actionPlan->due_date),
                'pic_user_id' => $actionPlan->pic_user_id,
            ],
        ]);
    }

    public function update(ActionPlanRequest $request, ActionPlan $actionPlan): RedirectResponse
    {
        Gate::authorize('update', $actionPlan);

        $this->plans->update($actionPlan, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rencana aksi berhasil diperbarui.']);

        return to_route('action-plans.show', $actionPlan);
    }

    public function progress(Request $request, ActionPlan $actionPlan): RedirectResponse
    {
        Gate::authorize('updateProgress', $actionPlan);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ActionPlanStatus::class)],
            'progress_percent' => ['nullable', 'integer', 'between:0,100'],
            'obstacle_notes' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['progress_percent' => 'persentase progres', 'obstacle_notes' => 'catatan hambatan', 'note' => 'catatan perubahan']);

        $this->plans->updateProgress(
            $request->user(),
            $actionPlan,
            ActionPlanStatus::from($data['status']),
            isset($data['progress_percent']) ? (int) $data['progress_percent'] : null,
            $data['obstacle_notes'] ?? null,
            $data['note'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Progres rencana aksi diperbarui.']);

        return to_route('action-plans.show', $actionPlan);
    }

    public function archive(ActionPlan $actionPlan): RedirectResponse
    {
        Gate::authorize('archive', $actionPlan);

        $this->plans->archive($actionPlan);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rencana aksi diarsipkan. Riwayatnya tetap tersimpan.']);

        return to_route('action-plans.show', $actionPlan);
    }

    /**
     * @return array{indicatorOptions: list<array{value: string, label: string}>, userOptions: list<array{value: string, label: string}>}
     */
    private function programProps(WorkProgram $program): array
    {
        $program->loadMissing(['period', 'indicators']);
        $indicators = $program->indicators->isNotEmpty()
            ? $program->indicators
            : Indicator::query()->where('is_active', true)->whereHas('area', fn ($query) => $query->where('guideline_version_id', $program->period->guideline_version_id))->orderBy('code')->get();

        return [
            'indicatorOptions' => array_values($indicators->map(fn (Indicator $indicator) => ['value' => (string) $indicator->id, 'label' => $indicator->label()])->all()),
            'userOptions' => Options::usersOf($program->organization_id),
        ];
    }
}
