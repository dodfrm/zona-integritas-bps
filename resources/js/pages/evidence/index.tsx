import { Head, Link } from '@inertiajs/react';
import { FileCheck2, FileUp } from 'lucide-react';
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
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDateTime } from '@/lib/format';
import { create, index, show } from '@/routes/evidence';
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
    category: string;
    indicator: string | null;
    program: string | null;
    action_plan: string | null;
    uploader: UserBrief | null;
    version: number | null;
    status: EnumValue;
    updated_at: string;
    archived: boolean;
};

type Props = {
    items: Paginated<Row>;
    periods: PeriodOption[];
    selectedPeriodId: number | null;
    filters: Filters;
    areaOptions: Option[];
    programOptions: Option[];
    categoryOptions: Option[];
    statusOptions: Option[];
    canCreate: boolean;
};

export default function EvidenceIndex({
    items,
    periods,
    selectedPeriodId,
    filters,
    areaOptions,
    programOptions,
    categoryOptions,
    statusOptions,
    canCreate,
}: Props) {
    const { apply, reset } = useFilters(index().url, {
        ...filters,
        period_id: selectedPeriodId,
    });

    return (
        <>
            <Head title="Bukti Dukung" />
            <PageBody>
                <PageHeader
                    title="Repositori Bukti Dukung"
                    description="Berkas disimpan privat dan hanya dapat diunduh oleh pengguna berwenang dalam cakupan organisasi. Status verifikasi adalah keputusan administratif, bukan penilaian predikat ZI."
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
                                    <FileUp aria-hidden="true" />
                                    Unggah bukti
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
                                work_program_id: null,
                            })
                        }
                    />
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Judul bukti"
                    />
                    <SelectFilter
                        id="filter-area"
                        label="Area"
                        value={filters.area_id as string}
                        options={areaOptions}
                        onChange={(area_id) => apply({ area_id })}
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
                        id="filter-category"
                        label="Jenis"
                        value={filters.category as string}
                        options={categoryOptions}
                        onChange={(category) => apply({ category })}
                    />
                    <SelectFilter
                        id="filter-status"
                        label="Status verifikasi"
                        value={filters.status as string}
                        options={statusOptions}
                        onChange={(status) => apply({ status })}
                    />
                    <div className="flex flex-col gap-1 text-sm sm:pb-2">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                className="size-4 accent-primary"
                                checked={Boolean(filters.mine)}
                                onChange={(event) =>
                                    apply({
                                        mine: event.target.checked ? 1 : null,
                                    })
                                }
                            />
                            Unggahan saya
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
                    caption="Daftar bukti dukung"
                    rows={items.data}
                    rowClassName={(row) =>
                        row.archived ? 'opacity-60' : undefined
                    }
                    empty={
                        <EmptyState
                            title="Tidak ada bukti dukung"
                            description="Ubah filter atau unggah bukti baru."
                            icon={FileCheck2}
                        />
                    }
                    columns={[
                        {
                            key: 'title',
                            header: 'Bukti dukung',
                            cell: (row) => (
                                <div className="min-w-48">
                                    <Link
                                        href={show(row.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {row.title}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        {row.category} · versi{' '}
                                        {row.version ?? '—'}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'links',
                            header: 'Keterkaitan',
                            cell: (row) => (
                                <div className="max-w-72 text-xs">
                                    {row.indicator && (
                                        <p className="truncate">
                                            Indikator: {row.indicator}
                                        </p>
                                    )}
                                    {row.program && (
                                        <p className="truncate">
                                            Program: {row.program}
                                        </p>
                                    )}
                                    {row.action_plan && (
                                        <p className="truncate">
                                            RA: {row.action_plan}
                                        </p>
                                    )}
                                </div>
                            ),
                        },
                        {
                            key: 'uploader',
                            header: 'Pengunggah',
                            cell: (row) => row.uploader?.name ?? '—',
                        },
                        {
                            key: 'updated',
                            header: 'Diperbarui',
                            cell: (row) => formatDateTime(row.updated_at),
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
                <Pagination page={items} />
            </PageBody>
        </>
    );
}

EvidenceIndex.layout = {
    breadcrumbs: [{ title: 'Bukti Dukung', href: index() }],
};
