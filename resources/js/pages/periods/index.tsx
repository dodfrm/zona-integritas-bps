import { Head, Link } from '@inertiajs/react';
import { CalendarRange, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import {
    FilterBar,
    ResetFilters,
    SelectFilter,
    useFilters,
} from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDate } from '@/lib/format';
import { create, index, show } from '@/routes/periods';
import type {
    EnumValue,
    Filters,
    Option,
    OrganizationBrief,
    Paginated,
} from '@/types';

type Row = {
    id: number;
    name: string;
    year: number;
    start_date: string | null;
    end_date: string | null;
    status: EnumValue;
    organization: OrganizationBrief;
    guideline: string;
    programs_count: number;
};

export default function PeriodsIndex({
    periods,
    filters,
    statusOptions,
    organizationOptions,
    canCreate,
}: {
    periods: Paginated<Row>;
    filters: Filters;
    statusOptions: Option[];
    organizationOptions: Option[];
    canCreate: boolean;
}) {
    const { apply, reset } = useFilters(index().url, filters);

    return (
        <>
            <Head title="Periode ZI" />
            <PageBody>
                <PageHeader
                    title="Periode ZI"
                    description="Setiap organisasi menjalankan periodenya sendiri. Periode yang ditutup menjadi baca-saja tanpa menghapus data historis."
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Buat periode
                                </Link>
                            </Button>
                        )
                    }
                />
                <FilterBar>
                    <SelectFilter
                        id="filter-org"
                        label="Organisasi"
                        value={filters.organization_id as string}
                        options={organizationOptions}
                        onChange={(organization_id) =>
                            apply({ organization_id })
                        }
                    />
                    <SelectFilter
                        id="filter-status"
                        label="Status"
                        value={filters.status as string}
                        options={statusOptions}
                        onChange={(status) => apply({ status })}
                    />
                    <ResetFilters onReset={() => reset()} />
                </FilterBar>
                <DataTable
                    caption="Daftar periode ZI"
                    rows={periods.data}
                    empty={
                        <EmptyState
                            title="Belum ada periode"
                            description="Buat periode untuk mulai menyusun program kerja."
                            icon={CalendarRange}
                        />
                    }
                    columns={[
                        {
                            key: 'name',
                            header: 'Periode',
                            cell: (row) => (
                                <Link
                                    href={show(row.id)}
                                    className="font-bold text-primary hover:underline"
                                >
                                    {row.name}
                                </Link>
                            ),
                        },
                        {
                            key: 'year',
                            header: 'Tahun',
                            cell: (row) => row.year,
                        },
                        {
                            key: 'org',
                            header: 'Organisasi',
                            cell: (row) => row.organization.name,
                        },
                        {
                            key: 'dates',
                            header: 'Jangka waktu',
                            cell: (row) =>
                                `${formatDate(row.start_date)} – ${formatDate(row.end_date)}`,
                        },
                        {
                            key: 'guideline',
                            header: 'Pedoman',
                            cell: (row) => row.guideline,
                        },
                        {
                            key: 'programs',
                            header: 'Program',
                            cell: (row) => row.programs_count,
                            className: 'text-right tabular-nums',
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (row) => <StatusBadge status={row.status} />,
                        },
                    ]}
                />
                <Pagination page={periods} />
            </PageBody>
        </>
    );
}

PeriodsIndex.layout = {
    breadcrumbs: [{ title: 'Periode ZI', href: index() }],
};
