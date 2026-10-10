import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    ClipboardList,
    FileCheck2,
    FolderKanban,
    RotateCcw,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/zi/empty-state';
import { FilterBar, PeriodFilter } from '@/components/zi/filters';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { ProgressBar } from '@/components/zi/progress-bar';
import { SectionCard } from '@/components/zi/section-card';
import { StatCard } from '@/components/zi/stat-card';
import { OverdueBadge, StatusBadge } from '@/components/zi/status-badge';
import { formatDate, formatDateTime, percent } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    index as actionPlansIndex,
    show as showActionPlan,
} from '@/routes/action-plans';
import {
    index as evidenceIndex,
    show as showEvidence,
} from '@/routes/evidence';
import { index as programsIndex, show as showProgram } from '@/routes/programs';
import { index as tasksIndex } from '@/routes/tasks';
import { index as verificationsIndex } from '@/routes/verifications';
import type {
    EnumValue,
    PeriodOption,
    TaskAssignment,
    UserBrief,
} from '@/types';

type StatusCount = EnumValue & { count: number };

type Props = {
    periods: PeriodOption[];
    selectedPeriod: {
        id: number;
        name: string;
        status: EnumValue;
        organization: string;
    } | null;
    summary: {
        programs: number;
        action_plans: {
            total: number;
            overdue: number;
            by_status: StatusCount[];
        };
        evidence: { total: number; by_status: StatusCount[] };
    } | null;
    areaProgress:
        | {
              area_id: number | null;
              label: string;
              programs: number;
              action_plans_total: number;
              action_plans_completed: number;
              evidence_total: number;
              evidence_accepted: number;
          }[]
        | null;
    attention: {
        overdue: {
            id: number;
            title: string;
            due_date: string;
            pic: UserBrief | null;
            status: EnumValue;
            obstacle_notes: string | null;
        }[];
        revisions: {
            id: number;
            title: string;
            uploader: UserBrief | null;
            updated_at: string;
        }[];
    } | null;
    myWork: MyWork;
    verificationQueue: {
        count: number;
        items: {
            id: number;
            evidence_id: number;
            title: string;
            organization: string;
            stage_name: string;
            version_number: number;
            status: EnumValue;
            created_at: string;
        }[];
    } | null;
};

export type MyWork = {
    plans: {
        id: number;
        title: string;
        program: string;
        due_date: string | null;
        is_overdue: boolean;
        status: EnumValue;
        progress_percent: number | null;
        is_pic: boolean;
    }[];
    myPrograms: {
        id: number;
        name: string;
        status: EnumValue;
        total: number;
        completed: number;
        target_date: string | null;
    }[];
    revisions: {
        id: number;
        title: string;
        status: EnumValue;
        updated_at: string;
    }[];
    assignments: TaskAssignment[];
};

const COMPLETION_DEFINITION =
    'Penyelesaian rencana aksi = rencana aksi berstatus "Selesai" ÷ rencana aksi yang tidak dibatalkan/diarsipkan. "Selesai" adalah status pelaksanaan, bukan hasil verifikasi.';
const ACCEPTANCE_DEFINITION =
    'Bukti diterima = bukti berstatus "Diterima" ÷ seluruh bukti aktif. Ini keputusan verifikasi administratif, bukan penilaian substantif atau predikat ZI.';

