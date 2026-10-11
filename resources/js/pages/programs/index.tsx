import { Head, Link } from '@inertiajs/react';
import { FolderKanban, Plus } from 'lucide-react';
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
import { ProgressBar } from '@/components/zi/progress-bar';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDate, percent } from '@/lib/format';
import { create, index, show } from '@/routes/programs';
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
    code: string | null;
    name: string;
    area: string | null;
    pic: UserBrief | null;
    status: EnumValue;
    target_date: string | null;
    action_plans_count: number;
    completed_action_plans_count: number;
    overdue_action_plans_count: number;
    archived: boolean;
};

type Props = {
    programs: Paginated<Row>;
    periods: PeriodOption[];
    selectedPeriodId: number | null;
    filters: Filters;
    areaOptions: Option[];
    userOptions: Option[];
    statusOptions: Option[];
    canCreate: boolean;
};

export default function ProgramsIndex({
    programs,
    periods,
    selectedPeriodId,
    filters,
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
            <Head title="Program Kerja" />
            <PageBody>
                <PageHeader
                    title="Program Kerja"
                    description="Program kerja per periode dan area. Progres dihitung dari rencana aksi berstatus Selesai dibanding rencana aksi yang tidak dibatalkan."
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link
                                    href={create({
                                        query: {
                                            period_id:
                                                selectedPeriodId ?? undefined,
                                        },
                                    })}
                                >
                                    <Plus aria-hidden="true" />
                                    Tambah program
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
                                area_id: null,
                                pic_user_id: null,
                            })
                        }
                    />
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Nama atau kode program"
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
                    <label className="flex items-center gap-2 text-sm sm:pb-2">
                        <input
                            type="checkbox"
                            className="size-4 accent-primary"
                            checked={Boolean(filters.archived)}
                            onChange={(event) =>
                                apply({
                                    archived: event.target.checked ? 1 : null,
                                })
                            }
                        />
                        Tampilkan arsip
                    </label>
                    <ResetFilters
                        onReset={() => reset({ period_id: selectedPeriodId })}
                    />
                </FilterBar>
                <DataTable
                    caption="Daftar program kerja"
                    rows={programs.data}
                    rowClassName={(row) =>
                        row.archived ? 'opacity-60' : undefined
                    }
                    empty={
                        <EmptyState
                            title={
                                periods.length === 0
                                    ? 'Belum ada periode ZI'
                                    : 'Belum ada program kerja'
                            }
                            description={
                                periods.length === 0
                                    ? 'Program kerja dibuat di dalam periode ZI.'
                                    : 'Tambahkan program kerja atau ubah filter.'
                            }
                            icon={FolderKanban}
                        />
                    }
                    columns={[
                        {
                            key: 'name',
                            header: 'Program kerja',
                            cell: (row) => (
                                <div className="min-w-48">
                                    <Link
                                        href={show(row.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {row.code ? `${row.code} · ` : ''}
                                        {row.name}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        {row.area ?? 'Tanpa area'}
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
                            key: 'target',
                            header: 'Target selesai',
                            cell: (row) => formatDate(row.target_date),
                        },
                        {
                            key: 'progress',
                            header: 'Penyelesaian RA',
                            className: 'min-w-44',
                            cell: (row) => (
                                <div className="grid gap-1">
                                    <ProgressBar
                                        label="Rencana aksi selesai"
                                        value={percent(
                                            row.completed_action_plans_count,
                                            row.action_plans_count,
                                        )}
                                        detail={`${row.completed_action_plans_count}/${row.action_plans_count}`}
                                    />
                                    {row.overdue_action_plans_count > 0 && (
                                        <span className="text-xs font-bold text-danger">
                                            {row.overdue_action_plans_count}{' '}
                                            rencana aksi terlambat
                                        </span>
                                    )}
                                </div>
                            ),
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (row) => (
                                <div className="flex flex-col items-start gap-1">
                                    <StatusBadge status={row.status} />
                                    {row.archived && (
                                        <StatusBadge
                                            status={{
                                                value: 'archived',
                                                label: 'Diarsipkan',
                                            }}
                                        />
                                    )}
                                </div>
                            ),
                        },
                    ]}
                />
                <Pagination page={programs} />
            </PageBody>
        </>
    );
}

ProgramsIndex.layout = {
    breadcrumbs: [{ title: 'Program Kerja', href: index() }],
};
