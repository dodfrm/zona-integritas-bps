import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Users } from 'lucide-react';
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
import { create, edit, index } from '@/routes/users';
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
    email: string;
    organization: OrganizationBrief | null;
    roles: string[];
    access_scope: EnumValue;
    is_active: boolean;
    can_update: boolean;
};

export default function UsersIndex({
    users,
    filters,
    organizationOptions,
    roleOptions,
    canCreate,
}: {
    users: Paginated<Row>;
    filters: Filters;
    organizationOptions: Option[];
    roleOptions: Option[];
    canCreate: boolean;
}) {
    const { apply, reset } = useFilters(index().url, filters);

    return (
        <>
            <Head title="Pengguna" />
            <PageBody>
                <PageHeader
                    title="Pengguna"
                    description="Kelola akun, organisasi, role, dan cakupan akses data. Role menentukan tindakan; cakupan menentukan data organisasi mana yang dapat diakses."
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Tambah pengguna
                                </Link>
                            </Button>
                        )
                    }
                />
                <FilterBar>
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Nama atau email"
                    />
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
                        id="filter-role"
                        label="Role"
                        value={filters.role as string}
                        options={roleOptions}
                        onChange={(role) => apply({ role })}
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
                    caption="Daftar pengguna"
                    rows={users.data}
                    empty={
                        <EmptyState title="Tidak ada pengguna" icon={Users} />
                    }
                    columns={[
                        {
                            key: 'name',
                            header: 'Pengguna',
                            cell: (row) => (
                                <div>
                                    <p className="font-bold">{row.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {row.email}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'org',
                            header: 'Organisasi',
                            cell: (row) => row.organization?.name ?? '—',
                        },
                        {
                            key: 'roles',
                            header: 'Role',
                            cell: (row) => row.roles.join(', ') || '—',
                        },
                        {
                            key: 'scope',
                            header: 'Cakupan',
                            cell: (row) => row.access_scope.label,
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
                <Pagination page={users} />
            </PageBody>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Pengguna', href: index() }],
};
