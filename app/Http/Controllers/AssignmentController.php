<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use App\Models\ActionPlan;
use App\Models\Assignment;
use App\Models\Evidence;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target_type' => ['required', Rule::in(['work_program', 'action_plan', 'evidence'])],
            'target_id' => ['required', 'integer'],
            'type' => ['required', Rule::in([AssignmentType::Implementer->value, AssignmentType::EvidenceCollection->value])],
            'assignee_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['assignee_id' => 'pengguna', 'type' => 'jenis penugasan', 'due_date' => 'tenggat']);

        $target = match ($data['target_type']) {
            'work_program' => WorkProgram::query()->findOrFail((int) $data['target_id']),
            'action_plan' => ActionPlan::query()->findOrFail((int) $data['target_id']),
            default => Evidence::query()->findOrFail((int) $data['target_id']),
        };

        Gate::authorize('assign', $target);

        $this->assignments->assign(
            $target,
            User::query()->findOrFail((int) $data['assignee_id']),
            AssignmentType::from($data['type']),
            $data['due_date'] ?? null,
            $data['notes'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penugasan berhasil dibuat.']);

        return back();
    }

    /**
     * The assignee may mark it done; managers in scope may complete or cancel it.
     */
    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([AssignmentStatus::Completed->value, AssignmentStatus::Cancelled->value])],
        ]);

        $status = AssignmentStatus::from($data['status']);
        $target = $assignment->workProgram ?? $assignment->actionPlan ?? $assignment->evidence;
        $isAssignee = $assignment->assignee_id === $request->user()->id && $status === AssignmentStatus::Completed;

        if ($assignment->status !== AssignmentStatus::Active) {
            throw ValidationException::withMessages(['status' => 'Penugasan sudah tidak aktif.']);
        }

        abort_unless($target && ($isAssignee || $request->user()->can('assign', $target)), 403);

        $this->assignments->changeStatus($assignment, $status);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Penugasan ditandai {$status->label()}."]);

        return back();
    }
}
