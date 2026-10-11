import { Head, Link } from '@inertiajs/react';
import { Download, FileBarChart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import {
    FilterBar,
    PeriodFilter,
    ResetFilters,
    SelectFilter,
    useFilters,
} from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { cn } from '@/lib/utils';
import { show as showActionPlan } from '@/routes/action-plans';
import { show as showEvidence } from '@/routes/evidence';
import { show as showProgram } from '@/routes/programs';
import { exportMethod, index } from '@/routes/reports';
import type {
    EnumValue,
    Filters,
    Option,
    Paginated,
    PeriodOption,
} from '@/types';

type Row = Record<string, string | number | null> & {
    id?: number;
    evidence_id?: number;
};

type Props = {
    reports: Option[];
    report: string;
    columns: { key: string; label: string }[];
    rows: Paginated<Row> | { data: Row[] } | null;
    periods: PeriodOption[];
    selectedPeriod: { id: number; name: string; status: EnumValue } | null;
    filters: Filters;
    areaOptions: Option[];
    userOptions: Option[];
    statusOptions: Option[];
    canExport: boolean;
};

const NOTES: Record<string, string> = {
    area_progress:
        'Penyelesaian rencana aksi = rencana aksi Selesai ÷ rencana aksi tidak dibatalkan/diarsipkan. Bukan nilai evaluasi resmi ZI.',
    action_plans:
        'Progres (%) adalah angka yang dilaporkan pelaksana, bukan hasil perhitungan sistem.',
    verification_history:
        'Keputusan verifikasi administratif per tahap dan versi bukti.',
};

export default function ReportsIndex({
    reports,
    report,
    columns,
    rows,
    periods,
    selectedPeriod,
    filters,
    areaOptions,
    userOptions,
    statusOptions,
    canExport,
}: Props) {
    const query = { ...filters, report, period_id: selectedPeriod?.id };
    const { apply, reset } = useFilters(index().url, query);
    const data =
        rows?.data.map((row, position) => ({
            ...row,
            id: row.id ?? position,
        })) ?? [];
    const exportUrl = exportMethod({
        query: Object.fromEntries(
            Object.entries(query).filter(
                ([, value]) => value != null && value !== '',
            ),
        ) as Record<string, string>,
    }).url;

    const linkFor = (row: Row): string | null => {
        if (report === 'programs' && row.id) return showProgram(row.id).url;
        if ((report === 'action_plans' || report === 'overdue') && row.id)
            return showActionPlan(row.id).url;
        if ((report === 'evidence' || report === 'revisions') && row.id)
            return showEvidence(row.id).url;
        if (report === 'verification_history' && row.evidence_id)
            return showEvidence(row.evidence_id).url;

        return null;
    };

    return (
        <>
            <Head title="Laporan" />
            <PageBody>
                <PageHeader
                    title="Laporan"
                    description="Rekap operasional berdasarkan data aktual dan cakupan akses Anda. Laporan ini bukan laporan resmi evaluasi ZI."
                    actions={
                        canExport &&
                        selectedPeriod && (
                            <Button asChild>
                                <a href={exportUrl}>
                                    <Download aria-hidden="true" />
                                    Ekspor CSV
                                </a>
                            </Button>
                        )
                    }
                />
                <nav
                    aria-label="Jenis laporan"
                    className="flex flex-wrap gap-2"
                >
                    {reports.map((option) => (
                        <Link
                            key={option.value}
                            href={index({
                                query: {
                                    report: option.value,
                                    period_id: selectedPeriod?.id,
                                },
                            })}
                            className={cn(
                                'rounded-md border px-3 py-1.5 text-sm transition-colors duration-150',
                                option.value === report
                                    ? 'border-primary bg-primary font-bold text-primary-foreground'
                                    : 'bg-card hover:bg-accent',
                            )}
                            aria-current={
                                option.value === report ? 'page' : undefined
                            }
                        >
                            {option.label}
                        </Link>
                    ))}
                </nav>
                <FilterBar>
                    <PeriodFilter
                        periods={periods}
                        selectedId={selectedPeriod?.id}
                        onChange={(period_id) =>
                            apply({
                                period_id,
                                area_id: null,
                                pic_user_id: null,
                            })
                        }
                    />
                    {report !== 'area_progress' && (
                        <>
                            <SelectFilter
                                id="filter-area"
                                label="Area"
                                value={filters.area_id as string}
                                options={areaOptions}
                                onChange={(area_id) => apply({ area_id })}
                            />
                            {statusOptions.length > 0 && (
                                <SelectFilter
                                    id="filter-status"
                                    label="Status"
                                    value={filters.status as string}
                                    options={statusOptions}
                                    onChange={(status) => apply({ status })}
                                />
                            )}
                            <SelectFilter
                                id="filter-pic"
                                label={
                                    report === 'verification_history'
                                        ? 'Verifikator'
                                        : report.startsWith('evidence') ||
                                            report === 'revisions'
                                          ? 'Pengunggah'
                                          : 'Penanggung jawab'
                                }
                                value={filters.pic_user_id as string}
                                options={userOptions}
                                onChange={(pic_user_id) =>
                                    apply({ pic_user_id })
                                }
                            />
                        </>
                    )}
                    <ResetFilters
                        onReset={() =>
                            reset({ report, period_id: selectedPeriod?.id })
                        }
                    />
                </FilterBar>
                {NOTES[report] && (
                    <p className="text-sm text-muted-foreground">
                        {NOTES[report]}
                    </p>
                )}
                {!selectedPeriod ? (
                    <EmptyState
                        title="Belum ada periode dalam cakupan Anda"
                        icon={FileBarChart}
                    />
                ) : (
                    <DataTable
                        caption={
                            reports.find((option) => option.value === report)
                                ?.label ?? 'Laporan'
                        }
                        rows={data}
                        empty={
                            <EmptyState
                                title="Tidak ada data untuk filter ini"
                                icon={FileBarChart}
                            />
                        }
                        columns={columns.map((column, position) => ({
                            key: column.key,
                            header: column.label,
                            cell: (row: Row) => {
                                const value = row[column.key];
                                const href =
                                    position === 0 ? linkFor(row) : null;
                                const text =
                                    value === null || value === ''
                                        ? '—'
                                        : column.key === 'completion'
                                          ? `${value}%`
                                          : String(value);

                                return href ? (
                                    <Link
                                        href={href}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {text}
                                    </Link>
                                ) : (
                                    text
                                );
                            },
                        }))}
                    />
                )}
                {rows && 'links' in rows && <Pagination page={rows} />}
            </PageBody>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Laporan', href: index() }],
};
