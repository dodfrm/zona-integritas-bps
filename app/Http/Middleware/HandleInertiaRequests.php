<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Permissions are shared only to show/hide UI; every action is re-checked on the server.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                    'organization' => $user->organization?->only(['id', 'name', 'type']),
                    'access_scope' => $user->access_scope->value,
                    'roles' => $user->getRoleNames()->map(fn (string $role) => Role::labelFor($role))->values(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : null,
                'permissions' => $user
                    ? $user->getAllPermissions()->pluck('name')->intersect(array_column(Permission::cases(), 'value'))->values()
                    : [],
            ],
            'unreadNotifications' => fn () => $user?->unreadNotifications()->count() ?? 0,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
