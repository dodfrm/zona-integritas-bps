<?php

namespace App\Http\Controllers;

use App\Enums\GuidelineStatus;
use App\Enums\PeriodStatus;
use App\Http\Requests\Zi\PeriodRequest;
use App\Models\ActionPlan;
use App\Models\Evidence;
use App\Models\GuidelineVersion;
use App\Models\Organization;
use App\Models\Period;
use App\Services\OrganizationScope;
use App\Services\PeriodService;
use App\Support\Present;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly PeriodService $periods,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Period::class);
        $user = $request->user();

        $periods = $this->scope->apply(Period::query(), $user)
            ->with(['organization:id,name,code,type', 'guidelineVersion:id,name'])
            ->withCount('workPrograms')
            ->when($request->integer('organization_id'), fn ($query, $id) => $query->where('organization_id', $id))
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('year')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Period $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'start_date' => Present::date($period->start_date),
                'end_date' => Present::date($period->end_date),
                'status' => Present::enum($period->status),
                'organization' => Present::organization($period->organization),
                'guideline' => $period->guidelineVersion->name,
                'programs_count' => $period->work_programs_count,
            ]);

        return Inertia::render('periods/index', [
            'periods' => $periods,
            'filters' => $request->only(['organization_id', 'status']),
            'statusOptions' => PeriodStatus::options(),
            'organizationOptions' => $this->organizationOptions($request),
            'canCreate' => $user->can('create', Period::class),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Period::class);

        return Inertia::render('periods/form', $this->formProps($request));
    }

    public function store(PeriodRequest $request): RedirectResponse
    {
        Gate::authorize('create', Period::class);

        $organization = Organization::query()->findOrFail($request->integer('organization_id'));
        abort_unless($this->scope->canAccess($request->user(), $organization->id), 403);

        $period = $this->periods->create(
            $request->user(),
            $organization,
            GuidelineVersion::query()->findOrFail($request->integer('guideline_version_id')),
            $request->safe()->only(['name', 'year', 'start_date', 'end_date', 'notes']),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode berhasil dibuat sebagai draf.']);

        return to_route('periods.show', $period);
    }

    public function show(Request $request, Period $period): Response
    {
        Gate::authorize('view', $period);
        $period->load(['organization:id,name,code,type', 'guidelineVersion:id,name,status']);

        return Inertia::render('periods/show', [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'start_date' => Present::date($period->start_date),
                'end_date' => Present::date($period->end_date),
                'notes' => $period->notes,
                'status' => Present::enum($period->status),
                'closed_at' => Present::datetime($period->closed_at),
                'organization' => Present::organization($period->organization),
                'guideline' => ['id' => $period->guidelineVersion->id, 'name' => $period->guidelineVersion->name],
            ],
            'summary' => [
                'programs' => $period->workPrograms()->notArchived()->count(),
                'action_plans' => ActionPlan::query()->where('period_id', $period->id)->notArchived()->count(),
                'evidence' => Evidence::query()->where('period_id', $period->id)->notArchived()->count(),
            ],
            'transitions' => collect($this->periods->allowedTransitions($period))
                ->map(fn (string $status) => Present::enum(PeriodStatus::from($status)))
                ->values(),
            'canUpdate' => $request->user()->can('update', $period),
        ]);
    }

    public function edit(Request $request, Period $period): Response
    {
        Gate::authorize('update', $period);

        return Inertia::render('periods/form', [
            ...$this->formProps($request, $period),
            'period' => [
                'id' => $period->id,
                'organization_id' => $period->organization_id,
                'guideline_version_id' => $period->guideline_version_id,
                'name' => $period->name,
                'year' => $period->year,
                'start_date' => Present::date($period->start_date),
                'end_date' => Present::date($period->end_date),
                'notes' => $period->notes,
                'can_change_guideline' => $period->status === PeriodStatus::Draft && ! $period->workPrograms()->exists(),
            ],
        ]);
    }

    public function update(PeriodRequest $request, Period $period): RedirectResponse
    {
        Gate::authorize('update', $period);

        $this->periods->update(
            $period,
            $request->safe()->only(['name', 'year', 'start_date', 'end_date', 'notes']),
            GuidelineVersion::query()->findOrFail($request->integer('guideline_version_id')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode berhasil diperbarui.']);

        return to_route('periods.show', $period);
    }

    public function changeStatus(Request $request, Period $period): RedirectResponse
    {
        Gate::authorize('update', $period);

        $data = $request->validate(['status' => ['required', Rule::enum(PeriodStatus::class)]]);
        $status = PeriodStatus::from($data['status']);
        $this->periods->changeStatus($request->user(), $period, $status);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Status periode menjadi {$status->label()}."]);

        return to_route('periods.show', $period);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Period $period = null): array
    {
        return [
            'organizationOptions' => $this->organizationOptions($request),
            'guidelineOptions' => GuidelineVersion::query()
                ->where('status', GuidelineStatus::Published->value)
                ->when($period, fn ($query) => $query->orWhere('id', $period->guideline_version_id))
                ->orderByDesc('published_at')
                ->get(['id', 'name'])
                ->map(fn (GuidelineVersion $version) => ['value' => (string) $version->id, 'label' => $version->name]),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function organizationOptions(Request $request): array
    {
        return array_values($this->scope->organizations($request->user())
            ->map(fn (Organization $organization) => ['value' => (string) $organization->id, 'label' => $organization->name])
            ->all());
    }
}
