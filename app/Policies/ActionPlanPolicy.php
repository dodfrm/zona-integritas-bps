<?php

namespace App\Policies;

use App\Enums\AssignmentStatus;
use App\Enums\Permission;
use App\Models\ActionPlan;
use App\Models\User;
use App\Models\WorkProgram;
use App\Policies\Concerns\ScopedPolicy;

class ActionPlanPolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::ActionPlansView);
    }

    public function view(User $user, ActionPlan $plan): bool
    {
        return $this->allowsIn($user, Permission::ActionPlansView, $plan->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::ActionPlansCreate);
    }

    public function createForProgram(User $user, WorkProgram $program): bool
    {
        return $program->archived_at === null
            && $program->period->isEditable()
            && $this->allowsIn($user, Permission::ActionPlansCreate, $program->organization_id);
    }

    public function update(User $user, ActionPlan $plan): bool
    {
        return $this->isOpen($plan) && $this->allowsIn($user, Permission::ActionPlansUpdate, $plan->organization_id);
    }

    /**
     * Status/progress/obstacle updates: full editors, or implementers who are PIC or actively assigned.
     */
    public function updateProgress(User $user, ActionPlan $plan): bool
    {
        if ($this->update($user, $plan)) {
            return true;
        }

        if (! $this->isOpen($plan) || ! $this->allowsIn($user, Permission::ActionPlansUpdateProgress, $plan->organization_id)) {
            return false;
        }

        return $plan->pic_user_id === $user->id
            || $plan->assignments()
                ->where('assignee_id', $user->id)
                ->where('status', AssignmentStatus::Active->value)
                ->exists();
    }

    public function archive(User $user, ActionPlan $plan): bool
    {
        return $this->isOpen($plan) && $this->allowsIn($user, Permission::ActionPlansArchive, $plan->organization_id);
    }

    public function assign(User $user, ActionPlan $plan): bool
    {
        return $this->isOpen($plan) && $this->allowsIn($user, Permission::AssignmentsManage, $plan->organization_id);
    }

    private function isOpen(ActionPlan $plan): bool
    {
        return $plan->archived_at === null && $plan->period->isEditable();
    }
}
