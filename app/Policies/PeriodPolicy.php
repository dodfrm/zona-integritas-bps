<?php

namespace App\Policies;

use App\Enums\PeriodStatus;
use App\Enums\Permission;
use App\Models\Period;
use App\Models\User;
use App\Policies\Concerns\ScopedPolicy;

class PeriodPolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::PeriodsView) || $this->allows($user, Permission::PeriodsManage);
    }

    public function view(User $user, Period $period): bool
    {
        return $this->viewAny($user) && $this->inScope($user, $period->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::PeriodsManage);
    }

    public function update(User $user, Period $period): bool
    {
        return $period->status !== PeriodStatus::Archived
            && $this->allowsIn($user, Permission::PeriodsManage, $period->organization_id);
    }
}
