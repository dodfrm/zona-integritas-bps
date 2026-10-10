import { Head, Link } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import {
    FilterBar,
    PeriodFilter,
    ResetFilters,
    SearchFilter,
    SelectFilter,
    useFilters,
} from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { OverdueBadge, StatusBadge } from '@/components/zi/status-badge';
import { formatDate } from '@/lib/format';
import { create, index, show } from '@/routes/action-plans';
import { show as showProgram } from '@/routes/programs';
import type {
    EnumValue,
    Filters,
    Option,
    Paginated,
    PeriodOption,
    UserBrief,
} from '@/types';

type Row = {
    id: number;
    title: string;
    program: { id: number; name: string };
    area: string | null;
    pic: UserBrief | null;
    status: EnumValue;
    progress_percent: number | null;
    due_date: string | null;
    is_overdue: boolean;
    evidence_count: number;
    archived: boolean;
};

type Props = {
    plans: Paginated<Row>;
    periods: PeriodOption[];
    selectedPeriodId: number | null;
    filters: Filters;
    programOptions: Option[];
    areaOptions: Option[];
    userOptions: Option[];
    statusOptions: Option[];
    canCreate: boolean;
};

export default function ActionPlansIndex({
    plans,
    periods,
    selectedPeriodId,
    filters,
    programOptions,
    areaOptions,
    userOptions,
    statusOptions,
    canCreate,
}: Props) {
    const { apply, reset } = useFilters(index().url, {
        ...filters,
        period_id: selectedPeriodId,
    });

    return (
        <>
            <Head title="Rencana Aksi" />
            <PageBody>
                <PageHeader
                    title="Rencana Aksi"
                    description='Status pelaksanaan berbeda dari status verifikasi bukti dukung: "Selesai" tidak berarti telah diverifikasi.'
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Tambah rencana aksi
                                </Link>
                            </Button>
                        )
                    }
                />
                <FilterBar>
                    <PeriodFilter
                        periods={periods}
                        selectedId={selectedPeriodId}
                        onChange={(period_id) =>
                            apply({
                                period_id,
                                work_program_id: null,
                                area_id: null,
                                pic_user_id: null,
                            })
                        }
                    />
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Judul rencana aksi"
                    />
                    <SelectFilter
                        id="filter-program"
                        label="Program"
                        value={filters.work_program_id as string}
                        options={programOptions}
                        onChange={(work_program_id) =>
                            apply({ work_program_id })
                        }
                    />
                    <SelectFilter
                        id="filter-area"
                        label="Area"
                        value={filters.area_id as string}
                        options={areaOptions}
                        onChange={(area_id) => apply({ area_id })}
                    />
                    <SelectFilter
                        id="filter-status"
                        label="Status"
                        value={filters.status as string}
                        options={statusOptions}
                        onChange={(status) => apply({ status })}
                    />
                    <SelectFilter
                        id="filter-pic"
                        label="Penanggung jawab"
                        value={filters.pic_user_id as string}
                        options={userOptions}
                        onChange={(pic_user_id) => apply({ pic_user_id })}
                    />
                    <div className="flex flex-col gap-1 text-sm sm:pb-2">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                className="size-4 accent-primary"
                                checked={Boolean(filters.overdue)}
                                onChange={(event) =>
                                    apply({
                                        overdue: event.target.checked
                                            ? 1
                                            : null,
                                    })
                                }
                            />
                            Hanya terlambat
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                className="size-4 accent-primary"
                                checked={Boolean(filters.archived)}
                                onChange={(event) =>
                                    apply({
                                        archived: event.target.checked
                                            ? 1
                                            : null,
                                    })
                                }
                            />
                            Tampilkan arsip
                        </label>
                    </div>
                    <ResetFilters
                        onReset={() => reset({ period_id: selectedPeriodId })}
                    />
                </FilterBar>
                <DataTable
                    caption="Daftar rencana aksi"
                    rows={plans.data}
                    rowClassName={(row) =>
                        row.archived ? 'opacity-60' : undefined
                    }
                    empty={
                        <EmptyState
                            title="Tidak ada rencana aksi"
                            description="Ubah filter atau tambahkan rencana aksi dari halaman program kerja."
                            icon={ClipboardList}
                        />
                    }
                    columns={[
                        {
                            key: 'title',
                            header: 'Rencana aksi',
                            cell: (row) => (
                                <div className="min-w-48">
                                    <Link
                                        href={show(row.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {row.title}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        <Link
                                            href={showProgram(row.program.id)}
                                            className="hover:underline"
                                        >
                                            {row.program.name}
                                        </Link>
                                        {row.area && ` · ${row.area}`}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'pic',
                            header: 'Penanggung jawab',
                            cell: (row) => row.pic?.name ?? '—',
                        },
                        {
                            key: 'due',
                            header: 'Tenggat',
                            cell: (row) => (
                                <div className="flex flex-col items-start gap-1">
                                    {formatDate(row.due_date)}
                                    {row.is_overdue && <OverdueBadge />}
                                </div>
                            ),
                        },
                        {
                            key: 'progress',
                            header: 'Progres dilaporkan',
                            cell: (row) =>
                                row.progress_percent !== null
                                    ? `${row.progress_percent}%`
                                    : '—',
                            className: 'tabular-nums',
                        },
                        {
                            key: 'evidence',
                            header: 'Bukti',
                            cell: (row) => row.evidence_count,
                            className: 'text-right tabular-nums',
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (row) => (
                                <StatusBadge
                                    status={
                                        row.archived
                                            ? {
                                                  value: 'archived',
                                                  label: 'Diarsipkan',
                                              }
                                            : row.status
                                    }
                                />
                            ),
                        },
                    ]}
                />
                <Pagination page={plans} />
            </PageBody>
        </>
    );
}

ActionPlansIndex.layout = {
    breadcrumbs: [{ title: 'Rencana Aksi', href: index() }],
};
