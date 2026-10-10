import { Form, Head, Link } from '@inertiajs/react';
import { Archive, FileUp, Pencil } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { AssignmentPanel } from '@/components/zi/assignment-panel';
import { ConfirmDialog } from '@/components/zi/confirm-dialog';
import { EmptyState } from '@/components/zi/empty-state';
import { FormField } from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { DetailList, SectionCard } from '@/components/zi/section-card';
import { OverdueBadge, StatusBadge } from '@/components/zi/status-badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { archive, edit, index, progress } from '@/routes/action-plans';
import {
    create as createEvidence,
    show as showEvidence,
} from '@/routes/evidence';
import { show as showProgram } from '@/routes/programs';
import type {
    Assignment,
    EnumValue,
    Option,
    OrganizationBrief,
    UserBrief,
} from '@/types';

type Props = {
    plan: {
        id: number;
        title: string;
        description: string | null;
        output_target: string | null;
        status: EnumValue;
        progress_percent: number | null;
        obstacle_notes: string | null;
        start_date: string | null;
        due_date: string | null;
        completed_at: string | null;
        archived_at: string | null;
        is_overdue: boolean;
        program: { id: number; name: string };
        area: string | null;
        indicator: string | null;
        period: { id: number; name: string; status: EnumValue };
        organization: OrganizationBrief;
        pic: UserBrief | null;
    };
    history: {
        id: number;
        from: EnumValue | null;
        to: EnumValue;
        progress_percent: number | null;
        note: string | null;
        changer: UserBrief | null;
        created_at: string;
    }[];
    evidence: {
        id: number;
        title: string;
        category: string;
        status: EnumValue;
        uploader: UserBrief | null;
        created_at: string;
    }[];
    evidenceSummary: (EnumValue & { count: number })[];
    assignments: Assignment[];
    assignmentTypes: Option[];
    userOptions: Option[];
    statusOptions: Option[];
    can: {
        update: boolean;
        updateProgress: boolean;
        archive: boolean;
        assign: boolean;
        uploadEvidence: boolean;
    };
};

