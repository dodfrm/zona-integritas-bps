<?php

namespace App\Services;

use App\Enums\AccessScope;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilderContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolves which organizations' data a user may access.
 *
 * Roles say WHAT a user may do; this scope says WHERE. Both are always checked.
 */
class OrganizationScope
{
    /** @var array<int, list<int>|null> */
    private array $cache = [];

    /**
     * Accessible organization IDs, or null when the user has national scope.
     *
     * @return list<int>|null
     */
    public function organizationIds(User $user): ?array
    {
        if (array_key_exists($user->id, $this->cache)) {
            return $this->cache[$user->id];
        }

        if (! $user->is_active) {
            return $this->cache[$user->id] = [];
        }

        if ($user->access_scope === AccessScope::National) {
            return $this->cache[$user->id] = null;
        }

        $ids = [];

        if ($user->organization) {
            $ids = $user->access_scope === AccessScope::Descendants
                ? $user->organization->selfAndDescendantIds()
                : [$user->organization->id];
        }

        foreach ($user->extraOrganizations as $organization) {
            $ids = [...$ids, ...($organization->getRelation('pivot')->getAttribute('include_descendants')
                ? $organization->selfAndDescendantIds()
                : [$organization->id])];
        }

        return $this->cache[$user->id] = array_values(array_unique($ids));
    }

    public function isNational(User $user): bool
    {
        return $user->is_active && $user->access_scope === AccessScope::National;
    }

    public function canAccess(User $user, ?int $organizationId): bool
    {
        if ($organizationId === null) {
            return false;
        }

        $ids = $this->organizationIds($user);

        return $ids === null || in_array($organizationId, $ids, true);
    }

    /**
     * Restrict a query to the user's accessible organizations.
     *
     * @template TBuilder of QueryBuilderContract
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function apply(QueryBuilderContract $query, User $user, string $column = 'organization_id'): QueryBuilderContract
    {
        $ids = $this->organizationIds($user);

        if ($ids !== null) {
            $query->whereIn($column, $ids);
        }

        return $query;
    }

    /**
     * Accessible organizations for select inputs and filters.
     *
     * @return Collection<int, Organization>
     */
    public function organizations(User $user, bool $activeOnly = true): Collection
    {
        return $this->apply(Organization::query(), $user, 'id')
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name', 'type', 'is_active']);
    }

    public function forget(User $user): void
    {
        unset($this->cache[$user->id]);
    }
}
