<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\ReportService;
use App\Support\Options;
use App\Support\PeriodContext;
use App\Support\Present;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request, PeriodContext $context): Response
    {
        Gate::authorize('viewReports');
        $period = $context->selected;
        $report = $this->reportKey($request);
        $filters = $this->filters($request);

        $data = null;

        if ($period) {
            $query = $this->reports->query($report, $period, $filters);
            $data = $query
                ? $query->paginate(25)->withQueryString()->through(fn (Model $row) => $this->reports->map($report, $row))
                : ['data' => $this->reports->aggregate($report, $period)];
        }

        return Inertia::render('reports/index', [
            'reports' => collect(ReportService::REPORTS)->map(fn (string $label, string $key) => ['value' => $key, 'label' => $label])->values(),
            'report' => $report,
            'columns' => collect($this->reports->columns($report))->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values(),
            'rows' => $data,
            'periods' => $context->options(),
            'selectedPeriod' => $period ? ['id' => $period->id, 'name' => $period->name, 'status' => Present::enum($period->status)] : null,
            'filters' => $request->only(['area_id', 'status', 'pic_user_id']),
            'areaOptions' => $period ? Options::areas($period->guideline_version_id) : [],
            'userOptions' => $period ? Options::usersOf($period->organization_id) : [],
            'statusOptions' => $this->reports->statusOptions($report),
            'canExport' => $request->user()->can('exportReports'),
        ]);
    }

    /**
     * CSV export with exactly the same filters and scope as the report page.
     */
    public function export(Request $request, PeriodContext $context, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('exportReports');
        $period = $context->selected;
        abort_if($period === null, 404);
        // Never fall back silently to another period when exporting: an inaccessible period is a 403.
        abort_if($request->filled('period_id') && $period->id !== $request->integer('period_id'), 403);

        $report = $this->reportKey($request);
        $filters = $this->filters($request);
        $columns = $this->reports->columns($report);
        $query = $this->reports->query($report, $period, $filters);

        $audit->record('report.exported', 'Mengekspor '.ReportService::REPORTS[$report]." periode {$period->name}", $period, properties: [
            'report' => $report,
            'filters' => $filters,
        ]);

        $filename = Str::slug(ReportService::REPORTS[$report].' '.$period->name).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $report, $period, $columns) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF"); // BOM so spreadsheet apps read UTF-8 correctly
            fputcsv($out, array_values($columns), escape: '');

            $write = function (array $row) use ($out, $columns) {
                fputcsv($out, array_map(fn (string $key) => $this->cell($row[$key] ?? null), array_keys($columns)), escape: '');
            };

            if ($query) {
                foreach ($query->lazy(500) as $model) {
                    $write($this->reports->map($report, $model));
                }
            } else {
                array_map($write, $this->reports->aggregate($report, $period));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reportKey(Request $request): string
    {
        $report = $request->string('report')->value();

        return array_key_exists($report, ReportService::REPORTS) ? $report : 'programs';
    }

    /**
     * @return array{area_id: int|null, status: string|null, pic_user_id: int|null}
     */
    private function filters(Request $request): array
    {
        return [
            'area_id' => $request->integer('area_id') ?: null,
            'status' => $request->string('status')->value() ?: null,
            'pic_user_id' => $request->integer('pic_user_id') ?: null,
        ];
    }

    /**
     * Neutralise spreadsheet formula injection.
     */
    private function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
