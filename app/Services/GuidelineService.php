<?php

namespace App\Services;

use App\Enums\GuidelineStatus;
use App\Models\Area;
use App\Models\GuidelineVersion;
use App\Models\Indicator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Guideline versions are snapshots: draft → published (frozen) → retired.
 * To change a published guideline, copy it into a new draft, edit, then publish.
 */
class GuidelineService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?GuidelineVersion $copyFrom = null): GuidelineVersion
    {
        return DB::transaction(function () use ($user, $data, $copyFrom) {
            $version = new GuidelineVersion($data);
            $version->status = GuidelineStatus::Draft;
            $version->created_by = $user->id;
            $version->copied_from_id = $copyFrom?->id;
            $version->save();

            if ($copyFrom) {
                $this->copyContent($copyFrom, $version);
            }

            $this->audit->record('guideline.created', "Membuat versi pedoman \"{$version->name}\"".($copyFrom ? " (salinan dari \"{$copyFrom->name}\")" : ''), $version);

            return $version;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuidelineVersion $version, array $data): void
    {
        $version->fill($data)->save();

        if ($changes = $this->audit->changesOf($version)) {
            $this->audit->record('guideline.updated', "Mengubah versi pedoman \"{$version->name}\"", $version, properties: $changes);
        }
    }

    public function publish(GuidelineVersion $version): void
    {
        if ($version->status !== GuidelineStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'Hanya versi draf yang dapat diberlakukan.']);
        }

        if (! $version->areas()->exists()) {
            throw ValidationException::withMessages(['status' => 'Tambahkan minimal satu area sebelum memberlakukan pedoman.']);
        }

        $version->forceFill(['status' => GuidelineStatus::Published, 'published_at' => now()])->save();
        $this->audit->record('guideline.published', "Memberlakukan pedoman \"{$version->name}\"", $version);
    }

    /**
     * Retired versions cannot be chosen for new periods; existing periods keep using them.
     */
    public function retire(GuidelineVersion $version): void
    {
        if ($version->status !== GuidelineStatus::Published) {
            throw ValidationException::withMessages(['status' => 'Hanya versi yang berlaku yang dapat dinonaktifkan.']);
        }

        $version->forceFill(['status' => GuidelineStatus::Retired])->save();
        $this->audit->record('guideline.retired', "Menyatakan pedoman \"{$version->name}\" tidak berlaku untuk periode baru", $version);
    }

    public function delete(GuidelineVersion $version): void
    {
        if (! $version->isEditable() || $version->periods()->exists()) {
            throw ValidationException::withMessages(['status' => 'Hanya versi draf yang belum digunakan yang dapat dihapus.']);
        }

        $this->audit->record('guideline.deleted', "Menghapus versi pedoman draf \"{$version->name}\"", $version);
        $version->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveArea(GuidelineVersion $version, array $data, ?Area $area = null): Area
    {
        $area ??= new Area;
        $area->guideline_version_id = $version->id;
        $isNew = ! $area->exists;
        $area->fill($data)->save();

        $this->audit->record($isNew ? 'area.created' : 'area.updated', ($isNew ? 'Menambah' : 'Mengubah')." area \"{$area->label()}\" pada pedoman \"{$version->name}\"", $area, properties: $isNew ? $data : $this->audit->changesOf($area));

        return $area;
    }

    public function deleteArea(Area $area): void
    {
        $this->audit->record('area.deleted', "Menghapus area \"{$area->label()}\"", $area);
        $area->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveIndicator(Area $area, array $data, ?Indicator $indicator = null): Indicator
    {
        if (! empty($data['parent_id']) && ! $area->indicators()->whereKey($data['parent_id'])->whereNull('parent_id')->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Induk harus berupa indikator (bukan subindikator) pada area yang sama.']);
        }

        if ($indicator && ! empty($data['parent_id']) && (int) $data['parent_id'] === $indicator->id) {
            throw ValidationException::withMessages(['parent_id' => 'Indikator tidak dapat menjadi induk dirinya sendiri.']);
        }

        $indicator ??= new Indicator;
        $indicator->area_id = $area->id;
        $isNew = ! $indicator->exists;
        $indicator->fill($data)->save();

        $this->audit->record($isNew ? 'indicator.created' : 'indicator.updated', ($isNew ? 'Menambah' : 'Mengubah')." indikator \"{$indicator->label()}\"", $indicator, properties: $isNew ? $data : $this->audit->changesOf($indicator));

        return $indicator;
    }

    public function deleteIndicator(Indicator $indicator): void
    {
        $this->audit->record('indicator.deleted', "Menghapus indikator \"{$indicator->label()}\"", $indicator);
        $indicator->delete();
    }

    private function copyContent(GuidelineVersion $from, GuidelineVersion $to): void
    {
        foreach ($from->areas()->with('indicators')->get() as $area) {
            $newArea = $to->areas()->create($area->only(['code', 'name', 'description', 'sort_order', 'is_active']));
            $idMap = [];

            // Parents first so sub-indicators can point to the copied parent.
            foreach ($area->indicators->sortBy(fn (Indicator $indicator) => $indicator->parent_id !== null) as $indicator) {
                $copy = $newArea->indicators()->create([
                    ...$indicator->only(['code', 'name', 'description', 'evidence_guidance', 'weight', 'sort_order', 'is_active']),
                    'parent_id' => $indicator->parent_id ? ($idMap[$indicator->parent_id] ?? null) : null,
                ]);
                $idMap[$indicator->id] = $copy->id;
            }
        }
    }
}
