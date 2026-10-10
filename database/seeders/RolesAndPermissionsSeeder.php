<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: creates missing permissions/roles. Existing roles keep any permission changes made
 * through the role management page; only super_admin is always re-synced to every permission.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Role::cases() as $role) {
            $model = RoleModel::query()->where('name', $role->value)->where('guard_name', 'web')->first();

            if (! $model) {
                RoleModel::create(['name' => $role->value, 'guard_name' => 'web'])
                    ->syncPermissions(array_map(fn (Permission $permission) => $permission->value, $role->defaultPermissions()));
            } elseif ($role === Role::SuperAdmin) {
                $model->syncPermissions(array_map(fn (Permission $permission) => $permission->value, Permission::cases()));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
