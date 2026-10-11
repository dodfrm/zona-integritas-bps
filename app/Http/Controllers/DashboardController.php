<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\EvidenceReview;
use App\Services\DashboardService;
use App\Services\VerificationService;
use App\Support\PeriodContext;
use App\Support\Present;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One dashboard whose sections depend on the user's permissions (not role names) and scope.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, PeriodContext $context, DashboardService $dashboard, VerificationService $verification): Response
    {
        $user = $request->user();
        $period = $context->selected;
        $canMonitor = $user->can(Permission::ProgramsView->value) && $user->can(Permission::ActionPlansView->value);
        $canVerify = $user->can('verify');

        return Inertia::render('dashboard', [
            'periods' => $context->options(),
            'selectedPeriod' => $period ? [
                'id' => $period->id,
                'name' => $period->name,
                'status' => Present::enum($period->status),
                'organization' => $period->organization->name,
            ] : null,
            'summary' => $canMonitor && $period ? $dashboard->summary($period) : null,
            'areaProgress' => $canMonitor && $period ? $dashboard->areaProgress($period) : null,
            'attention' => $canMonitor && $period ? $dashboard->attention($period) : null,
            'myWork' => $dashboard->myWork($user),
            'verificationQueue' => $canVerify ? [
                'count' => $verification->queueFor($user)->count(),
                'items' => $verification->queueFor($user)
                    ->with(['evidence:id,title,organization_id', 'evidence.organization:id,name', 'evidenceVersion:id,version_number'])
                    ->orderBy('created_at')
                    ->limit(6)
                    ->get()
                    ->map(fn (EvidenceReview $review) => [
                        'id' => $review->id,
                        'evidence_id' => $review->evidence_id,
                        'title' => $review->evidence->title,
                        'organization' => $review->evidence->organization->name,
                        'stage_name' => $review->stage_name,
                        'version_number' => $review->evidenceVersion->version_number,
                        'status' => Present::enum($review->status),
                        'created_at' => Present::datetime($review->created_at),
                    ])->values(),
            ] : null,
        ]);
    }
}
