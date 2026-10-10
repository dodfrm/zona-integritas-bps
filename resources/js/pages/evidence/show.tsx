import { Form, Head, Link } from '@inertiajs/react';
import {
    Archive,
    Download,
    FileUp,
    Pencil,
    Play,
    UserCheck,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { AssignmentPanel } from '@/components/zi/assignment-panel';
import { ConfirmDialog } from '@/components/zi/confirm-dialog';
import { FormField } from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { DetailList, SectionCard } from '@/components/zi/section-card';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatBytes, formatDateTime } from '@/lib/format';
import { show as showActionPlan } from '@/routes/action-plans';
import { archive, edit, index } from '@/routes/evidence';
import { download, store as storeVersion } from '@/routes/evidence/versions';
import { show as showProgram } from '@/routes/programs';
import { assign, decide, start } from '@/routes/reviews';
import type {
    Assignment,
    EnumValue,
    Option,
    OrganizationBrief,
    UserBrief,
} from '@/types';

type Version = {
    id: number;
    version_number: number;
    original_name: string;
    mime_type: string;
    size: number;
    checksum: string;
    change_note: string | null;
    uploader: UserBrief | null;
    created_at: string;
};

type Review = {
    id: number;
    version_number: number | null;
    stage_name: string;
    stage_sequence: number;
    executor_level: EnumValue;
    executor_organization: string;
    status: EnumValue;
    assigned_verifier: UserBrief | null;
    decider: UserBrief | null;
    decided_at: string | null;
    started_at: string | null;
    decision_note: string | null;
};

type Props = {
    item: {
        id: number;
        title: string;
        description: string | null;
        notes: string | null;
        category: EnumValue;
        status: EnumValue;
        archived_at: string | null;
        created_at: string;
        period: { id: number; name: string; status: EnumValue };
        organization: OrganizationBrief;
        indicator: { label: string; area: string } | null;
        program: { id: number; name: string } | null;
        action_plan: { id: number; title: string } | null;
        uploader: UserBrief | null;
    };
    versions: Version[];
    reviews: Review[];
    openReview: {
        id: number;
        stage_name: string;
        status: EnumValue;
        can_start: boolean;
        decisions: EnumValue[];
        separation_blocked: boolean;
        verifier_options: Option[];
    } | null;
    assignments: Assignment[];
    upload: { max_kb: number; extensions: string[] };
    can: { update: boolean; addVersion: boolean; archive: boolean };
};

