import { Head, Link } from '@inertiajs/react';
import { Archive, FileUp, Pencil, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AssignmentPanel } from '@/components/zi/assignment-panel';
import { ConfirmDialog } from '@/components/zi/confirm-dialog';
import { DataTable } from '@/components/zi/data-table';
import { EmptyState } from '@/components/zi/empty-state';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { ProgressBar } from '@/components/zi/progress-bar';
import { DetailList, SectionCard } from '@/components/zi/section-card';
import { OverdueBadge, StatusBadge } from '@/components/zi/status-badge';
import { formatDate, formatDateTime, percent } from '@/lib/format';
import {
    create as createActionPlan,
    show as showActionPlan,
} from '@/routes/action-plans';
import {
    create as createEvidence,
    show as showEvidence,
} from '@/routes/evidence';
import { show as showPeriod } from '@/routes/periods';
import { archive, edit, index } from '@/routes/programs';
import type {
    Assignment,
    EnumValue,
    Option,
    OrganizationBrief,
    UserBrief,
} from '@/types';

type Props = {
    program: {
        id: number;
        code: string | null;
        name: string;
        description: string | null;
        objective: string | null;
        notes: string | null;
        status: EnumValue;
        start_date: string | null;
        target_date: string | null;
        archived_at: string | null;
        period: { id: number; name: string; status: EnumValue };
        organization: OrganizationBrief;
        area: string | null;
        pic: UserBrief | null;
        creator: UserBrief | null;
        indicators: { id: number; label: string }[];
    };
    progress: { total: number; completed: number };
    actionPlans: {
        id: number;
        title: string;
        status: EnumValue;
        progress_percent: number | null;
        due_date: string | null;
        is_overdue: boolean;
        pic: UserBrief | null;
        evidence_count: number;
        archived: boolean;
    }[];
    evidence: {
        id: number;
        title: string;
        category: string;
        status: EnumValue;
        created_at: string;
    }[];
    assignments: Assignment[];
    assignmentTypes: Option[];
    userOptions: Option[];
    can: {
        update: boolean;
        archive: boolean;
        assign: boolean;
        createActionPlan: boolean;
        uploadEvidence: boolean;
    };
};

