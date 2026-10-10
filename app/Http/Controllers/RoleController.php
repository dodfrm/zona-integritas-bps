<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        Gate::authorize('manageRoles', User::class);

        return Inertia::render('roles/index', [
            'roles' => Role::query()->where('guard_name', 'web')->with('permissions:id,name')->withCount('users')->orderBy('id')->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => RoleEnum::labelFor($role->name),
                    'is_system' => RoleEnum::tryFrom($role->name) !== null,
                    'is_locked' => $role->name === RoleEnum::SuperAdmin->value,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name')->values(),
                ]),
            'permissions' => collect(Permission::cases())->map(fn (Permission $permission) => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'group' => $permission->group(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manageRoles', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(Role::class)->where('guard_name', 'web')],
        ], ['name.regex' => 'Gunakan huruf kecil, angka, dan garis bawah, diawali huruf (contoh: verifikator_area).']);

        $role = Role::query()->create(['name' => $data['name'], 'guard_name' => 'web']);
        $this->audit->record('role.created', "Membuat role {$role->name}", $role);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role berhasil dibuat. Atur permission-nya.']);

        return to_route('roles.index');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('manageRoles', User::class);

        if ($role->name === RoleEnum::SuperAdmin->value) {
            throw ValidationException::withMessages(['permissions' => 'Permission Super Admin tidak dapat diubah.']);
        }

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(array_column(Permission::cases(), 'value'))],
        ]);

        DB::transaction(function () use ($role, $data) {
            $old = $role->permissions()->pluck('name')->all();
            $role->syncPermissions($data['permissions'] ?? []);
            $new = $data['permissions'] ?? [];

            $this->audit->record('role.permissions_updated', "Mengubah permission role {$role->name}", $role, properties: [
                'added' => array_values(array_diff($new, $old)),
                'removed' => array_values(array_diff($old, $new)),
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Permission role diperbarui.']);

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('manageRoles', User::class);

        if (RoleEnum::tryFrom($role->name) || $role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Role bawaan atau role yang masih dipakai pengguna tidak dapat dihapus.']);
        }

        $this->audit->record('role.deleted', "Menghapus role {$role->name}", $role);
        $role->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role dihapus.']);

        return to_route('roles.index');
    }
}