export default function EvidenceShow({
    item,
    versions,
    reviews,
    openReview,
    assignments,
    upload,
    can,
}: Props) {
    const byVersion = versions.map((version) => ({
        version,
        reviews: reviews.filter(
            (review) => review.version_number === version.version_number,
        ),
    }));

    return (
        <>
            <Head title={item.title} />
            <PageBody>
                <PageHeader
                    title={item.title}
                    description={`${item.category.label} · ${item.organization.name} · ${item.period.name}`}
                    meta={
                        <>
                            <StatusBadge status={item.status} />
                            {item.archived_at && (
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
                            {can.update && (
                                <Button asChild variant="outline">
                                    <Link href={edit(item.id)}>
                                        <Pencil aria-hidden="true" />
                                        Ubah metadata
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
                                    title="Arsipkan bukti dukung?"
                                    description="Bukti tidak lagi muncul di daftar aktif dan antrean verifikasi. Seluruh versi berkas dan riwayat verifikasi tetap tersimpan."
                                    confirmLabel="Arsipkan"
                                    destructive
                                    action={{ url: archive(item.id).url }}
                                />
                            )}
                        </>
                    }
                />

                {openReview && <ReviewPanel review={openReview} />}

                <div className="grid gap-5 lg:grid-cols-3">
                    <SectionCard title="Metadata" className="lg:col-span-2">
                        <DetailList
                            items={[
                                {
                                    label: 'Indikator',
                                    value: item.indicator
                                        ? `${item.indicator.label} (${item.indicator.area})`
                                        : null,
                                },
                                {
                                    label: 'Program kerja',
                                    value: item.program && (
                                        <Link
                                            href={showProgram(item.program.id)}
                                            className="text-primary hover:underline"
                                        >
                                            {item.program.name}
                                        </Link>
                                    ),
                                },
                                {
                                    label: 'Rencana aksi',
                                    value: item.action_plan && (
                                        <Link
                                            href={showActionPlan(
                                                item.action_plan.id,
                                            )}
                                            className="text-primary hover:underline"
                                        >
                                            {item.action_plan.title}
                                        </Link>
                                    ),
                                },
                                {
                                    label: 'Pengunggah awal',
                                    value: item.uploader?.name,
                                },
                                {
                                    label: 'Diunggah',
                                    value: formatDateTime(item.created_at),
                                },
                                {
                                    label: 'Periode',
                                    value: `${item.period.name} (${item.period.status.label})`,
                                },
                                { label: 'Deskripsi', value: item.description },
                                { label: 'Catatan', value: item.notes },
                            ]}
                        />
                    </SectionCard>
                    {can.addVersion ? (
                        <NewVersionCard
                            evidenceId={item.id}
                            upload={upload}
                            needsRevision={[
                                'revision_required',
                                'rejected',
                            ].includes(item.status.value)}
                        />
                    ) : (
                        <SectionCard title="Versi baru">
                            <p className="text-sm text-muted-foreground">
                                {item.status.value === 'in_review'
                                    ? 'Berkas tidak dapat diganti saat sedang diverifikasi.'
                                    : 'Anda tidak berwenang mengganti berkas bukti ini, atau periode/bukti sudah ditutup.'}
                            </p>
                        </SectionCard>
                    )}
                </div>

                <SectionCard
                    title="Riwayat versi & verifikasi"
                    description="Setiap versi berkas disimpan; keputusan verifikasi melekat pada versi yang diperiksa dan tidak pernah ditimpa."
                    contentClassName="p-0"
                >
                    <ol className="divide-y">
                        {byVersion.map(
                            ({ version, reviews: versionReviews }) => (
                                <li
                                    key={version.id}
                                    className="grid gap-3 p-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]"
                                >
                                    <div className="min-w-0">
                                        <p className="font-bold">
                                            Versi {version.version_number}
                                        </p>
                                        <p
                                            className="truncate text-sm"
                                            title={version.original_name}
                                        >
                                            {version.original_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatBytes(version.size)} ·{' '}
                                            {version.uploader?.name} ·{' '}
                                            {formatDateTime(version.created_at)}
                                        </p>
                                        {version.change_note && (
                                            <p className="mt-1 text-xs">
                                                Catatan perubahan:{' '}
                                                {version.change_note}
                                            </p>
                                        )}
                                        <p
                                            className="mt-1 truncate font-mono text-[11px] text-muted-foreground"
                                            title={version.checksum}
                                        >
                                            SHA-256 {version.checksum}
                                        </p>
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                            className="mt-2"
                                        >
                                            <a
                                                href={
                                                    download({
                                                        evidence: item.id,
                                                        version: version.id,
                                                    }).url
                                                }
                                            >
                                                <Download aria-hidden="true" />
                                                Unduh versi{' '}
                                                {version.version_number}
                                            </a>
                                        </Button>
                                    </div>
                                    <ol className="grid content-start gap-2">
                                        {versionReviews.length === 0 && (
                                            <li className="text-sm text-muted-foreground">
                                                Tidak ada tahap verifikasi.
                                            </li>
                                        )}
                                        {versionReviews.map((review) => (
                                            <li
                                                key={review.id}
                                                className="rounded-md border p-2.5 text-sm"
                                            >
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <p className="font-bold">
                                                        {review.stage_sequence}.{' '}
                                                        {review.stage_name}
                                                    </p>
                                                    <StatusBadge
                                                        status={review.status}
                                                    />
                                                </div>
                                                <p className="text-xs text-muted-foreground">
                                                    Pelaksana:{' '}
                                                    {
                                                        review.executor_organization
                                                    }
                                                    {review.assigned_verifier &&
                                                        ` · ditugaskan ke ${review.assigned_verifier.name}`}
                                                </p>
                                                {review.decider && (
                                                    <p className="text-xs">
                                                        Diputuskan oleh{' '}
                                                        <span className="font-bold">
                                                            {
                                                                review.decider
                                                                    .name
                                                            }
                                                        </span>{' '}
                                                        ·{' '}
                                                        {formatDateTime(
                                                            review.decided_at,
                                                        )}
                                                    </p>
                                                )}
                                                {review.decision_note && (
                                                    <p className="mt-1 rounded bg-muted px-2 py-1 text-xs whitespace-pre-line">
                                                        {review.decision_note}
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ol>
                                </li>
                            ),
                        )}
                    </ol>
                </SectionCard>

                <AssignmentPanel
                    assignments={assignments}
                    targetType="evidence"
                    targetId={item.id}
                    typeOptions={[]}
                    userOptions={[]}
                    canAssign={false}
                />
            </PageBody>
        </>
    );
}

function ReviewPanel({ review }: { review: NonNullable<Props['openReview']> }) {
    const [decision, setDecision] = useState(review.decisions[0]?.value ?? '');
    const canDecide = review.decisions.length > 0;

    return (
        <SectionCard
            title={`Verifikasi: ${review.stage_name}`}
            description="Keputusan administratif atas kelengkapan bukti, bukan penilaian substantif atau penetapan predikat ZI."
            className="border-primary/40"
            actions={<StatusBadge status={review.status} />}
        >
            {review.separation_blocked && (
                <p className="mb-3 rounded-md border border-warning/30 bg-warning-soft px-3 py-2 text-sm text-warning">
                    Anda mengunggah bukti/versi ini sehingga tidak dapat
                    memverifikasinya (pemisahan tugas).
                </p>
            )}
            {!canDecide && !review.can_start && !review.separation_blocked && (
                <p className="text-sm text-muted-foreground">
                    Menunggu verifikator yang berwenang pada tahap ini.
                </p>
            )}
            <div className="flex flex-col gap-4">
                {review.can_start && (
                    <ConfirmDialog
                        trigger={
                            <Button variant="outline" className="self-start">
                                <Play aria-hidden="true" />
                                Mulai verifikasi
                            </Button>
                        }
                        title="Mulai verifikasi?"
                        description="Status bukti menjadi Sedang diverifikasi; pengunggah tidak dapat mengganti berkas sampai keputusan diberikan."
                        confirmLabel="Mulai"
                        action={{ url: start(review.id).url }}
                    />
                )}
                {canDecide && (
                    <Form
                        {...decide.form(review.id)}
                        options={{ preserveScroll: true }}
                        className="grid gap-3"
                    >
                        {({ errors, processing }) => (
                            <>
                                <fieldset className="grid gap-2">
                                    <legend className="mb-1 text-sm font-bold">
                                        Keputusan
                                    </legend>
                                    <div className="flex flex-wrap gap-2">
                                        {review.decisions.map((option) => (
                                            <label
                                                key={option.value}
                                                className={`flex items-center gap-2 rounded-md border px-3 py-2 text-sm ${decision === option.value ? 'border-primary bg-primary/5 font-bold' : ''}`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="decision"
                                                    value={option.value}
                                                    checked={
                                                        decision ===
                                                        option.value
                                                    }
                                                    onChange={() =>
                                                        setDecision(
                                                            option.value,
                                                        )
                                                    }
                                                    className="size-4 accent-primary"
                                                />
                                                {option.label}
                                            </label>
                                        ))}
                                    </div>
                                    {errors.decision && (
                                        <p
                                            role="alert"
                                            className="text-sm font-bold text-danger"
                                        >
                                            {errors.decision}
                                        </p>
                                    )}
                                </fieldset>
                                <FormField
                                    id="review-note"
                                    label="Catatan verifikator"
                                    error={errors.note}
                                    required={decision !== 'accepted'}
                                    hint="Wajib untuk permintaan revisi atau penolakan. Catatan tersimpan permanen sebagai riwayat."
                                >
                                    <Textarea
                                        name="note"
                                        rows={3}
                                        required={decision !== 'accepted'}
                                    />
                                </FormField>
                                <div>
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Simpan keputusan
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                )}
                {review.verifier_options.length > 0 && (
                    <AssignVerifierDialog
                        reviewId={review.id}
                        options={review.verifier_options}
                    />
                )}
            </div>
        </SectionCard>
    );
}

function AssignVerifierDialog({
    reviewId,
    options,
}: {
    reviewId: number;
    options: Option[];
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" className="self-start">
                    <UserCheck aria-hidden="true" />
                    Tetapkan verifikator
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Tetapkan verifikator tahap ini</DialogTitle>
                    <DialogDescription>
                        Hanya verifikator yang memenuhi syarat (organisasi
                        pelaksana, cakupan, dan pemisahan tugas) yang
                        ditampilkan.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...assign.form(reviewId)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="verifier"
                                label="Verifikator"
                                error={errors.assignee_id}
                                required
                            >
                                <NativeSelect
                                    name="assignee_id"
                                    defaultValue=""
                                    required
                                >
                                    <option value="" disabled>
                                        Pilih verifikator
                                    </option>
                                    {options.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Tetapkan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function NewVersionCard({
    evidenceId,
    upload,
    needsRevision,
}: {
    evidenceId: number;
    upload: Props['upload'];
    needsRevision: boolean;
}) {
    return (
        <SectionCard
            title={needsRevision ? 'Unggah perbaikan' : 'Unggah versi baru'}
            description="Versi lama tetap tersimpan. Verifikasi dimulai ulang dari tahap pertama untuk versi baru."
        >
            <Form
                {...storeVersion.form(evidenceId)}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="grid gap-3"
            >
                {({ errors, processing, progress }) => (
                    <>
                        <FormField
                            id="version-file"
                            label="Berkas"
                            error={errors.file}
                            required
                            hint={`${upload.extensions.join(', ').toUpperCase()} · maks. ${Math.round(upload.max_kb / 1024)} MB`}
                        >
                            <Input
                                name="file"
                                type="file"
                                required
                                accept={upload.extensions
                                    .map((ext) => `.${ext}`)
                                    .join(',')}
                                className="h-auto py-1.5"
                            />
                        </FormField>
                        <FormField
                            id="change_note"
                            label="Catatan perubahan"
                            error={errors.change_note}
                            required
                        >
                            <Textarea
                                name="change_note"
                                rows={2}
                                required
                                placeholder="Apa yang diperbaiki/diubah?"
                            />
                        </FormField>
                        {progress && (
                            <progress
                                value={progress.percentage}
                                max={100}
                                className="w-full"
                                aria-label="Progres unggah"
                            >
                                {progress.percentage}%
                            </progress>
                        )}
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <Spinner />
                            ) : (
                                <FileUp aria-hidden="true" />
                            )}
                            Unggah versi
                        </Button>
                    </>
                )}
            </Form>
        </SectionCard>
    );
}

EvidenceShow.layout = {
    breadcrumbs: [
        { title: 'Bukti Dukung', href: index() },
        { title: 'Detail', href: index() },
    ],
};
