<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Indicator;
use App\Services\GuidelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IndicatorController extends Controller
{
    public function __construct(private readonly GuidelineService $guidelines) {}

    public function store(Request $request, Area $area): RedirectResponse
    {
        Gate::authorize('update', $area->guidelineVersion);

        $this->guidelines->saveIndicator($area, $this->validated($request, $area));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Indikator ditambahkan.']);

        return to_route('guidelines.show', $area->guideline_version_id);
    }

    public function update(Request $request, Indicator $indicator): RedirectResponse
    {
        $area = $indicator->area;
        Gate::authorize('update', $area->guidelineVersion);

        $this->guidelines->saveIndicator($area, $this->validated($request, $area, $indicator), $indicator);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Indikator diperbarui.']);

        return to_route('guidelines.show', $area->guideline_version_id);
    }

    public function destroy(Indicator $indicator): RedirectResponse
    {
        $area = $indicator->area;
        Gate::authorize('update', $area->guidelineVersion);

        $this->guidelines->deleteIndicator($indicator);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Indikator dihapus.']);

        return to_route('guidelines.show', $area->guideline_version_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Area $area, ?Indicator $indicator = null): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'code' => ['required', 'string', 'max:30', Rule::unique(Indicator::class)->where('area_id', $area->id)->ignore($indicator?->id)],
            'name' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'evidence_guidance' => ['nullable', 'string', 'max:5000'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65000'],
            'is_active' => ['boolean'],
        ], attributes: ['code' => 'kode', 'name' => 'nama indikator', 'parent_id' => 'indikator induk', 'weight' => 'bobot', 'sort_order' => 'urutan']);
    }
}
