<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\ProgramStatus;
use App\Enums\ReviewStatus;
use App\Enums\VerificationStatus;
use App\Models\ActionPlan;
use App\Models\Evidence;
use App\Models\EvidenceReview;
use App\Models\Period;
use App\Models\WorkProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Report definitions shared by the report page and the CSV export, so both always use
 * identical filters. Callers must have authorized the period (which fixes the organization).
 */
class ReportService
{
    public const REPORTS = [
        'programs' => 'Rekap program kerja',
        'action_plans' => 'Rekap rencana aksi',
        'area_progress' => 'Rekap progres per area',
        'evidence' => 'Rekap bukti dukung per status',
        'revisions' => 'Bukti dukung perlu revisi',
        'overdue' => 'Pekerjaan terlambat',
        'verification_history' => 'Riwayat verifikasi',
    ];

    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * @return array<string, string>
     */
    public function columns(string $report): array
    {
        return match ($report) {
            'programs' => ['code' => 'Kode', 'name' => 'Program kerja', 'area' => 'Area', 'pic' => 'Penanggung jawab', 'status' => 'Status', 'start_date' => 'Mulai', 'target_date' => 'Target selesai', 'action_plans' => 'Rencana aksi (tanpa batal)', 'completed' => 'Rencana aksi selesai', 'evidence' => 'Bukti dukung', 'archived' => 'Diarsipkan'],
            'action_plans' => ['title' => 'Rencana aksi', 'program' => 'Program kerja', 'area' => 'Area', 'indicator' => 'Indikator', 'pic' => 'Penanggung jawab', 'status' => 'Status pelaksanaan', 'progress' => 'Progres dilaporkan (%)', 'start_date' => 'Mulai', 'due_date' => 'Tenggat', 'overdue' => 'Terlambat', 'obstacles' => 'Hambatan'],
            'area_progress' => ['label' => 'Area', 'programs' => 'Program kerja', 'action_plans_total' => 'Rencana aksi (tanpa batal)', 'action_plans_completed' => 'Rencana aksi selesai', 'completion' => 'Penyelesaian rencana aksi (%)', 'evidence_total' => 'Bukti dukung', 'evidence_accepted' => 'Bukti diterima'],
            'evidence' => ['title' => 'Bukti dukung', 'category' => 'Jenis', 'indicator' => 'Indikator', 'program' => 'Program kerja', 'action_plan' => 'Rencana aksi', 'uploader' => 'Pengunggah', 'version' => 'Versi', 'status' => 'Status verifikasi', 'created_at' => 'Diunggah'],
            'revisions' => ['title' => 'Bukti dukung', 'uploader' => 'Pengunggah', 'status' => 'Status', 'note' => 'Catatan verifikator terakhir', 'decider' => 'Verifikator', 'decided_at' => 'Waktu keputusan'],
            'overdue' => ['title' => 'Rencana aksi', 'program' => 'Program kerja', 'pic' => 'Penanggung jawab', 'status' => 'Status', 'due_date' => 'Tenggat', 'days_late' => 'Hari terlambat', 'obstacles' => 'Hambatan'],
            'verification_history' => ['evidence' => 'Bukti dukung', 'version' => 'Versi', 'stage' => 'Tahap', 'decision' => 'Keputusan', 'decider' => 'Verifikator', 'decided_at' => 'Waktu', 'note' => 'Catatan'],
            default => [],
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function statusOptions(string $report): array
    {
        return match ($report) {
            'programs' => ProgramStatus::options(),
            'action_plans', 'overdue' => ActionPlanStatus::options(),
            'evidence' => VerificationStatus::options(),
            'verification_history' => array_values(array_filter(ReviewStatus::options(), fn (array $option) => in_array($option['value'], ['accepted', 'revision_required', 'rejected'], true))),
            default => [],
        };
    }

    /**
     * Aggregated report rows (not paginated).
     *
     * @return list<array<string, mixed>>
     */
    public function aggregate(string $report, Period $period): array
    {
        return array_map(fn (array $row) => [
            ...$row,
            'completion' => $row['action_plans_total'] > 0 ? round($row['action_plans_completed'] / $row['action_plans_total'] * 100, 1) : null,
        ], $this->dashboard->areaProgress($period));
    }

    /**
     * @param  array{area_id?: int|null, status?: string|null, pic_user_id?: int|null}  $filters
     * @return Builder<covariant Model>|null
     */
    public function query(string $report, Period $period, array $filters): ?Builder
    {
        $area = $filters['area_id'] ?? null;
        $status = $filters['status'] ?? null;
        $pic = $filters['pic_user_id'] ?? null;

        $evidenceArea = fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->whereHas('indicator', fn ($query) => $query->where('area_id', $area))
            ->orWhereHas('workProgram', fn ($query) => $query->where('area_id', $area)));

        return match ($report) {
            'programs' => WorkProgram::query()
                ->where('period_id', $period->id)
                ->with(['area:id,code,name', 'pic:id,name'])
                ->withCount([
                    'actionPlans as action_plans_count' => fn ($query) => $query->notArchived()->where('status', '!=', ActionPlanStatus::Cancelled->value),
                    'actionPlans as completed_count' => fn ($query) => $query->notArchived()->where('status', ActionPlanStatus::Completed->value),
                    'evidence as evidence_count' => fn ($query) => $query->notArchived(),
                ])
                ->when($area, fn ($query) => $query->where('area_id', $area))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($pic, fn ($query) => $query->where('pic_user_id', $pic))
                ->orderBy('code')->orderBy('name'),
            'action_plans', 'overdue' => ActionPlan::query()
                ->where('period_id', $period->id)
                ->notArchived()
                ->with(['workProgram:id,name,area_id', 'workProgram.area:id,code,name', 'indicator:id,code,name', 'pic:id,name'])
                ->when($report === 'overdue', fn ($query) => $query->overdue())
                ->when($area, fn ($query) => $query->whereHas('workProgram', fn ($query) => $query->where('area_id', $area)))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($pic, fn ($query) => $query->where('pic_user_id', $pic))
                ->orderByRaw('due_date IS NULL')->orderBy('due_date'),
            'evidence', 'revisions' => Evidence::query()
                ->where('period_id', $period->id)
                ->notArchived()
                ->with(['indicator:id,code,name', 'workProgram:id,name', 'actionPlan:id,title', 'uploader:id,name', 'latestVersion:id,evidence_id,version_number'])
                ->when($report === 'revisions', fn ($query) => $query
                    ->where('verification_status', VerificationStatus::RevisionRequired->value)
                    ->with(['reviews' => fn ($query) => $query->whereNotNull('decided_at')->latest('decided_at')->with('decider:id,name')]))
                ->when($area, $evidenceArea)
                ->when($status && $report === 'evidence', fn ($query) => $query->where('verification_status', $status))
                ->when($pic, fn ($query) => $query->where('uploaded_by', $pic))
                ->latest(),
            'verification_history' => EvidenceReview::query()
                ->whereNotNull('decided_at')
                ->whereHas('evidence', fn ($query) => $query->where('period_id', $period->id)->when($area, $evidenceArea))
                ->with(['evidence:id,title', 'evidenceVersion:id,version_number', 'decider:id,name'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($pic, fn ($query) => $query->where('decided_by', $pic))
                ->latest('decided_at'),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function map(string $report, Model $row): array
    {
        return match (true) {
            $row instanceof WorkProgram => [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'area' => $row->area?->label(),
                'pic' => $row->pic?->name,
                'status' => $row->status->label(),
                'start_date' => $row->start_date?->toDateString(),
                'target_date' => $row->target_date?->toDateString(),
                'action_plans' => $row->getAttribute('action_plans_count'),
                'completed' => $row->getAttribute('completed_count'),
                'evidence' => $row->getAttribute('evidence_count'),
                'archived' => $row->archived_at ? 'Ya' : 'Tidak',
            ],
            $row instanceof ActionPlan => [
                'id' => $row->id,
                'title' => $row->title,
                'program' => $row->workProgram->name,
                'area' => $row->workProgram->area?->label(),
                'indicator' => $row->indicator?->label(),
                'pic' => $row->pic?->name,
                'status' => $row->status->label(),
                'progress' => $row->progress_percent,
                'start_date' => $row->start_date?->toDateString(),
                'due_date' => $row->due_date?->toDateString(),
                'overdue' => $row->isOverdue() ? 'Ya' : 'Tidak',
                'days_late' => $row->isOverdue() ? (int) $row->due_date?->diffInDays(today()) : null,
                'obstacles' => $row->obstacle_notes,
            ],
            $row instanceof Evidence => [
                'id' => $row->id,
                'title' => $row->title,
                'category' => $row->category->label(),
                'indicator' => $row->indicator?->label(),
                'program' => $row->workProgram?->name,
                'action_plan' => $row->actionPlan?->title,
                'uploader' => $row->uploader->name,
                'version' => $row->latestVersion?->version_number,
                'status' => $row->verification_status->label(),
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'note' => $row->relationLoaded('reviews') ? $row->reviews->first()?->decision_note : null,
                'decider' => $row->relationLoaded('reviews') ? $row->reviews->first()?->decider?->name : null,
                'decided_at' => $row->relationLoaded('reviews') ? $row->reviews->first()?->decided_at?->format('Y-m-d H:i') : null,
            ],
            $row instanceof EvidenceReview => [
                'id' => $row->id,
                'evidence_id' => $row->evidence_id,
                'evidence' => $row->evidence->title,
                'version' => $row->evidenceVersion->version_number,
                'stage' => $row->stage_name,
                'decision' => $row->status->label(),
                'decider' => $row->decider?->name,
                'decided_at' => $row->decided_at?->format('Y-m-d H:i'),
                'note' => $row->decision_note,
            ],
            default => [],
        };
    }
}
