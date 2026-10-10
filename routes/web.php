<?php

use App\Http\Controllers\ActionPlanController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\GuidelineController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VerificationStageController;
use App\Http\Controllers\WorkProgramController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

// Every route below requires login; each controller action also checks permission AND organization scope.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('tasks', TaskController::class)->name('tasks.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Administration
    Route::resource('organizations', OrganizationController::class)->except(['show', 'destroy']);
    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Periods & master data
    Route::resource('periods', PeriodController::class)->except(['destroy']);
    Route::post('periods/{period}/status', [PeriodController::class, 'changeStatus'])->name('periods.status');

    Route::resource('guidelines', GuidelineController::class)->except(['edit']);
    Route::post('guidelines/{guideline}/publish', [GuidelineController::class, 'publish'])->name('guidelines.publish');
    Route::post('guidelines/{guideline}/retire', [GuidelineController::class, 'retire'])->name('guidelines.retire');
    Route::post('guidelines/{guideline}/areas', [AreaController::class, 'store'])->name('guidelines.areas.store');
    Route::put('areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::delete('areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
    Route::post('areas/{area}/indicators', [IndicatorController::class, 'store'])->name('areas.indicators.store');
    Route::put('indicators/{indicator}', [IndicatorController::class, 'update'])->name('indicators.update');
    Route::delete('indicators/{indicator}', [IndicatorController::class, 'destroy'])->name('indicators.destroy');

    // Work programs, action plans, assignments
    Route::resource('programs', WorkProgramController::class)->parameters(['programs' => 'program'])->except(['destroy']);
    Route::post('programs/{program}/archive', [WorkProgramController::class, 'archive'])->name('programs.archive');

    Route::resource('action-plans', ActionPlanController::class)->parameters(['action-plans' => 'actionPlan'])->except(['destroy']);
    Route::post('action-plans/{actionPlan}/progress', [ActionPlanController::class, 'progress'])->name('action-plans.progress');
    Route::post('action-plans/{actionPlan}/archive', [ActionPlanController::class, 'archive'])->name('action-plans.archive');

    Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::patch('assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');

    // Evidence & verification
    Route::resource('evidence', EvidenceController::class)->except(['destroy'])->middlewareFor(['store'], 'throttle:uploads');
    Route::post('evidence/{evidence}/versions', [EvidenceController::class, 'storeVersion'])->middleware('throttle:uploads')->name('evidence.versions.store');
    Route::get('evidence/{evidence}/versions/{version}/download', [EvidenceController::class, 'download'])
        ->scopeBindings()
        ->middleware('throttle:downloads')
        ->name('evidence.versions.download');
    Route::post('evidence/{evidence}/archive', [EvidenceController::class, 'archive'])->name('evidence.archive');

    Route::get('verifications', [VerificationController::class, 'index'])->name('verifications.index');
    Route::post('reviews/{review}/start', [VerificationController::class, 'start'])->name('reviews.start');
    Route::post('reviews/{review}/decide', [VerificationController::class, 'decide'])->name('reviews.decide');
    Route::post('reviews/{review}/assign', [VerificationController::class, 'assign'])->name('reviews.assign');

    Route::get('verification-stages', [VerificationStageController::class, 'index'])->name('verification-stages.index');
    Route::post('verification-stages', [VerificationStageController::class, 'store'])->name('verification-stages.store');
    Route::put('verification-stages/{stage}', [VerificationStageController::class, 'update'])->name('verification-stages.update');

    // Reports & audit
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->middleware('throttle:exports')->name('reports.export');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__.'/settings.php';
