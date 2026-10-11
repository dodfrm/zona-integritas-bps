<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\GuidelineVersion;
use App\Services\GuidelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AreaController extends Controller
{
    public function __construct(private readonly GuidelineService $guidelines) {}

    public function store(Request $request, GuidelineVersion $guideline): RedirectResponse
    {
        Gate::authorize('update', $guideline);

        $this->guidelines->saveArea($guideline, $this->validated($request, $guideline));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Area ditambahkan.']);

        return to_route('guidelines.show', $guideline);
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        Gate::authorize('update', $area->guidelineVersion);

        $this->guidelines->saveArea($area->guidelineVersion, $this->validated($request, $area->guidelineVersion, $area), $area);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Area diperbarui.']);

        return to_route('guidelines.show', $area->guideline_version_id);
    }

    public function destroy(Area $area): RedirectResponse
    {
        Gate::authorize('update', $area->guidelineVersion);

        $this->guidelines->deleteArea($area);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Area dihapus.']);

        return to_route('guidelines.show', $area->guideline_version_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, GuidelineVersion $version, ?Area $area = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique(Area::class)->where('guideline_version_id', $version->id)->ignore($area?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65000'],
            'is_active' => ['boolean'],
        ], attributes: ['code' => 'kode', 'name' => 'nama area', 'sort_order' => 'urutan']);
    }
}
