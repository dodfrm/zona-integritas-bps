import { Head, Link, router } from '@inertiajs/react';
import { ClipboardCheck, ListChecks } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import { FilterBar, SelectFilter, useFilters } from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { SectionCard } from '@/components/zi/section-card';
import { OverdueBadge, StatusBadge } from '@/components/zi/status-badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { MyWorkSection } from '@/pages/dashboard';
import type { MyWork } from '@/pages/dashboard';
import { update } from '@/routes/assignments';
import { index } from '@/routes/tasks';
import { index as verificationsIndex } from '@/routes/verifications';
import type { Option, Paginated, TaskAssignment } from '@/types';

export default function TasksIndex({
    assignments,
    status,
    statusOptions,
    myWork,
    verificationCount,
}: {
    assignments: Paginated<TaskAssignment>;
    status: string;
    statusOptions: Option[];
    myWork: MyWork;
    verificationCount: number | null;
}) {
    const { apply } = useFilters(index().url, { status });

    return (
        <>
            <Head title="Tugas Saya" />
            <PageBody>
                <PageHeader
                    title="Tugas Saya"
                    description="Semua tanggung jawab yang diberikan kepada Anda. Penugasan tidak menambah hak akses di luar cakupan Anda."
                    actions={
                        verificationCount !== null && (
                            <Button asChild variant="outline">
                                <Link href={verificationsIndex()}>
                                    <ClipboardCheck aria-hidden="true" />
                                    Antrean verifikasi ({verificationCount})
                                </Link>
                            </Button>
                        )
                    }
                />

                <MyWorkSection myWork={myWork} compact={false} />

                <SectionCard
                    title="Daftar penugasan"
                    contentClassName="grid gap-3"
                >
                    <FilterBar>
                        <SelectFilter
                            id="filter-status"
                            label="Status penugasan"
                            value={status}
                            options={statusOptions}
                            allLabel="Aktif"
                            onChange={(value) =>
                                apply({ status: value || null })
                            }
                        />
                    </FilterBar>
                    <DataTable
                        caption="Daftar penugasan saya"
                        rows={assignments.data}
                        empty={
                            <EmptyState
                                title="Tidak ada penugasan"
                                icon={ListChecks}
                            />
                        }
                        columns={[
                            {
                                key: 'target',
                                header: 'Tugas',
                                cell: (row) => (
                                    <div className="min-w-48">
                                        {row.url ? (
                                            <Link
                                                href={row.url}
                                                className="font-bold text-primary hover:underline"
                                            >
                                                {row.target}
                                            </Link>
                                        ) : (
                                            <span className="font-bold">
                                                {row.target}
                                            </span>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            {row.type.label}
                                        </p>
                                        {row.notes && (
                                            <p className="mt-0.5 text-xs whitespace-pre-line">
                                                {row.notes}
                                            </p>
                                        )}
                                    </div>
                                ),
                            },
                            {
                                key: 'from',
                                header: 'Dari',
                                cell: (row) => (
                                    <span className="text-xs">
                                        {row.assigner?.name ?? 'Sistem'}
                                        <br />
                                        {formatDateTime(row.created_at)}
                                    </span>
                                ),
                            },
                            {
                                key: 'due',
                                header: 'Tenggat',
                                cell: (row) => (
                                    <div className="flex flex-col items-start gap-1">
                                        {formatDate(row.due_date)}
                                        {row.is_overdue &&
                                            row.status.value === 'active' && (
                                                <OverdueBadge />
                                            )}
                                    </div>
                                ),
                            },
                            {
                                key: 'status',
                                header: 'Status',
                                cell: (row) => (
                                    <StatusBadge status={row.status} />
                                ),
                            },
                            {
                                key: 'actions',
                                header: <span className="sr-only">Aksi</span>,
                                className: 'text-right',
                                cell: (row) =>
                                    row.status.value === 'active' && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                router.patch(
                                                    update(row.id).url,
                                                    { status: 'completed' },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Tandai selesai
                                        </Button>
                                    ),
                            },
                        ]}
                    />
                    <Pagination page={assignments} />
                </SectionCard>
            </PageBody>
        </>
    );
}

TasksIndex.layout = {
    breadcrumbs: [{ title: 'Tugas Saya', href: index() }],
};
