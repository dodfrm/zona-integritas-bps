<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceCategory;
use App\Enums\PeriodStatus;
use App\Enums\ReviewDecision;
use App\Enums\ReviewStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\Zi\EvidenceRequest;
use App\Models\ActionPlan;
use App\Models\Evidence;
use App\Models\EvidenceReview;
use App\Models\EvidenceVersion;
use App\Models\Indicator;
use App\Models\Period;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\EvidenceService;
use App\Services\OrganizationScope;
use App\Services\VerificationService;
use App\Support\Options;
use App\Support\PeriodContext;
use App\Support\Present;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly EvidenceService $evidence,
        private readonly VerificationService $verification,
    ) {}

    public function index(Request $request, PeriodContext $context): Response
    {
        Gate::authorize('viewAny', Evidence::class);
        $user = $request->user();
        $period = $context->selected;

        $items = Evidence::query()
            ->where('period_id', $period->id ?? 0)
            ->tap(fn ($query) => $this->scope->apply($query, $user))
            ->with(['indicator:id,code,name', 'workProgram:id,name', 'actionPlan:id,title', 'uploader:id,name', 'latestVersion:id,evidence_id,version_number'])
            ->when(! $request->boolean('archived'), fn ($query) => $query->notArchived())
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->integer('area_id'), fn ($query, $areaId) => $query->where(fn (Builder $query) => $query
                ->whereHas('indicator', fn ($query) => $query->where('area_id', $areaId))
                ->orWhereHas('workProgram', fn ($query) => $query->where('area_id', $areaId))))
            ->when($request->integer('work_program_id'), fn ($query, $id) => $query->where('work_program_id', $id))
            ->when($request->string('category')->value(), fn ($query, $category) => $query->where('category', $category))
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('verification_status', $status))
            ->when($request->boolean('mine'), fn ($query) => $query->where('uploaded_by', $user->id))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Evidence $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category->label(),
                'indicator' => $item->indicator?->label(),
                'program' => $item->workProgram?->name,
                'action_plan' => $item->actionPlan?->title,
                'uploader' => Present::user($item->uploader),
                'version' => $item->latestVersion?->version_number,
                'status' => Present::enum($item->verification_status),
                'updated_at' => Present::datetime($item->updated_at),
                'archived' => $item->archived_at !== null,
            ]);

        return Inertia::render('evidence/index', [
            'items' => $items,
            'periods' => $context->options(),
            'selectedPeriodId' => $period?->id,
            'filters' => $request->only(['search', 'area_id', 'work_program_id', 'category', 'status', 'mine', 'archived']),
            'areaOptions' => $period ? Options::areas($period->guideline_version_id) : [],
            'programOptions' => $period ? WorkProgram::query()->where('period_id', $period->id)->orderBy('name')->get(['id', 'name'])
                ->map(fn (WorkProgram $program) => ['value' => (string) $program->id, 'label' => $program->name]) : [],
            'categoryOptions' => EvidenceCategory::options(),
            'statusOptions' => VerificationStatus::options(),
            'canCreate' => $period !== null && $user->can('createInPeriod', [Evidence::class, $period]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Evidence::class);
        $user = $request->user();

        $actionPlan = $request->integer('action_plan_id') ? ActionPlan::query()->find($request->integer('action_plan_id')) : null;
        $program = $request->integer('work_program_id') ? WorkProgram::query()->find($request->integer('work_program_id')) : $actionPlan?->workProgram;

        $periods = $this->scope->apply(Period::query(), $user)
            ->whereIn('status', [PeriodStatus::Draft->value, PeriodStatus::Active->value])
            ->with('organization:id,name')
            ->orderByDesc('year')
            ->get()
            ->filter(fn (Period $period) => $user->can('createInPeriod', [Evidence::class, $period]));

        $period = $periods->firstWhere('id', $request->integer('period_id') ?: $program?->period_id)
            ?? $periods->firstWhere('status', PeriodStatus::Active)
            ?? $periods->first();

        return Inertia::render('evidence/form', [
            'periodOptions' => $periods->map(fn (Period $option) => ['value' => (string) $option->id, 'label' => "{$option->name} — {$option->organization->name}"])->values(),
            'selectedPeriodId' => $period?->id,
            'defaults' => [
                'work_program_id' => $program?->period_id === $period?->id ? $program?->id : null,
                'action_plan_id' => $actionPlan?->period_id === $period?->id ? $actionPlan?->id : null,
                'indicator_id' => $actionPlan->indicator_id ?? ($request->integer('indicator_id') ?: null),
            ],
            ...$this->linkOptions($period),
            'categoryOptions' => EvidenceCategory::options(),
            'upload' => $this->uploadRules(),
        ]);
    }

    public function store(EvidenceRequest $request): RedirectResponse
    {
        $period = Period::query()->findOrFail($request->integer('period_id'));
        Gate::authorize('createInPeriod', [Evidence::class, $period]);

        $data = $this->resolveLinks($period, $request->safe()->except(['period_id', 'file']));
        $item = $this->evidence->create($request->user(), $period, $data, $request->file('file'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bukti dukung berhasil diunggah dan diajukan untuk verifikasi.']);

        return to_route('evidence.show', $item);
    }

    public function show(Request $request, Evidence $evidence): Response
    {
        Gate::authorize('view', $evidence);
        $user = $request->user();
        $evidence->load([
            'period', 'organization:id,name,code,type', 'indicator.area:id,code,name', 'workProgram:id,name', 'actionPlan:id,title', 'uploader:id,name',
            'versions.uploader:id,name',
            'reviews' => fn ($query) => $query->with(['decider:id,name', 'assignedVerifier:id,name', 'executorOrganization:id,name'])->orderByDesc('evidence_version_id')->orderBy('stage_sequence'),
        ]);

        $openReview = $evidence->reviews->first(fn (EvidenceReview $review) => $review->status->isOpen());
        $openReview?->setRelation('evidence', $evidence);

        return Inertia::render('evidence/show', [
            'item' => [
                'id' => $evidence->id,
                'title' => $evidence->title,
                'description' => $evidence->description,
                'notes' => $evidence->notes,
                'category' => Present::enum($evidence->category),
                'status' => Present::enum($evidence->verification_status),
                'archived_at' => Present::datetime($evidence->archived_at),
                'created_at' => Present::datetime($evidence->created_at),
                'period' => ['id' => $evidence->period->id, 'name' => $evidence->period->name, 'status' => Present::enum($evidence->period->status)],
                'organization' => Present::organization($evidence->organization),
                'indicator' => $evidence->indicator ? ['label' => $evidence->indicator->label(), 'area' => $evidence->indicator->area->label()] : null,
                'program' => $evidence->workProgram?->only(['id', 'name']),
                'action_plan' => $evidence->actionPlan?->only(['id', 'title']),
                'uploader' => Present::user($evidence->uploader),
            ],
            'versions' => $evidence->versions->map(fn (EvidenceVersion $version) => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'original_name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size' => $version->size,
                'checksum' => $version->checksum,
                'change_note' => $version->change_note,
                'uploader' => Present::user($version->uploader),
                'created_at' => Present::datetime($version->created_at),
            ])->values(),
            'reviews' => $evidence->reviews->map(fn (EvidenceReview $review) => [
                'id' => $review->id,
                'version_number' => $evidence->versions->firstWhere('id', $review->evidence_version_id)?->version_number,
                'stage_name' => $review->stage_name,
                'stage_sequence' => $review->stage_sequence,
                'executor_level' => Present::enum($review->executor_level),
                'executor_organization' => $review->executorOrganization->name,
                'status' => Present::enum($review->status),
                'assigned_verifier' => Present::user($review->assignedVerifier),
                'decider' => Present::user($review->decider),
                'decided_at' => Present::datetime($review->decided_at),
                'started_at' => Present::datetime($review->started_at),
                'decision_note' => $review->decision_note,
            ])->values(),
            'openReview' => $openReview ? [
                'id' => $openReview->id,
                'stage_name' => $openReview->stage_name,
                'status' => Present::enum($openReview->status),
                'can_start' => $openReview->status === ReviewStatus::Pending && $this->verification->canAct($user, $openReview),
                'decisions' => collect(ReviewDecision::cases())
                    ->filter(fn (ReviewDecision $decision) => $this->verification->canDecide($user, $openReview, $decision))
                    ->map(fn (ReviewDecision $decision) => Present::enum($decision))
                    ->values(),
                'separation_blocked' => $this->verification->violatesSeparationOfDuties($user, $openReview),
                'verifier_options' => $user->can('assign', $evidence)
                    ? $this->verification->eligibleVerifiers($openReview, ignoreAssignment: true)->map(fn (User $verifier) => ['value' => (string) $verifier->id, 'label' => $verifier->name])->values()
                    : [],
            ] : null,
            'assignments' => WorkProgramController::assignmentsFor('evidence_id', $evidence->id),
            'upload' => $this->uploadRules(),
            'can' => [
                'update' => $user->can('update', $evidence),
                'addVersion' => $user->can('addVersion', $evidence),
                'archive' => $user->can('archive', $evidence),
            ],
        ]);
    }

    public function edit(Evidence $evidence): Response
    {
        Gate::authorize('update', $evidence);

        return Inertia::render('evidence/form', [
            'periodOptions' => [['value' => (string) $evidence->period_id, 'label' => $evidence->period->name]],
            'selectedPeriodId' => $evidence->period_id,
            ...$this->linkOptions($evidence->period),
            'categoryOptions' => EvidenceCategory::options(),
            'upload' => $this->uploadRules(),
            'item' => [
                'id' => $evidence->id,
                'indicator_id' => $evidence->indicator_id,
                'work_program_id' => $evidence->work_program_id,
                'action_plan_id' => $evidence->action_plan_id,
                'title' => $evidence->title,
                'description' => $evidence->description,
                'category' => $evidence->category->value,
                'notes' => $evidence->notes,
            ],
        ]);
    }

    public function update(EvidenceRequest $request, Evidence $evidence): RedirectResponse
    {
        Gate::authorize('update', $evidence);

        $this->evidence->update($evidence, $this->resolveLinks($evidence->period, $request->safe()->except(['period_id', 'file'])));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Metadata bukti dukung diperbarui.']);

        return to_route('evidence.show', $evidence);
    }

    public function storeVersion(Request $request, Evidence $evidence): RedirectResponse
    {
        Gate::authorize('addVersion', $evidence);

        $data = $request->validate([
            'file' => ['required', ...EvidenceRequest::fileRules()],
            'change_note' => ['required', 'string', 'max:2000'],
        ], attributes: ['file' => 'berkas', 'change_note' => 'catatan perubahan']);

        $version = $this->evidence->addVersion($request->user(), $evidence, $request->file('file'), $data['change_note']);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Versi {$version->version_number} diunggah. Verifikasi dimulai ulang dari tahap pertama."]);

        return to_route('evidence.show', $evidence);
    }

    public function download(Evidence $evidence, EvidenceVersion $version): StreamedResponse
    {
        Gate::authorize('view', $evidence);

        return $this->evidence->download($version);
    }

    public function archive(Evidence $evidence): RedirectResponse
    {
        Gate::authorize('archive', $evidence);

        $this->evidence->archive($evidence);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bukti dukung diarsipkan. Berkas dan riwayatnya tetap tersimpan.']);

        return to_route('evidence.show', $evidence);
    }

    /**
     * Links must point to data of the same period; an action plan implies its program.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveLinks(Period $period, array $data): array
    {
        $errors = [];

        if (! empty($data['action_plan_id'])) {
            $plan = ActionPlan::query()->findOrFail((int) $data['action_plan_id']);

            if ($plan->period_id !== $period->id) {
                $errors['action_plan_id'] = 'Rencana aksi tidak berada pada periode ini.';
            } elseif (! empty($data['work_program_id']) && (int) $data['work_program_id'] !== $plan->work_program_id) {
                $errors['work_program_id'] = 'Program kerja tidak sesuai dengan rencana aksi yang dipilih.';
            }

            $data['work_program_id'] = $plan->work_program_id;
        }

        if (! empty($data['work_program_id']) && ! WorkProgram::query()->whereKey($data['work_program_id'])->where('period_id', $period->id)->exists()) {
            $errors['work_program_id'] = 'Program kerja tidak berada pada periode ini.';
        }

        if (! empty($data['indicator_id']) && ! Indicator::query()->whereKey($data['indicator_id'])
            ->whereHas('area', fn ($query) => $query->where('guideline_version_id', $period->guideline_version_id))->exists()) {
            $errors['indicator_id'] = 'Indikator tidak termasuk pedoman periode ini.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function linkOptions(?Period $period): array
    {
        if (! $period) {
            return ['areas' => [], 'programOptions' => [], 'actionPlanOptions' => []];
        }

        return [
            'areas' => Options::areasWithIndicators($period->guideline_version_id),
            'programOptions' => WorkProgram::query()->where('period_id', $period->id)->notArchived()->orderBy('name')->get(['id', 'name'])
                ->map(fn (WorkProgram $program) => ['value' => (string) $program->id, 'label' => $program->name]),
            'actionPlanOptions' => ActionPlan::query()->where('period_id', $period->id)->notArchived()->orderBy('title')->get(['id', 'title', 'work_program_id'])
                ->map(fn (ActionPlan $plan) => ['value' => (string) $plan->id, 'label' => $plan->title, 'work_program_id' => (string) $plan->work_program_id]),
        ];
    }

    /**
     * @return array{max_kb: int, extensions: list<string>}
     */
    private function uploadRules(): array
    {
        return [
            'max_kb' => (int) config('zi.evidence.max_kb'),
            'extensions' => config('zi.evidence.allowed_extensions'),
        ];
    }
}
