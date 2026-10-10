import { Head, Link } from '@inertiajs/react';
import { Building2, Pencil, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import {
    FilterBar,
    ResetFilters,
    SearchFilter,
    SelectFilter,
    useFilters,
} from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { StatusBadge } from '@/components/zi/status-badge';
import { create, edit, index } from '@/routes/organizations';
import type { Filters, Option, OrganizationBrief, Paginated } from '@/types';

type Row = OrganizationBrief & {
    parent: string | null;
    is_active: boolean;
    users_count: number;
    can_update: boolean;
};

export default function OrganizationsIndex({
    organizations,
    filters,
    typeOptions,
    canCreate,
}: {
    organizations: Paginated<Row>;
    filters: Filters;
    typeOptions: Option[];
    canCreate: boolean;
}) {
    const { apply, reset } = useFilters(index().url, filters);

    return (
        <>
            <Head title="Organisasi" />
            <PageBody>
                <PageHeader
                    title="Organisasi"
                    description="Struktur BPS Pusat, Provinsi, dan Kabupaten/Kota. Daftar dibatasi sesuai cakupan akses Anda."
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Tambah organisasi
                                </Link>
                            </Button>
                        )
                    }
                />
                <FilterBar>
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Nama atau kode"
                    />
                    <SelectFilter
                        id="filter-type"
                        label="Tipe"
                        value={filters.type as string}
                        options={typeOptions}
                        onChange={(type) => apply({ type })}
                    />
                    <SelectFilter
                        id="filter-status"
                        label="Status"
                        value={filters.status as string}
                        options={[
                            { value: 'active', label: 'Aktif' },
                            { value: 'inactive', label: 'Nonaktif' },
                        ]}
                        onChange={(status) => apply({ status })}
                    />
                    <ResetFilters onReset={() => reset()} />
                </FilterBar>
                <DataTable
                    caption="Daftar organisasi"
                    rows={organizations.data}
                    empty={
                        <EmptyState
                            title="Tidak ada organisasi"
                            description="Ubah filter atau tambahkan organisasi baru."
                            icon={Building2}
                        />
                    }
                    columns={[
                        {
                            key: 'name',
                            header: 'Nama',
                            cell: (row) => (
                                <span className="font-bold">{row.name}</span>
                            ),
                        },
                        {
                            key: 'code',
                            header: 'Kode',
                            cell: (row) => row.code ?? '—',
                        },
                        {
                            key: 'type',
                            header: 'Tipe',
                            cell: (row) => row.type_label,
                        },
                        {
                            key: 'parent',
                            header: 'Induk',
                            cell: (row) => row.parent ?? '—',
                        },
                        {
                            key: 'users',
                            header: 'Pengguna',
                            cell: (row) => row.users_count,
                            className: 'text-right tabular-nums',
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (row) => (
                                <StatusBadge
                                    status={
                                        row.is_active
                                            ? {
                                                  value: 'active',
                                                  label: 'Aktif',
                                              }
                                            : {
                                                  value: 'archived',
                                                  label: 'Nonaktif',
                                              }
                                    }
                                />
                            ),
                        },
                        {
                            key: 'actions',
                            header: <span className="sr-only">Aksi</span>,
                            className: 'text-right',
                            cell: (row) =>
                                row.can_update && (
                                    <Button asChild size="sm" variant="ghost">
                                        <Link
                                            href={edit(row.id)}
                                            aria-label={`Ubah ${row.name}`}
                                        >
                                            <Pencil aria-hidden="true" />
                                            Ubah
                                        </Link>
                                    </Button>
                                ),
                        },
                    ]}
                />
                <Pagination page={organizations} />
            </PageBody>
        </>
    );
}

OrganizationsIndex.layout = {
    breadcrumbs: [{ title: 'Organisasi', href: index() }],
};
