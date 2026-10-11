<?php

namespace App\Http\Controllers;

use App\Enums\AccessScope;
use App\Enums\Role as RoleEnum;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationScope;
use App\Services\UserManagementService;
use App\Support\Present;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly UserManagementService $users,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);
        $actor = $request->user();

        $users = $this->scope->apply(User::query(), $actor)
            ->with(['organization:id,name,code,type', 'roles:id,name'])
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->integer('organization_id'), fn ($query, $id) => $query->where('organization_id', $id))
            ->when($request->string('role')->value(), fn ($query, $role) => $query->whereHas('roles', fn ($query) => $query->where('name', $role)))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'organization' => Present::organization($user->organization),
                'roles' => $user->getRoleNames()->map(fn (string $name) => RoleEnum::labelFor($name))->values(),
                'access_scope' => Present::enum($user->access_scope),
                'is_active' => $user->is_active,
                'can_update' => $actor->can('update', $user),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'organization_id', 'role', 'status']),
            'organizationOptions' => $this->organizationOptions($actor),
            'roleOptions' => $this->roleOptions(),
            'canCreate' => $actor->can('create', User::class),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('users/form', $this->formProps($request));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $this->users->create($request->user(), $request->userData());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil dibuat.']);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);
        $user->load(['roles:id,name', 'extraOrganizations:id']);

        return Inertia::render('users/form', [
            ...$this->formProps($request),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'organization_id' => $user->organization_id,
                'access_scope' => $user->access_scope->value,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->values(),
                'extra_organizations' => $user->extraOrganizations->map(fn (Organization $organization) => [
                    'organization_id' => $organization->id,
                    'include_descendants' => (bool) $organization->getRelation('pivot')->getAttribute('include_descendants'),
                ])->values(),
                'is_self' => $request->user()->is($user),
            ],
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->users->update($request->user(), $user, $request->userData());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);

        return to_route('users.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request): array
    {
        $actor = $request->user();

        return [
            'organizationOptions' => $this->organizationOptions($actor),
            'roleOptions' => array_values(array_filter(
                $this->roleOptions(),
                fn (array $role) => $role['value'] !== RoleEnum::SuperAdmin->value || $actor->hasRole(RoleEnum::SuperAdmin->value),
            )),
            'scopeOptions' => array_values(array_filter(
                AccessScope::options(),
                fn (array $option) => $option['value'] !== AccessScope::National->value || $this->scope->isNational($actor),
            )),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function organizationOptions(User $actor): array
    {
        return array_values($this->scope->organizations($actor)
            ->map(fn (Organization $organization) => ['value' => (string) $organization->id, 'label' => $organization->name])
            ->all());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_values(Role::query()->where('guard_name', 'web')->orderBy('id')->pluck('name')
            ->map(fn (string $name) => ['value' => $name, 'label' => RoleEnum::labelFor($name)])
            ->all());
    }
}
