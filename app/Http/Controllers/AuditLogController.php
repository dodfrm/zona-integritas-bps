<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\OrganizationScope;
use App\Support\Present;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only. There are deliberately no routes to edit or delete audit records.
 */
class AuditLogController extends Controller
{
    public function index(Request $request, OrganizationScope $scope): Response
    {
        Gate::authorize('viewAuditLogs');
        $user = $request->user();

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AuditLog::query()
            // Global (organization-less) events such as guideline or role changes are visible to national scope only.
            ->when(! $scope->isNational($user), fn ($query) => $query->whereIn('organization_id', $scope->organizationIds($user) ?? []))
            ->with(['user:id,name', 'organization:id,name,code,type'])
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where('description', 'like', "%{$search}%"))
            ->when($request->string('module')->value(), fn ($query, $module) => $query->where('event', 'like', "{$module}.%"))
            ->when($request->date('from'), fn ($query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($query, $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'user' => Present::user($log->user),
                'organization' => $log->organization?->name,
                'subject' => $log->auditable_type ? "{$log->auditable_type} #{$log->auditable_id}" : null,
                'properties' => $log->properties,
                'ip_address' => $log->ip_address,
                'created_at' => Present::datetime($log->created_at),
            ]);

        return Inertia::render('audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'module', 'from', 'to']),
            'moduleOptions' => collect([
                'user' => 'Pengguna', 'organization' => 'Organisasi', 'role' => 'Role & permission', 'period' => 'Periode',
                'guideline' => 'Pedoman', 'area' => 'Area', 'indicator' => 'Indikator', 'program' => 'Program kerja',
                'action_plan' => 'Rencana aksi', 'assignment' => 'Penugasan', 'evidence' => 'Bukti dukung & verifikasi',
                'verification_stage' => 'Alur verifikasi', 'report' => 'Ekspor laporan',
            ])->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])->values(),
        ]);
    }
}
