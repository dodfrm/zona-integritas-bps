<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Period;
use App\Models\User;
use App\Models\WorkProgram;
use App\Policies\Concerns\ScopedPolicy;

class WorkProgramPolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::ProgramsView);
    }

    public function view(User $user, WorkProgram $program): bool
    {
        return $this->allowsIn($user, Permission::ProgramsView, $program->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::ProgramsCreate);
    }

    public function createInPeriod(User $user, Period $period): bool
    {
        return $period->isEditable() && $this->allowsIn($user, Permission::ProgramsCreate, $period->organization_id);
    }

    public function update(User $user, WorkProgram $program): bool
    {
        return $this->isOpen($program) && $this->allowsIn($user, Permission::ProgramsUpdate, $program->organization_id);
    }

    public function archive(User $user, WorkProgram $program): bool
    {
        return $this->isOpen($program) && $this->allowsIn($user, Permission::ProgramsArchive, $program->organization_id);
    }

    public function assign(User $user, WorkProgram $program): bool
    {
        return $this->isOpen($program) && $this->allowsIn($user, Permission::AssignmentsManage, $program->organization_id);
    }

    private function isOpen(WorkProgram $program): bool
    {
        return $program->archived_at === null && $program->period->isEditable();
    }
}
