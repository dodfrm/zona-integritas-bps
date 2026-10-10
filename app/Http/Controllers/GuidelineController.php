<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\GuidelineVersion;
use App\Models\Indicator;
use App\Services\GuidelineService;
use App\Support\Present;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GuidelineController extends Controller
{
    public function __construct(private readonly GuidelineService $guidelines) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GuidelineVersion::class);

        return Inertia::render('guidelines/index', [
            'versions' => GuidelineVersion::query()
                ->withCount(['areas', 'indicators', 'periods'])
                ->orderByRaw("CASE status WHEN 'published' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END")
                ->orderByDesc('id')
                ->get()
                ->map(fn (GuidelineVersion $version) => [
                    'id' => $version->id,
                    'name' => $version->name,
                    'reference' => $version->reference,
                    'status' => Present::enum($version->status),
                    'published_at' => Present::datetime($version->published_at),
                    'areas_count' => $version->areas_count,
                    'indicators_count' => $version->indicators_count,
                    'periods_count' => $version->periods_count,
                ]),
            'canCreate' => $request->user()->can('create', GuidelineVersion::class),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', GuidelineVersion::class);

        return Inertia::render('guidelines/form', [
            'copyOptions' => GuidelineVersion::query()->orderByDesc('id')->get(['id', 'name'])
                ->map(fn (GuidelineVersion $version) => ['value' => (string) $version->id, 'label' => $version->name]),
            'copyFrom' => $request->integer('copy_from') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', GuidelineVersion::class);

        $data = $this->validateVersion($request);
        $copyFrom = $request->filled('copy_from_id')
            ? GuidelineVersion::query()->findOrFail($request->integer('copy_from_id'))
            : null;

        $version = $this->guidelines->create($request->user(), $data, $copyFrom);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Versi pedoman draf berhasil dibuat.']);

        return to_route('guidelines.show', $version);
    }

    public function show(Request $request, GuidelineVersion $guideline): Response
    {
        Gate::authorize('view', $guideline);
        $guideline->load(['areas.indicators', 'copiedFrom:id,name']);

        return Inertia::render('guidelines/show', [
            'version' => [
                'id' => $guideline->id,
                'name' => $guideline->name,
                'description' => $guideline->description,
                'reference' => $guideline->reference,
                'status' => Present::enum($guideline->status),
                'published_at' => Present::datetime($guideline->published_at),
                'copied_from' => $guideline->copiedFrom?->only(['id', 'name']),
                'periods_count' => $guideline->periods()->count(),
            ],
            'areas' => $guideline->areas->map(fn (Area $area) => [
                'id' => $area->id,
                'code' => $area->code,
                'name' => $area->name,
                'description' => $area->description,
                'sort_order' => $area->sort_order,
                'is_active' => $area->is_active,
                'indicators' => $area->indicators->map(fn (Indicator $indicator) => [
                    'id' => $indicator->id,
                    'parent_id' => $indicator->parent_id,
                    'code' => $indicator->code,
                    'name' => $indicator->name,
                    'description' => $indicator->description,
                    'evidence_guidance' => $indicator->evidence_guidance,
                    'weight' => $indicator->weight,
                    'sort_order' => $indicator->sort_order,
                    'is_active' => $indicator->is_active,
                ])->values(),
            ])->values(),
            'can' => [
                'edit' => $request->user()->can('update', $guideline),
                'manage' => $request->user()->can('manage', $guideline),
            ],
        ]);
    }

    public function update(Request $request, GuidelineVersion $guideline): RedirectResponse
    {
        Gate::authorize('update', $guideline);

        $this->guidelines->update($guideline, $this->validateVersion($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Versi pedoman diperbarui.']);

        return to_route('guidelines.show', $guideline);
    }

    public function destroy(GuidelineVersion $guideline): RedirectResponse
    {
        Gate::authorize('update', $guideline);

        $this->guidelines->delete($guideline);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Versi pedoman draf dihapus.']);

        return to_route('guidelines.index');
    }

    public function publish(GuidelineVersion $guideline): RedirectResponse
    {
        Gate::authorize('manage', $guideline);

        $this->guidelines->publish($guideline);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedoman diberlakukan dan kini terkunci.']);

        return to_route('guidelines.show', $guideline);
    }

    public function retire(GuidelineVersion $guideline): RedirectResponse
    {
        Gate::authorize('manage', $guideline);

        $this->guidelines->retire($guideline);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedoman tidak lagi dapat dipilih untuk periode baru.']);

        return to_route('guidelines.show', $guideline);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVersion(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(GuidelineVersion::class)->ignore($request->route('guideline'))],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference' => ['nullable', 'string', 'max:255'],
            'copy_from_id' => ['nullable', 'integer', Rule::exists(GuidelineVersion::class, 'id')],
        ], attributes: ['name' => 'nama versi', 'reference' => 'rujukan regulasi']);
    }
}
