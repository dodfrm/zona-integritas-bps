<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationType;
use App\Http\Requests\Admin\OrganizationRequest;
use App\Models\Organization;
use App\Services\AuditLogger;
use App\Services\OrganizationScope;
use App\Support\Present;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationScope $scope,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Organization::class);
        $user = $request->user();

        $organizations = $this->scope->apply(Organization::query(), $user, 'id')
            ->with('parent:id,name')
            ->withCount('users')
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->when($request->string('type')->value(), fn ($query, $type) => $query->where('type', $type))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderByRaw("CASE type WHEN 'pusat' THEN 0 WHEN 'provinsi' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Organization $organization) => [
                ...Present::organizationFields($organization),
                'parent' => $organization->parent?->name,
                'is_active' => $organization->is_active,
                'users_count' => $organization->users_count,
                'can_update' => $user->can('update', $organization),
            ]);

        return Inertia::render('organizations/index', [
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'type', 'status']),
            'typeOptions' => OrganizationType::options(),
            'canCreate' => $user->can('create', Organization::class),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Organization::class);

        return Inertia::render('organizations/form', $this->formProps($request));
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        $parent = $request->filled('parent_id') ? Organization::query()->findOrFail($request->integer('parent_id')) : null;
        Gate::authorize('createUnder', [Organization::class, $parent]);

        $organization = Organization::create([
            ...$request->safe()->only(['name', 'code', 'type', 'parent_id']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->audit->record('organization.created', "Membuat organisasi {$organization->name}", $organization, $organization->id, $organization->only(['name', 'code', 'type', 'parent_id']));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisasi berhasil dibuat.']);

        return to_route('organizations.index');
    }

    public function edit(Request $request, Organization $organization): Response
    {
        Gate::authorize('update', $organization);

        return Inertia::render('organizations/form', [
            ...$this->formProps($request, $organization),
            'organization' => [
                ...Present::organizationFields($organization),
                'parent_id' => $organization->parent_id,
                'is_active' => $organization->is_active,
            ],
        ]);
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $parentId = $request->filled('parent_id') ? $request->integer('parent_id') : null;

        if ($parentId !== $organization->parent_id) {
            Gate::authorize('createUnder', [Organization::class, $parentId ? Organization::query()->findOrFail($parentId) : null]);
        }

        $organization->fill([
            ...$request->safe()->only(['name', 'code', 'type']),
            'parent_id' => $parentId,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        if ($changes = $this->audit->changesOf($organization)) {
            $this->audit->record('organization.updated', "Mengubah organisasi {$organization->name}", $organization, $organization->id, $changes);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisasi berhasil diperbarui.']);

        return to_route('organizations.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Organization $organization = null): array
    {
        $excluded = $organization ? $organization->selfAndDescendantIds() : [];

        return [
            'typeOptions' => OrganizationType::options(),
            'parentOptions' => $this->scope->organizations($request->user())
                ->reject(fn (Organization $candidate) => in_array($candidate->id, $excluded, true))
                ->map(fn (Organization $candidate) => ['value' => (string) $candidate->id, 'label' => "{$candidate->name} ({$candidate->type->label()})"])
                ->values(),
            'canCreateRoot' => $this->scope->isNational($request->user()),
        ];
    }
}
