<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Organization|null $organization */
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique(Organization::class)->ignore($organization?->id)],
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Organization::class, 'id'),
                // Prevent self-parenting and cycles (new parent may not be a descendant).
                function (string $attribute, mixed $value, Closure $fail) use ($organization) {
                    if ($organization && $value && in_array((int) $value, $organization->selfAndDescendantIds(), true)) {
                        $fail('Organisasi induk tidak boleh dirinya sendiri atau salah satu turunannya.');
                    }
                },
            ],
            'is_active' => ['boolean'],
        ];
    }
}