export default function Dashboard(props: Props) {
    const {
        periods,
        selectedPeriod,
        summary,
        areaProgress,
        attention,
        myWork,
        verificationQueue,
    } = props;
    const periodQuery = selectedPeriod ? { period_id: selectedPeriod.id } : {};
    const count = (rows: StatusCount[], value: string) =>
        rows.find((row) => row.value === value)?.count ?? 0;

    return (
        <>
            <Head title="Dashboard" />
            <PageBody>
                <PageHeader
                    title="Dashboard"
                    description="Ringkasan dari data aktual periode terpilih, sebatas cakupan organisasi Anda."
                    meta={
                        selectedPeriod && (
                            <StatusBadge status={selectedPeriod.status} />
                        )
                    }
                />

                {periods.length > 0 && (
                    <FilterBar>
                        <PeriodFilter
                            periods={periods}
                            selectedId={selectedPeriod?.id}
                            onChange={(id) =>
                                router.get(
                                    dashboard().url,
                                    { period_id: id },
                                    { preserveScroll: true },
                                )
                            }
                        />
                    </FilterBar>
                )}

                {summary && selectedPeriod && (
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                        <StatCard
                            label="Program kerja"
                            value={summary.programs}
                            icon={FolderKanban}
                            href={programsIndex({ query: periodQuery }).url}
                        />
                        <StatCard
                            label="Rencana aksi"
                            value={summary.action_plans.total}
                            icon={ClipboardList}
                            hint="Tidak termasuk yang diarsipkan"
                            href={actionPlansIndex({ query: periodQuery }).url}
                        />
                        <StatCard
                            label="RA selesai"
                            value={count(
                                summary.action_plans.by_status,
                                'completed',
                            )}
                            icon={CheckCircle2}
                            tone="success"
                            hint="Status pelaksanaan"
                            href={
                                actionPlansIndex({
                                    query: {
                                        ...periodQuery,
                                        status: 'completed',
                                    },
                                }).url
                            }
                        />
                        <StatCard
                            label="RA terlambat"
                            value={summary.action_plans.overdue}
                            icon={AlertTriangle}
                            tone={
                                summary.action_plans.overdue > 0
                                    ? 'danger'
                                    : 'neutral'
                            }
                            hint="Lewat tenggat, belum selesai"
                            href={
                                actionPlansIndex({
                                    query: { ...periodQuery, overdue: 1 },
                                }).url
                            }
                        />
                        <StatCard
                            label="Bukti dukung"
                            value={summary.evidence.total}
                            icon={FileCheck2}
                            href={evidenceIndex({ query: periodQuery }).url}
                        />
                        <StatCard
                            label="Perlu revisi"
                            value={count(
                                summary.evidence.by_status,
                                'revision_required',
                            )}
                            icon={RotateCcw}
                            tone={
                                count(
                                    summary.evidence.by_status,
                                    'revision_required',
                                ) > 0
                                    ? 'warning'
                                    : 'neutral'
                            }
                            href={
                                evidenceIndex({
                                    query: {
                                        ...periodQuery,
                                        status: 'revision_required',
                                    },
                                }).url
                            }
                        />
                    </div>
                )}

                {periods.length === 0 && (
                    <EmptyState
                        title="Belum ada periode ZI dalam cakupan Anda"
                        description="Dashboard pemantauan akan tampil setelah administrator membuat dan mengaktifkan periode ZI."
                    />
                )}

                <div className="grid gap-5 xl:grid-cols-3">
                    {areaProgress && (
                        <SectionCard
                            title="Progres per area"
                            description={COMPLETION_DEFINITION}
                            className="xl:col-span-2"
                        >
                            {areaProgress.length === 0 ? (
                                <EmptyState title="Pedoman periode ini belum memiliki area" />
                            ) : (
                                <ul className="grid gap-4">
                                    {areaProgress.map((row) => (
                                        <li
                                            key={row.area_id ?? 'none'}
                                            className="grid gap-2 border-b pb-3 last:border-0 last:pb-0"
                                        >
                                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                                <p className="font-bold">
                                                    {row.label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {row.programs} program ·{' '}
                                                    {row.action_plans_total}{' '}
                                                    rencana aksi ·{' '}
                                                    {row.evidence_total} bukti
                                                </p>
                                            </div>
                                            <div className="grid gap-2 md:grid-cols-2">
                                                <ProgressBar
                                                    label="Penyelesaian rencana aksi"
                                                    value={percent(
                                                        row.action_plans_completed,
                                                        row.action_plans_total,
                                                    )}
                                                    detail={`${row.action_plans_completed}/${row.action_plans_total}`}
                                                />
                                                <ProgressBar
                                                    label="Bukti diterima"
                                                    value={percent(
                                                        row.evidence_accepted,
                                                        row.evidence_total,
                                                    )}
                                                    detail={`${row.evidence_accepted}/${row.evidence_total}`}
                                                />
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <p className="mt-3 text-xs text-muted-foreground">
                                {ACCEPTANCE_DEFINITION}
                            </p>
                        </SectionCard>
                    )}

                    {summary && (
                        <div className="grid content-start gap-5">
                            <SectionCard title="Status rencana aksi">
                                <StatusBreakdown
                                    rows={summary.action_plans.by_status}
                                />
                            </SectionCard>
                            <SectionCard title="Status verifikasi bukti">
                                <StatusBreakdown
                                    rows={summary.evidence.by_status}
                                />
                            </SectionCard>
                        </div>
                    )}
                </div>

                {verificationQueue && (
                    <SectionCard
                        title="Antrean verifikasi saya"
                        description="Bukti dukung yang menunggu keputusan Anda sesuai tahap dan cakupan organisasi."
                        actions={
                            <Button asChild size="sm" variant="outline">
                                <Link href={verificationsIndex()}>
                                    Buka antrean ({verificationQueue.count})
                                </Link>
                            </Button>
                        }
                    >
                        {verificationQueue.items.length === 0 ? (
                            <EmptyState
                                title="Tidak ada bukti yang menunggu verifikasi Anda"
                                icon={CheckCircle2}
                            />
                        ) : (
                            <ul className="divide-y">
                                {verificationQueue.items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex flex-wrap items-center justify-between gap-2 py-2"
                                    >
                                        <div className="min-w-0">
                                            <Link
                                                href={showEvidence(
                                                    item.evidence_id,
                                                )}
                                                className="font-bold text-primary hover:underline"
                                            >
                                                {item.title}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {item.stage_name} · versi{' '}
                                                {item.version_number} ·{' '}
                                                {item.organization} · diajukan{' '}
                                                {formatDateTime(
                                                    item.created_at,
                                                )}
                                            </p>
                                        </div>
                                        <StatusBadge status={item.status} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SectionCard>
                )}

                {attention && (
                    <div className="grid gap-5 lg:grid-cols-2">
                        <SectionCard
                            title="Rencana aksi terlambat"
                            description="Lewat tenggat dan belum selesai/dibatalkan."
                        >
                            {attention.overdue.length === 0 ? (
                                <EmptyState
                                    title="Tidak ada rencana aksi terlambat"
                                    icon={CheckCircle2}
                                />
                            ) : (
                                <ul className="divide-y">
                                    {attention.overdue.map((plan) => (
                                        <li key={plan.id} className="py-2">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <Link
                                                    href={showActionPlan(
                                                        plan.id,
                                                    )}
                                                    className="font-bold text-primary hover:underline"
                                                >
                                                    {plan.title}
                                                </Link>
                                                <OverdueBadge />
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                Tenggat{' '}
                                                {formatDate(plan.due_date)} ·{' '}
                                                {plan.pic?.name ??
                                                    'Belum ada penanggung jawab'}{' '}
                                                · {plan.status.label}
                                            </p>
                                            {plan.obstacle_notes && (
                                                <p className="mt-1 text-xs">
                                                    Hambatan:{' '}
                                                    {plan.obstacle_notes}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </SectionCard>
                        <SectionCard
                            title="Bukti dukung perlu revisi"
                            description="Menunggu tindak lanjut pengunggah."
                        >
                            {attention.revisions.length === 0 ? (
                                <EmptyState
                                    title="Tidak ada bukti yang perlu revisi"
                                    icon={CheckCircle2}
                                />
                            ) : (
                                <ul className="divide-y">
                                    {attention.revisions.map((item) => (
                                        <li key={item.id} className="py-2">
                                            <Link
                                                href={showEvidence(item.id)}
                                                className="font-bold text-primary hover:underline"
                                            >
                                                {item.title}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {item.uploader?.name} ·
                                                diperbarui{' '}
                                                {formatDateTime(
                                                    item.updated_at,
                                                )}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </SectionCard>
                    </div>
                )}

                <MyWorkSection myWork={myWork} />
            </PageBody>
        </>
    );
}

function StatusBreakdown({ rows }: { rows: StatusCount[] }) {
    return (
        <ul className="grid gap-2">
            {rows.map((row) => (
                <li
                    key={row.value}
                    className="flex items-center justify-between gap-2"
                >
                    <StatusBadge status={row} />
                    <span className="font-bold tabular-nums">{row.count}</span>
                </li>
            ))}
        </ul>
    );
}

export function MyWorkSection({
    myWork,
    compact = true,
}: {
    myWork: MyWork;
    compact?: boolean;
}) {
    return (
        <SectionCard
            title="Pekerjaan saya"
            description="Rencana aksi yang Anda pegang atau ditugaskan kepada Anda, penugasan aktif, dan bukti yang perlu Anda perbaiki."
            actions={
                compact && (
                    <Button asChild size="sm" variant="outline">
                        <Link href={tasksIndex()}>Lihat semua tugas</Link>
                    </Button>
                )
            }
        >
            <div className="grid gap-5 lg:grid-cols-2">
                <div>
                    <h3 className="mb-2 text-sm font-bold">
                        Rencana aksi (tenggat terdekat)
                    </h3>
                    {myWork.plans.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada rencana aksi terbuka.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {myWork.plans.map((plan) => (
                                <li key={plan.id} className="py-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Link
                                            href={showActionPlan(plan.id)}
                                            className="font-bold text-primary hover:underline"
                                        >
                                            {plan.title}
                                        </Link>
                                        <div className="flex gap-1">
                                            {plan.is_overdue && (
                                                <OverdueBadge />
                                            )}
                                            <StatusBadge status={plan.status} />
                                        </div>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {plan.program} · tenggat{' '}
                                        {formatDate(plan.due_date)} ·{' '}
                                        {plan.is_pic
                                            ? 'penanggung jawab'
                                            : 'ditugaskan'}
                                        {plan.progress_percent !== null &&
                                            ` · progres dilaporkan ${plan.progress_percent}%`}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div>
                    <h3 className="mb-2 text-sm font-bold">Penugasan aktif</h3>
                    {myWork.assignments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada penugasan aktif.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {myWork.assignments.map((assignment) => (
                                <li key={assignment.id} className="py-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        {assignment.url ? (
                                            <Link
                                                href={assignment.url}
                                                className="font-bold text-primary hover:underline"
                                            >
                                                {assignment.target}
                                            </Link>
                                        ) : (
                                            <span className="font-bold">
                                                {assignment.target}
                                            </span>
                                        )}
                                        {assignment.is_overdue && (
                                            <OverdueBadge />
                                        )}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {assignment.type.label} · dari{' '}
                                        {assignment.assigner?.name ?? 'sistem'}
                                        {assignment.due_date &&
                                            ` · tenggat ${formatDate(assignment.due_date)}`}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div>
                    <h3 className="mb-2 text-sm font-bold">
                        Bukti saya yang perlu ditindaklanjuti
                    </h3>
                    {myWork.revisions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada bukti yang perlu diperbaiki.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {myWork.revisions.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex flex-wrap items-center justify-between gap-2 py-2"
                                >
                                    <Link
                                        href={showEvidence(item.id)}
                                        className="font-bold text-primary hover:underline"
                                    >
                                        {item.title}
                                    </Link>
                                    <StatusBadge status={item.status} />
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div>
                    <h3 className="mb-2 text-sm font-bold">
                        Program yang saya pegang
                    </h3>
                    {myWork.myPrograms.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Anda belum menjadi penanggung jawab program.
                        </p>
                    ) : (
                        <ul className="grid gap-3">
                            {myWork.myPrograms.map((program) => (
                                <li key={program.id} className="grid gap-1">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Link
                                            href={showProgram(program.id)}
                                            className="font-bold text-primary hover:underline"
                                        >
                                            {program.name}
                                        </Link>
                                        <StatusBadge status={program.status} />
                                    </div>
                                    <ProgressBar
                                        label="Penyelesaian rencana aksi"
                                        value={percent(
                                            program.completed,
                                            program.total,
                                        )}
                                        detail={`${program.completed}/${program.total}`}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </SectionCard>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
