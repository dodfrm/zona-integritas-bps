<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\VerificationStatus;
use App\Models\Evidence;
use App\Models\Period;
use App\Models\User;
use App\Policies\Concerns\ScopedPolicy;

class EvidencePolicy
{
    use ScopedPolicy;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::EvidenceView);
    }

    /**
     * Also covers downloading: knowing an ID is never enough.
     */
    public function view(User $user, Evidence $evidence): bool
    {
        return $this->allowsIn($user, Permission::EvidenceView, $evidence->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::EvidenceUpload);
    }

    public function createInPeriod(User $user, Period $period): bool
    {
        return $period->isEditable() && $this->allowsIn($user, Permission::EvidenceUpload, $period->organization_id);
    }

    /**
     * Edit metadata: the uploader (with upload permission) or anyone with evidence.update, in scope.
     */
    public function update(User $user, Evidence $evidence): bool
    {
        if ($evidence->archived_at !== null || ! $evidence->period->isEditable()) {
            return false;
        }

        if ($this->allowsIn($user, Permission::EvidenceUpdate, $evidence->organization_id)) {
            return true;
        }

        return $evidence->uploaded_by === $user->id
            && $this->allowsIn($user, Permission::EvidenceUpload, $evidence->organization_id);
    }

    /**
     * Upload a replacement file. Not allowed while a verifier is actively reviewing it.
     */
    public function addVersion(User $user, Evidence $evidence): bool
    {
        return $evidence->verification_status !== VerificationStatus::InReview && $this->update($user, $evidence);
    }

    public function archive(User $user, Evidence $evidence): bool
    {
        return $evidence->archived_at === null
            && $evidence->period->isEditable()
            && $this->allowsIn($user, Permission::EvidenceArchive, $evidence->organization_id);
    }

    public function assign(User $user, Evidence $evidence): bool
    {
        return $evidence->archived_at === null
            && $evidence->period->isEditable()
            && $this->allowsIn($user, Permission::AssignmentsManage, $evidence->organization_id);
    }
}
