<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Small, explicit shapes sent to the frontend (never whole models).
 */
final class Present
{
    /**
     * @return array{id: int, name: string}|null
     */
    public static function user(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name] : null;
    }

    /**
     * @return array{id: int, name: string, code: string|null, type: string, type_label: string}|null
     */
    public static function organization(?Organization $organization): ?array
    {
        return $organization ? self::organizationFields($organization) : null;
    }

    /**
     * @return array{id: int, name: string, code: string|null, type: string, type_label: string}
     */
    public static function organizationFields(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'code' => $organization->code,
            'type' => $organization->type->value,
            'type_label' => $organization->type->label(),
        ];
    }

    /**
     * Enum options with a count, e.g. for status breakdowns.
     *
     * @param  list<BackedEnum>  $cases
     * @param  Collection<array-key, mixed>  $counts  keyed by enum value
     * @return list<array{value: string, label: string, count: int}>
     */
    public static function enumCounts(array $cases, Collection $counts): array
    {
        $rows = [];

        foreach ($cases as $case) {
            $rows[] = [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : (string) $case->value,
                'count' => (int) ($counts[$case->value] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return array{value: string, label: string}|null
     */
    public static function enum(?BackedEnum $enum): ?array
    {
        return $enum && method_exists($enum, 'label')
            ? ['value' => (string) $enum->value, 'label' => $enum->label()]
            : null;
    }

    public static function date(?CarbonInterface $date): ?string
    {
        return $date?->toDateString();
    }

    public static function datetime(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }
}
