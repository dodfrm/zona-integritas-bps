<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Indicator;
use App\Models\User;

/**
 * Select-option builders shared by several forms and filters.
 */
final class Options
{
    /**
     * Active users of one organization (candidates for PIC / assignments).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function usersOf(int $organizationId): array
    {
        return array_values(User::query()
            ->active()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['value' => (string) $user->id, 'label' => $user->name])
            ->all());
    }

    /**
     * Active areas of a guideline version (filters).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function areas(int $guidelineVersionId): array
    {
        return array_values(Area::query()
            ->where('guideline_version_id', $guidelineVersionId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (Area $area) => ['value' => (string) $area->id, 'label' => $area->label()])
            ->all());
    }

    /**
     * Active areas of a guideline version with their active indicators.
     *
     * @return list<array{value: string, label: string, indicators: list<array{value: string, label: string, is_sub: bool}>}>
     */
    public static function areasWithIndicators(int $guidelineVersionId): array
    {
        return array_values(Area::query()
            ->where('guideline_version_id', $guidelineVersionId)
            ->where('is_active', true)
            ->with(['indicators' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (Area $area) => [
                'value' => (string) $area->id,
                'label' => $area->label(),
                // Parents first, each followed by its sub-indicators.
                'indicators' => array_values($area->indicators
                    ->sortBy(fn (Indicator $indicator) => [$indicator->parent_id ?? $indicator->id, $indicator->parent_id ? 1 : 0, $indicator->sort_order])
                    ->map(fn (Indicator $indicator) => [
                        'value' => (string) $indicator->id,
                        'label' => $indicator->label(),
                        'is_sub' => $indicator->parent_id !== null,
                    ])
                    ->all()),
            ])
            ->all());
    }
}
