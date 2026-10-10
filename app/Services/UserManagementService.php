<?php

namespace App\Services;

use App\Enums\AccessScope;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type UserData array{name: string, email: string, password: string|null, organization_id: int, access_scope: string, is_active: bool, roles: list<string>, extra_organizations: list<array{organization_id: int, include_descendants: bool}>}
 */
class UserManagementService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OrganizationScope $scope,
    ) {}

    /**
     * @param  UserData  $data
     */
    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => (string) $data['password']]);
            // Accounts are created by an administrator who vouches for the address.
            $user->email_verified_at = now();
            $this->applyAccess($actor, $user, $data);

            $this->audit->record('user.created', "Membuat pengguna {$user->name} ({$user->email})", $user, $user->organization_id, [
                'roles' => $data['roles'],
                'access_scope' => $data['access_scope'],
                'extra_organizations' => $data['extra_organizations'],
            ]);

            return $user;
        });
    }

    /**
     * @param  UserData  $data
     */
    public function update(User $actor, User $user, array $data): void
    {
        DB::transaction(function () use ($actor, $user, $data) {
            $oldRoles = $user->getRoleNames()->all();
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = now();
            }

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $this->applyAccess($actor, $user, $data);
            $changes = $this->audit->changesOf($user);
            unset($changes['password']);

            $this->audit->record('user.updated', "Mengubah pengguna {$user->name}", $user, $user->organization_id, [
                ...$changes,
                'password_reset' => ! empty($data['password']),
                'roles' => ['old' => $oldRoles, 'new' => $data['roles']],
                'extra_organizations' => $data['extra_organizations'],
            ]);
        });

        $this->scope->forget($user);
    }

    /**
     * @param  UserData  $data
     */
    private function applyAccess(User $actor, User $user, array $data): void
    {
        $isSelf = $user->exists && $actor->is($user);
        $scope = AccessScope::from($data['access_scope']);

        // Nobody can hand out more reach than they have themselves.
        if ($scope === AccessScope::National && ! $this->scope->isNational($actor)) {
            throw ValidationException::withMessages(['access_scope' => 'Hanya pengguna bercakupan nasional yang dapat memberikan cakupan nasional.']);
        }

        foreach ([$data['organization_id'], ...array_column($data['extra_organizations'], 'organization_id')] as $organizationId) {
            if (! $this->scope->canAccess($actor, $organizationId)) {
                throw ValidationException::withMessages(['organization_id' => 'Organisasi berada di luar cakupan Anda.']);
            }
        }

        if (in_array(Role::SuperAdmin->value, $data['roles'], true) && ! $actor->hasRole(Role::SuperAdmin->value)) {
            throw ValidationException::withMessages(['roles' => 'Hanya Super Admin yang dapat memberikan role Super Admin.']);
        }

        if ($isSelf && (! $data['is_active'] || ($actor->hasRole(Role::SuperAdmin->value) && ! in_array(Role::SuperAdmin->value, $data['roles'], true)))) {
            throw ValidationException::withMessages(['roles' => 'Anda tidak dapat menonaktifkan akun sendiri atau mencabut role Super Admin milik sendiri.']);
        }

        $user->organization_id = $data['organization_id'];
        $user->access_scope = $scope;
        $user->is_active = $data['is_active'];
        $user->save();

        $user->syncRoles($data['roles']);
        $extra = [];

        foreach ($data['extra_organizations'] as $row) {
            $extra[$row['organization_id']] = ['include_descendants' => $row['include_descendants'], 'granted_by' => $actor->id];
        }

        $user->extraOrganizations()->sync($extra);
    }
}
