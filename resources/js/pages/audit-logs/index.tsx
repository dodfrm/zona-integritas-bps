import { Head } from '@inertiajs/react';
import { History } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import {
    FilterBar,
    FilterField,
    ResetFilters,
    SearchFilter,
    SelectFilter,
    useFilters,
} from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { formatDateTime } from '@/lib/format';
import { index } from '@/routes/audit-logs';
import type { Filters, Option, Paginated, UserBrief } from '@/types';

type Row = {
    id: number;
    event: string;
    description: string;
    user: UserBrief | null;
    organization: string | null;
    subject: string | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};

export default function AuditLogsIndex({
    logs,
    filters,
    moduleOptions,
}: {
    logs: Paginated<Row>;
    filters: Filters;
    moduleOptions: Option[];
}) {
    const { apply, reset } = useFilters(index().url, filters);

    return (
        <>
            <Head title="Audit Trail" />
            <PageBody>
                <PageHeader
                    title="Audit Trail"
                    description="Catatan tindakan penting yang tidak dapat diubah atau dihapus melalui aplikasi. Kata sandi dan token tidak pernah dicatat."
                />
                <FilterBar>
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Cari deskripsi"
                    />
                    <SelectFilter
                        id="filter-module"
                        label="Modul"
                        value={filters.module as string}
                        options={moduleOptions}
                        onChange={(module) => apply({ module })}
                    />
                    <FilterField
                        label="Dari tanggal"
                        htmlFor="filter-from"
                        className="sm:w-40"
                    >
                        <Input
                            id="filter-from"
                            type="date"
                            defaultValue={(filters.from as string) ?? ''}
                            onChange={(event) =>
                                apply({ from: event.target.value })
                            }
                        />
                    </FilterField>
                    <FilterField
                        label="Sampai tanggal"
                        htmlFor="filter-to"
                        className="sm:w-40"
                    >
                        <Input
                            id="filter-to"
                            type="date"
                            defaultValue={(filters.to as string) ?? ''}
                            onChange={(event) =>
                                apply({ to: event.target.value })
                            }
                        />
                    </FilterField>
                    <ResetFilters onReset={() => reset()} />
                </FilterBar>
                <DataTable
                    caption="Catatan audit"
                    rows={logs.data}
                    empty={
                        <EmptyState
                            title="Tidak ada catatan audit"
                            icon={History}
                        />
                    }
                    columns={[
                        {
                            key: 'time',
                            header: 'Waktu',
                            cell: (row) => (
                                <span className="text-xs whitespace-nowrap">
                                    {formatDateTime(row.created_at)}
                                </span>
                            ),
                        },
                        {
                            key: 'user',
                            header: 'Pengguna',
                            cell: (row) => (
                                <span className="text-xs">
                                    {row.user?.name ?? 'Sistem'}
                                    <br />
                                    {row.ip_address}
                                </span>
                            ),
                        },
                        {
                            key: 'description',
                            header: 'Tindakan',
                            cell: (row) => (
                                <div className="min-w-64">
                                    <p className="text-sm">{row.description}</p>
                                    <p className="font-mono text-[11px] text-muted-foreground">
                                        {row.event}
                                        {row.subject && ` · ${row.subject}`}
                                        {row.organization &&
                                            ` · ${row.organization}`}
                                    </p>
                                    {row.properties && (
                                        <details className="mt-1">
                                            <summary className="text-xs text-primary">
                                                Detail perubahan
                                            </summary>
                                            <pre className="mt-1 max-h-48 overflow-auto rounded bg-muted p-2 text-[11px] whitespace-pre-wrap">
                                                {JSON.stringify(
                                                    row.properties,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </details>
                                    )}
                                </div>
                            ),
                        },
                    ]}
                />
                <Pagination page={logs} />
            </PageBody>
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [{ title: 'Audit Trail', href: index() }],
};