export default function ActionPlanShow({
    plan,
    history,
    evidence,
    evidenceSummary,
    assignments,
    assignmentTypes,
    userOptions,
    statusOptions,
    can,
}: Props) {
    return (
        <>
            <Head title={plan.title} />
            <PageBody>
                <PageHeader
                    title={plan.title}
                    description={
                        <>
                            Program:{' '}
                            <Link
                                href={showProgram(plan.program.id)}
                                className="text-primary hover:underline"
                            >
                                {plan.program.name}
                            </Link>{' '}
                            · {plan.organization.name}
                        </>
                    }
                    meta={
                        <>
                            <StatusBadge status={plan.status} />
                            {plan.is_overdue && <OverdueBadge />}
                            {plan.archived_at && (
                                <StatusBadge
                                    status={{
                                        value: 'archived',
                                        label: 'Diarsipkan',
                                    }}
                                />
                            )}
                        </>
                    }
                    actions={
                        <>
                            {can.uploadEvidence && !plan.archived_at && (
                                <Button asChild>
                                    <Link
                                        href={createEvidence({
                                            query: { action_plan_id: plan.id },
                                        })}
                                    >
                                        <FileUp aria-hidden="true" />
                                        Unggah bukti dukung
                                    </Link>
                                </Button>
                            )}
                            {can.update && (
                                <Button asChild variant="outline">
                                    <Link href={edit(plan.id)}>
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
                                    title="Arsipkan rencana aksi?"
                                    description="Rencana aksi tidak dapat diubah lagi. Riwayat status dan bukti dukung tetap tersimpan."
                                    confirmLabel="Arsipkan"
                                    destructive
                                    action={{ url: archive(plan.id).url }}
                                />
                            )}
                        </>
                    }
                />

                <div className="grid gap-5 lg:grid-cols-3">
                    <SectionCard
                        title="Detail rencana aksi"
                        className="lg:col-span-2"
                    >
                        <DetailList
                            items={[
                                {
                                    label: 'Periode',
                                    value: `${plan.period.name} (${plan.period.status.label})`,
                                },
                                { label: 'Area', value: plan.area },
                                { label: 'Indikator', value: plan.indicator },
                                {
                                    label: 'Penanggung jawab',
                                    value: plan.pic?.name,
                                },
                                {
                                    label: 'Tanggal mulai',
                                    value: formatDate(plan.start_date),
                                },
                                {
                                    label: 'Tenggat',
                                    value: formatDate(plan.due_date),
                                },
                                {
                                    label: 'Target keluaran',
                                    value: plan.output_target,
                                },
                                { label: 'Deskripsi', value: plan.description },
                                {
                                    label: 'Progres dilaporkan',
                                    value:
                                        plan.progress_percent !== null
                                            ? `${plan.progress_percent}% (dilaporkan pelaksana)`
                                            : null,
                                },
                                {
                                    label: 'Selesai pada',
                                    value: formatDateTime(plan.completed_at),
                                },
                                {
                                    label: 'Catatan hambatan',
                                    value: plan.obstacle_notes,
                                },
                            ]}
                        />
                    </SectionCard>
                    <SectionCard
                        title="Ringkasan bukti dukung"
                        description="Status verifikasi bukti terkait rencana aksi ini."
                    >
                        {evidenceSummary.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada bukti dukung.
                            </p>
                        ) : (
                            <ul className="grid gap-2">
                                {evidenceSummary.map((row) => (
                                    <li
                                        key={row.value}
                                        className="flex items-center justify-between"
                                    >
                                        <StatusBadge status={row} />
                                        <span className="font-bold tabular-nums">
                                            {row.count}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </SectionCard>
                </div>

                {can.updateProgress && (
                    <SectionCard
                        title="Perbarui pelaksanaan"
                        description="Setiap perubahan status/progres tercatat di riwayat. Status Selesai bukan berarti bukti telah diverifikasi."
                    >
                        <Form
                            {...progress.form(plan.id)}
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['note']}
                            className="grid gap-4"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-4 md:grid-cols-[220px_160px_1fr]">
                                        <FormField
                                            id="status"
                                            label="Status pelaksanaan"
                                            error={errors.status}
                                            required
                                        >
                                            <NativeSelect
                                                name="status"
                                                defaultValue={plan.status.value}
                                                required
                                            >
                                                {statusOptions.map((option) => (
                                                    <option
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        </FormField>
                                        <FormField
                                            id="progress_percent"
                                            label="Progres (%)"
                                            error={errors.progress_percent}
                                            hint="Opsional, 0–100."
                                        >
                                            <Input
                                                name="progress_percent"
                                                type="number"
                                                min={0}
                                                max={100}
                                                defaultValue={
                                                    plan.progress_percent ?? ''
                                                }
                                            />
                                        </FormField>
                                        <FormField
                                            id="note"
                                            label="Catatan perubahan"
                                            error={errors.note}
                                        >
                                            <Input
                                                name="note"
                                                maxLength={2000}
                                                placeholder="Mis. rapat koordinasi telah dilaksanakan"
                                            />
                                        </FormField>
                                    </div>
                                    <FormField
                                        id="obstacle_notes"
                                        label="Catatan hambatan"
                                        error={errors.obstacle_notes}
                                    >
                                        <Textarea
                                            name="obstacle_notes"
                                            defaultValue={
                                                plan.obstacle_notes ?? ''
                                            }
                                            rows={2}
                                        />
                                    </FormField>
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Simpan pembaruan
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </SectionCard>
                )}

                <div className="grid gap-5 lg:grid-cols-2">
                    <SectionCard title="Bukti dukung" contentClassName="p-0">
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
                                                {item.uploader?.name} ·{' '}
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
                    <SectionCard title="Riwayat status" contentClassName="p-0">
                        <ol className="divide-y">
                            {history.map((item) => (
                                <li
                                    key={item.id}
                                    className="px-4 py-2.5 text-sm"
                                >
                                    <div className="flex flex-wrap items-center gap-1">
                                        {item.from && (
                                            <>
                                                <StatusBadge
                                                    status={item.from}
                                                />
                                                <span aria-hidden="true">
                                                    →
                                                </span>
                                                <span className="sr-only">
                                                    menjadi
                                                </span>
                                            </>
                                        )}
                                        <StatusBadge status={item.to} />
                                        {item.progress_percent !== null && (
                                            <span className="text-xs">
                                                ({item.progress_percent}%)
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {item.changer?.name ?? 'Sistem'} ·{' '}
                                        {formatDateTime(item.created_at)}
                                    </p>
                                    {item.note && (
                                        <p className="mt-0.5 text-xs">
                                            {item.note}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </SectionCard>
                </div>

                <AssignmentPanel
                    assignments={assignments}
                    targetType="action_plan"
                    targetId={plan.id}
                    typeOptions={assignmentTypes}
                    userOptions={userOptions}
                    canAssign={can.assign}
                />
            </PageBody>
        </>
    );
}

ActionPlanShow.layout = {
    breadcrumbs: [
        { title: 'Rencana Aksi', href: index() },
        { title: 'Detail', href: index() },
    ],
};
