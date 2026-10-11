<?php

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\User;
use App\Services\OrganizationScope;

/**
 * Every data-level check combines a permission with the user's organization scope.
 */
trait ScopedPolicy
{
    protected function allows(User $user, Permission $permission): bool
    {
        return $user->is_active && $user->can($permission->value);
    }

    protected function allowsIn(User $user, Permission $permission, ?int $organizationId): bool
    {
        return $this->allows($user, $permission) && $this->inScope($user, $organizationId);
    }

    protected function inScope(User $user, ?int $organizationId): bool
    {
        return app(OrganizationScope::class)->canAccess($user, $organizationId);
    }
}
