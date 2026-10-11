<?php

namespace App\Support;

use App\Enums\PeriodStatus;
use App\Models\Period;
use App\Models\User;
use App\Services\OrganizationScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Resolves the period a list/dashboard/report is filtered by, limited to the user's scope.
 */
final class PeriodContext
{
    /** @var Collection<int, Period> */
    public readonly Collection $periods;

    public readonly ?Period $selected;

    public function __construct(Request $request, OrganizationScope $scope)
    {
        /** @var User $user */
        $user = $request->user();

        $this->periods = $scope->apply(Period::query(), $user)
            ->with('organization:id,name,code,type')
            ->orderByRaw('CASE WHEN organization_id = ? THEN 0 ELSE 1 END', [$user->organization_id ?? 0])
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();

        $requested = $request->integer('period_id');

        $this->selected = $this->periods->firstWhere('id', $requested)
            ?? $this->periods->first(fn (Period $period) => $period->status === PeriodStatus::Active)
            ?? $this->periods->first();
    }

    /**
     * @return list<array{id: int, name: string, status: string, status_label: string, organization: string, year: int}>
     */
    public function options(): array
    {
        return array_values($this->periods->map(fn (Period $period) => [
            'id' => $period->id,
            'name' => $period->name,
            'year' => $period->year,
            'status' => $period->status->value,
            'status_label' => $period->status->label(),
            'organization' => $period->organization->name,
        ])->all());
    }
}
