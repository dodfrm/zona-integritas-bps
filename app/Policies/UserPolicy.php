<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Policies\Concerns\ScopedPolicy;

class UserPolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::UsersView) || $this->allows($user, Permission::UsersManage);
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user) && $this->inScope($user, $model->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::UsersManage);
    }

    public function update(User $user, User $model): bool
    {
        return $this->allowsIn($user, Permission::UsersManage, $model->organization_id);
    }

    public function manageRoles(User $user): bool
    {
        return $this->allows($user, Permission::RolesManage);
    }
}
