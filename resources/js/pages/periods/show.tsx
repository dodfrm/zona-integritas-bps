import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/zi/confirm-dialog';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { DetailList, SectionCard } from '@/components/zi/section-card';
import { StatCard } from '@/components/zi/stat-card';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { index as actionPlansIndex } from '@/routes/action-plans';
import { index as evidenceIndex } from '@/routes/evidence';
import { show as showGuideline } from '@/routes/guidelines';
import { edit, index, status as changeStatus } from '@/routes/periods';
import { index as programsIndex } from '@/routes/programs';
import type { EnumValue, OrganizationBrief } from '@/types';

type Props = {
    period: {
        id: number;
        name: string;
        year: number;
        start_date: string | null;
        end_date: string | null;
        notes: string | null;
        status: EnumValue;
        closed_at: string | null;
        organization: OrganizationBrief;
        guideline: { id: number; name: string };
    };
    summary: { programs: number; action_plans: number; evidence: number };
    transitions: EnumValue[];
    canUpdate: boolean;
};

const TRANSITION_TEXT: Record<
    string,
    { label: string; description: string; destructive?: boolean }
> = {
    active: {
        label: 'Aktifkan',
        description:
            'Periode menjadi aktif dan data dapat dikelola. Satu organisasi hanya boleh memiliki satu periode aktif.',
    },
    closed: {
        label: 'Tutup periode',
        description:
            'Semua program, rencana aksi, dan bukti dukung menjadi baca-saja. Data dan keputusan yang ada tidak diubah.',
    },
    archived: {
        label: 'Arsipkan',
        description:
            'Periode diarsipkan secara permanen (tetap dapat dilihat, tidak dapat diaktifkan kembali).',
        destructive: true,
    },
};

export default function PeriodShow({
    period,
    summary,
    transitions,
    canUpdate,
}: Props) {
    const query = { query: { period_id: period.id } };

    return (
        <>
            <Head title={period.name} />
            <PageBody>
                <PageHeader
                    title={period.name}
                    description={`${period.organization.name} · Tahun ${period.year}`}
                    meta={<StatusBadge status={period.status} />}
                    actions={
                        canUpdate && (
                            <>
                                {transitions.map((transition) => {
                                    const text =
                                        TRANSITION_TEXT[transition.value];

                                    return (
                                        <ConfirmDialog
                                            key={transition.value}
                                            trigger={
                                                <Button
                                                    variant={
                                                        text.destructive
                                                            ? 'outline'
                                                            : 'default'
                                                    }
                                                >
                                                    {text.label}
                                                </Button>
                                            }
                                            title={`${text.label}: ${period.name}?`}
                                            description={text.description}
                                            confirmLabel={text.label}
                                            destructive={text.destructive}
                                            action={{
                                                url: changeStatus(period.id)
                                                    .url,
                                                data: {
                                                    status: transition.value,
                                                },
                                            }}
                                        />
                                    );
                                })}
                                <Button asChild variant="outline">
                                    <Link href={edit(period.id)}>
                                        <Pencil aria-hidden="true" />
                                        Ubah
                                    </Link>
                                </Button>
                            </>
                        )
                    }
                />
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <StatCard
                        label="Program kerja"
                        value={summary.programs}
                        href={programsIndex(query).url}
                    />
                    <StatCard
                        label="Rencana aksi"
                        value={summary.action_plans}
                        href={actionPlansIndex(query).url}
                    />
                    <StatCard
                        label="Bukti dukung"
                        value={summary.evidence}
                        href={evidenceIndex(query).url}
                    />
                </div>
                <SectionCard title="Detail periode">
                    <DetailList
                        items={[
                            {
                                label: 'Organisasi',
                                value: period.organization.name,
                            },
                            { label: 'Tahun', value: period.year },
                            {
                                label: 'Tanggal mulai',
                                value: formatDate(period.start_date),
                            },
                            {
                                label: 'Tanggal selesai',
                                value: formatDate(period.end_date),
                            },
                            {
                                label: 'Pedoman',
                                value: (
                                    <Link
                                        href={showGuideline(
                                            period.guideline.id,
                                        )}
                                        className="text-primary hover:underline"
                                    >
                                        {period.guideline.name}
                                    </Link>
                                ),
                            },
                            {
                                label: 'Ditutup pada',
                                value: formatDateTime(period.closed_at),
                            },
                            { label: 'Catatan', value: period.notes },
                        ]}
                    />
                </SectionCard>
            </PageBody>
        </>
    );
}

PeriodShow.layout = {
    breadcrumbs: [
        { title: 'Periode ZI', href: index() },
        { title: 'Detail', href: index() },
    ],
};
