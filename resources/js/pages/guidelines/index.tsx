import { Head, Link } from '@inertiajs/react';
import { BookOpenCheck, Copy, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDateTime } from '@/lib/format';
import { create, index, show } from '@/routes/guidelines';
import type { EnumValue } from '@/types';

type Row = {
    id: number;
    name: string;
    reference: string | null;
    status: EnumValue;
    published_at: string | null;
    areas_count: number;
    indicators_count: number;
    periods_count: number;
};

export default function GuidelinesIndex({
    versions,
    canCreate,
}: {
    versions: Row[];
    canCreate: boolean;
}) {
    return (
        <>
            <Head title="Pedoman & Indikator" />
            <PageBody>
                <PageHeader
                    title="Pedoman, area, dan indikator"
                    description="Setiap versi pedoman adalah snapshot. Versi yang berlaku dikunci agar data periode lama tetap merujuk definisi yang berlaku saat itu. Nama, kode, dan bobot wajib dicocokkan dengan pedoman resmi."
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Versi baru
                                </Link>
                            </Button>
                        )
                    }
                />
                <DataTable
                    caption="Daftar versi pedoman"
                    rows={versions}
                    empty={
                        <EmptyState
                            title="Belum ada versi pedoman"
                            icon={BookOpenCheck}
                        />
                    }
                    columns={[
                        {
                            key: 'name',
                            header: 'Versi pedoman',
                            cell: (row) => (
                                <div>
                                    <Link
                                        href={show(row.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {row.name}
                                    </Link>
                                    {row.reference && (
                                        <p className="text-xs text-muted-foreground">
                                            {row.reference}
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
                        {
                            key: 'published',
                            header: 'Diberlakukan',
                            cell: (row) => formatDateTime(row.published_at),
                        },
                        {
                            key: 'areas',
                            header: 'Area',
                            cell: (row) => row.areas_count,
                            className: 'text-right tabular-nums',
                        },
                        {
                            key: 'indicators',
                            header: 'Indikator',
                            cell: (row) => row.indicators_count,
                            className: 'text-right tabular-nums',
                        },
                        {
                            key: 'periods',
                            header: 'Dipakai periode',
                            cell: (row) => row.periods_count,
                            className: 'text-right tabular-nums',
                        },
                        {
                            key: 'copy',
                            header: <span className="sr-only">Aksi</span>,
                            className: 'text-right',
                            cell: (row) =>
                                canCreate && (
                                    <Button asChild size="sm" variant="ghost">
                                        <Link
                                            href={create({
                                                query: { copy_from: row.id },
                                            })}
                                            aria-label={`Salin ${row.name}`}
                                        >
                                            <Copy aria-hidden="true" />
                                            Salin
                                        </Link>
                                    </Button>
                                ),
                        },
                    ]}
                />
            </PageBody>
        </>
    );
}

GuidelinesIndex.layout = {
    breadcrumbs: [{ title: 'Pedoman & Indikator', href: index() }],
};