export default function ProgramShow({
    program,
    progress,
    actionPlans,
    evidence,
    assignments,
    assignmentTypes,
    userOptions,
    can,
}: Props) {
    return (
        <>
            <Head title={program.name} />
            <PageBody>
                <PageHeader
                    title={
                        program.code
                            ? `${program.code} · ${program.name}`
                            : program.name
                    }
                    description={`${program.organization.name} · ${program.area ?? 'Tanpa area'}`}
                    meta={
                        <>
                            <StatusBadge status={program.status} />
                            {program.archived_at && (
                                <StatusBadge
                                    status={{
                                        value: 'archived',
                                        label: `Diarsipkan ${formatDateTime(program.archived_at)}`,
                                    }}
                                />
                            )}
                        </>
                    }
                    actions={
                        <>
                            {can.uploadEvidence && !program.archived_at && (
                                <Button asChild variant="outline">
                                    <Link
                                        href={createEvidence({
                                            query: {
                                                work_program_id: program.id,
                                            },
                                        })}
                                    >
                                        <FileUp aria-hidden="true" />
                                        Unggah bukti
                                    </Link>
                                </Button>
                            )}
                            {can.update && (
                                <Button asChild variant="outline">
                                    <Link href={edit(program.id)}>
                                        <Pencil aria-hidden="true" />
                                        Ubah
                                    </Link>
                                </Button>
                            )}
                            {can.archive && (
                                <ConfirmDialog
                                    trigger={
                                        <Button variant="ghost">
                                            <Archive aria-hidden="true" />
                                            Arsipkan
                                        </Button>
                                    }
                                    title="Arsipkan program kerja?"
                                    description="Program tidak lagi muncul di daftar aktif dan tidak dapat diubah. Rencana aksi, bukti dukung, dan riwayatnya tetap tersimpan."
                                    confirmLabel="Arsipkan"
                                    destructive
                                    action={{ url: archive(program.id).url }}
                                />
                            )}
                        </>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-3">
                    <SectionCard
                        title="Detail program"
                        className="lg:col-span-2"
                    >
                        <DetailList
                            items={[
                                {
                                    label: 'Periode',
                                    value: (
                                        <Link
                                            href={showPeriod(program.period.id)}
                                            className="text-primary hover:underline"
                                        >
                                            {program.period.name} (
                                            {program.period.status.label})
                                        </Link>
                                    ),
                                },
                                {
                                    label: 'Penanggung jawab',
                                    value: program.pic?.name,
                                },
                                {
                                    label: 'Tanggal mulai',
                                    value: formatDate(program.start_date),
                                },
                                {
                                    label: 'Target selesai',
                                    value: formatDate(program.target_date),
                                },
                                { label: 'Tujuan', value: program.objective },
                                {
                                    label: 'Deskripsi',
                                    value: program.description,
                                },
                                { label: 'Catatan', value: program.notes },
                                {
                                    label: 'Dibuat oleh',
                                    value: program.creator?.name,
                                },
                            ]}
                        />
                    </SectionCard>
                    <SectionCard
                        title="Progres"
                        description="Rencana aksi Selesai ÷ rencana aksi yang tidak dibatalkan/diarsipkan."
                    >
                        <ProgressBar
                            label="Penyelesaian rencana aksi"
                            value={percent(progress.completed, progress.total)}
                            detail={`${progress.completed}/${progress.total}`}
                        />
                        <h3 className="mt-4 mb-1 text-sm font-bold">
                            Indikator terkait
                        </h3>
                        {program.indicators.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum dikaitkan dengan indikator.
                            </p>
                        ) : (
                            <ul className="list-disc space-y-1 pl-5 text-sm">
                                {program.indicators.map((indicator) => (
                                    <li key={indicator.id}>
                                        {indicator.label}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SectionCard>
                </div>

                <SectionCard
                    title="Rencana aksi"
                    actions={
                        can.createActionPlan && (
                            <Button asChild size="sm">
                                <Link
                                    href={createActionPlan({
                                        query: { work_program_id: program.id },
                                    })}
                                >
                                    <Plus aria-hidden="true" />
                                    Tambah rencana aksi
                                </Link>
                            </Button>
                        )
                    }
                    contentClassName="p-0"
                >
                    <DataTable
                        caption="Rencana aksi program"
                        rows={actionPlans}
                        rowClassName={(row) =>
                            row.archived ? 'opacity-60' : undefined
                        }
                        empty={
                            <div className="p-4">
                                <EmptyState title="Belum ada rencana aksi" />
                            </div>
                        }
                        columns={[
                            {
                                key: 'title',
                                header: 'Rencana aksi',
                                cell: (row) => (
                                    <Link
                                        href={showActionPlan(row.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {row.title}
                                    </Link>
                                ),
                            },
                            {
                                key: 'pic',
                                header: 'Penanggung jawab',
                                cell: (row) => row.pic?.name ?? '—',
                            },
                            {
                                key: 'due',
                                header: 'Tenggat',
                                cell: (row) => (
                                    <div className="flex flex-col items-start gap-1">
                                        {formatDate(row.due_date)}
                                        {row.is_overdue && <OverdueBadge />}
                                    </div>
                                ),
                            },
                            {
                                key: 'progress',
                                header: 'Progres dilaporkan',
                                cell: (row) =>
                                    row.progress_percent !== null
                                        ? `${row.progress_percent}%`
                                        : '—',
                            },
                            {
                                key: 'evidence',
                                header: 'Bukti',
                                cell: (row) => row.evidence_count,
                                className: 'text-right tabular-nums',
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
                </SectionCard>

                <SectionCard
                    title="Bukti dukung terkait"
                    description="Bukti yang dikaitkan langsung ke program atau ke rencana aksinya (maks. 50 terbaru)."
                    contentClassName="p-0"
                >
                    {evidence.length === 0 ? (
                        <div className="p-4">
                            <EmptyState title="Belum ada bukti dukung" />
                        </div>
                    ) : (
                        <ul className="divide-y">
                            {evidence.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5"
                                >
                                    <div>
                                        <Link
                                            href={showEvidence(item.id)}
                                            className="font-bold text-primary hover:underline"
                                        >
                                            {item.title}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {item.category} ·{' '}
                                            {formatDateTime(item.created_at)}
                                        </p>
                                    </div>
                                    <StatusBadge status={item.status} />
                                </li>
                            ))}
                        </ul>
                    )}
                </SectionCard>

                <AssignmentPanel
                    assignments={assignments}
                    targetType="work_program"
                    targetId={program.id}
                    typeOptions={assignmentTypes}
                    userOptions={userOptions}
                    canAssign={can.assign}
                />
            </PageBody>
        </>
    );
}

ProgramShow.layout = {
    breadcrumbs: [
        { title: 'Program Kerja', href: index() },
        { title: 'Detail', href: index() },
    ],
};
