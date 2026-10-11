<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Services\DashboardService;
use App\Services\OrganizationScope;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Tugas saya": everything assigned to or awaiting the current user.
 */
class TaskController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard, OrganizationScope $scope, VerificationService $verification): Response
    {
        $user = $request->user();
        $status = $request->string('status')->value() ?: AssignmentStatus::Active->value;

        $assignments = $scope->apply(Assignment::query(), $user)
            ->where('assignee_id', $user->id)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['workProgram:id,name', 'actionPlan:id,title', 'evidence:id,title', 'assigner:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Assignment $assignment) => DashboardService::presentAssignment($assignment));

        return Inertia::render('tasks/index', [
            'assignments' => $assignments,
            'status' => $status,
            'statusOptions' => [...AssignmentStatus::options(), ['value' => 'all', 'label' => 'Semua']],
            'myWork' => $dashboard->myWork($user, 20),
            'verificationCount' => $user->can('verify') ? $verification->queueFor($user)->count() : null,
        ]);
    }
}
