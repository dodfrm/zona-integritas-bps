<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccessScope;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::defaults(), 'confirmed'],
            'organization_id' => ['required', 'integer', Rule::exists(Organization::class, 'id')],
            'access_scope' => ['required', Rule::enum(AccessScope::class)],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::exists(Role::class, 'name')->where('guard_name', 'web')],
            'extra_organizations' => ['array'],
            'extra_organizations.*.organization_id' => ['required', 'integer', 'distinct', Rule::exists(Organization::class, 'id')],
            'extra_organizations.*.include_descendants' => ['boolean'],
        ];
    }

    /**
     * Validated input normalized to the shape the user management service expects.
     *
     * @return array{name: string, email: string, password: string|null, organization_id: int, access_scope: string, is_active: bool, roles: list<string>, extra_organizations: list<array{organization_id: int, include_descendants: bool}>}
     */
    public function userData(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return [
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => isset($data['password']) ? (string) $data['password'] : null,
            'organization_id' => (int) $data['organization_id'],
            'access_scope' => (string) $data['access_scope'],
            'is_active' => $this->boolean('is_active'),
            'roles' => array_values(array_map(strval(...), (array) $data['roles'])),
            'extra_organizations' => array_values(array_map(fn (mixed $row) => [
                'organization_id' => (int) data_get($row, 'organization_id'),
                'include_descendants' => filter_var(data_get($row, 'include_descendants', false), FILTER_VALIDATE_BOOL),
            ], (array) ($data['extra_organizations'] ?? []))),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organization_id' => 'organisasi',
            'access_scope' => 'cakupan akses',
            'roles' => 'role',
            'extra_organizations.*.organization_id' => 'organisasi tambahan',
        ];
    }
}
