<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\GuidelineVersion;
use App\Models\User;
use App\Policies\Concerns\ScopedPolicy;
use App\Services\OrganizationScope;

/**
 * Guideline/area/indicator master data is shared by all organizations, so changing it
 * requires both the permission and national scope.
 */
class GuidelineVersionPolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::MasterView) || $this->allows($user, Permission::MasterManage);
    }

    public function view(User $user, GuidelineVersion $version): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::MasterManage) && app(OrganizationScope::class)->isNational($user);
    }

    /**
     * Edit content (areas & indicators): drafts only.
     */
    public function update(User $user, GuidelineVersion $version): bool
    {
        return $version->isEditable() && $this->create($user);
    }

    /**
     * Publish / retire / copy.
     */
    public function manage(User $user, GuidelineVersion $version): bool
    {
        return $this->create($user);
    }
}
