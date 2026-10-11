import { Head, Link } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
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
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as showEvidence } from '@/routes/evidence';
import { index } from '@/routes/verifications';
import type { EnumValue, Filters, Option, Paginated, UserBrief } from '@/types';

type Row = {
    id: number;
    evidence: { id: number; title: string; category: string };
    organization: string;
    period: string;
    uploader: UserBrief | null;
    version_number: number;
    stage_name: string;
    status: EnumValue;
    assigned_verifier: UserBrief | null;
    decider: UserBrief | null;
    decided_at: string | null;
    decision_note: string | null;
    created_at: string;
};

export default function VerificationsIndex({
    reviews,
    tab,
    filters,
    statusOptions,
    queueCount,
}: {
    reviews: Paginated<Row>;
    tab: 'queue' | 'history';
    filters: Filters;
    statusOptions: Option[];
    queueCount: number;
}) {
    const { apply, reset } = useFilters(index().url, { ...filters, tab });
    const tabClass = (active: boolean) =>
        cn(
            'rounded-md px-3 py-1.5 text-sm font-bold transition-colors duration-150',
            active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
        );

    return (
        <>
            <Head title="Verifikasi" />
            <PageBody>
                <PageHeader
                    title="Verifikasi Bukti Dukung"
                    description="Antrean berisi tahap yang dijalankan organisasi Anda, dalam cakupan Anda, dan bukan unggahan Anda sendiri."
                />
                <nav aria-label="Tab verifikasi" className="flex gap-2">
                    <Link
                        href={index()}
                        className={tabClass(tab === 'queue')}
                        aria-current={tab === 'queue' ? 'page' : undefined}
                    >
                        Antrean ({queueCount})
                    </Link>
                    <Link
                        href={index({ query: { tab: 'history' } })}
                        className={tabClass(tab === 'history')}
                        aria-current={tab === 'history' ? 'page' : undefined}
                    >
                        Riwayat keputusan
                    </Link>
                </nav>
                <FilterBar>
                    <SearchFilter
                        value={filters.search as string}
                        onSearch={(search) => apply({ search })}
                        placeholder="Judul bukti"
                    />
                    <SelectFilter
                        id="filter-status"
                        label="Status tahap"
                        value={filters.status as string}
                        options={statusOptions}
                        onChange={(status) => apply({ status })}
                    />
                    {tab === 'history' && (
                        <label className="flex items-center gap-2 text-sm sm:pb-2">
                            <input
                                type="checkbox"
                                className="size-4 accent-primary"
                                checked={Boolean(filters.all)}
                                onChange={(event) =>
                                    apply({
                                        all: event.target.checked ? 1 : null,
                                    })
                                }
                            />
                            Semua verifikator dalam cakupan
                        </label>
                    )}
                    <ResetFilters onReset={() => reset({ tab })} />
                </FilterBar>
                <DataTable
                    caption={
                        tab === 'queue'
                            ? 'Antrean verifikasi'
                            : 'Riwayat keputusan verifikasi'
                    }
                    rows={reviews.data}
                    empty={
                        <EmptyState
                            title={
                                tab === 'queue'
                                    ? 'Antrean kosong'
                                    : 'Belum ada keputusan'
                            }
                            icon={ClipboardCheck}
                        />
                    }
                    columns={[
                        {
                            key: 'evidence',
                            header: 'Bukti dukung',
                            cell: (row) => (
                                <div className="min-w-48">
                                    <Link
                                        href={showEvidence(row.evidence.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {row.evidence.title}
                                    </Link>
                                    <p className="text-xs text-muted-foreground">
                                        {row.evidence.category} · versi{' '}
                                        {row.version_number} ·{' '}
                                        {row.uploader?.name}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            key: 'org',
                            header: 'Organisasi / periode',
                            cell: (row) => (
                                <span className="text-xs">
                                    {row.organization}
                                    <br />
                                    {row.period}
                                </span>
                            ),
                        },
                        {
                            key: 'stage',
                            header: 'Tahap',
                            cell: (row) => row.stage_name,
                        },
                        tab === 'queue'
                            ? {
                                  key: 'submitted',
                                  header: 'Diajukan',
                                  cell: (row) => formatDateTime(row.created_at),
                              }
                            : {
                                  key: 'decided',
                                  header: 'Keputusan',
                                  cell: (row) => (
                                      <div className="text-xs">
                                          <p>
                                              {row.decider?.name} ·{' '}
                                              {formatDateTime(row.decided_at)}
                                          </p>
                                          {row.decision_note && (
                                              <p className="mt-0.5 line-clamp-2 text-muted-foreground">
                                                  {row.decision_note}
                                              </p>
                                          )}
                                      </div>
                                  ),
                              },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (row) => <StatusBadge status={row.status} />,
                        },
                    ]}
                />
                <Pagination page={reviews} />
            </PageBody>
        </>
    );
}

VerificationsIndex.layout = {
    breadcrumbs: [{ title: 'Verifikasi', href: index() }],
};
