<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ScopedPolicy;
use App\Services\OrganizationScope;

class OrganizationPolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::OrganizationsView) || $this->allows($user, Permission::OrganizationsManage);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->viewAny($user) && $this->inScope($user, $organization->id);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::OrganizationsManage);
    }

    /**
     * Creating a root organization (no parent) needs national scope.
     */
    public function createUnder(User $user, ?Organization $parent): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        return $parent ? $this->inScope($user, $parent->id) : app(OrganizationScope::class)->isNational($user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->allowsIn($user, Permission::OrganizationsManage, $organization->id);
    }
}
