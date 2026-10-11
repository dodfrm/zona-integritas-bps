import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/zi/empty-state';
import {
    FormActions,
    FormField,
    FormSection,
} from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { create, index, show, store, update } from '@/routes/evidence';
import type { AreaOption, Option } from '@/types';

type Props = {
    item?: {
        id: number;
        indicator_id: number | null;
        work_program_id: number | null;
        action_plan_id: number | null;
        title: string;
        description: string | null;
        category: string;
        notes: string | null;
    };
    defaults?: {
        work_program_id: number | null;
        action_plan_id: number | null;
        indicator_id: number | null;
    };
    periodOptions: Option[];
    selectedPeriodId: number | null;
    areas: AreaOption[];
    programOptions: Option[];
    actionPlanOptions: (Option & { work_program_id: string })[];
    categoryOptions: Option[];
    upload: { max_kb: number; extensions: string[] };
};

export default function EvidenceForm({
    item,
    defaults,
    periodOptions,
    selectedPeriodId,
    areas,
    programOptions,
    actionPlanOptions,
    categoryOptions,
    upload,
}: Props) {
    const initial = item ?? defaults;
    const [programId, setProgramId] = useState(
        initial?.work_program_id ? String(initial.work_program_id) : '',
    );
    const [planId, setPlanId] = useState(
        initial?.action_plan_id ? String(initial.action_plan_id) : '',
    );
    const action = item ? update.form(item.id) : store.form();
    const plans = programId
        ? actionPlanOptions.filter((plan) => plan.work_program_id === programId)
        : actionPlanOptions;

    if (!item && periodOptions.length === 0) {
        return (
            <PageBody>
                <PageHeader title="Unggah bukti dukung" />
                <EmptyState
                    title="Tidak ada periode yang dapat diisi"
                    description="Bukti dukung hanya dapat diunggah pada periode draf/aktif dalam cakupan Anda."
                />
            </PageBody>
        );
    }

    return (
        <>
            <Head
                title={item ? 'Ubah metadata bukti' : 'Unggah bukti dukung'}
            />
            <PageBody>
                <PageHeader
                    title={
                        item
                            ? `Ubah metadata: ${item.title}`
                            : 'Unggah bukti dukung'
                    }
                    description={
                        item
                            ? 'Mengubah metadata tidak mengganti berkas. Untuk mengganti berkas, unggah versi baru dari halaman detail.'
                            : 'Setelah diunggah, bukti otomatis diajukan untuk verifikasi sesuai alur yang aktif.'
                    }
                />
                <Form {...action} className="grid gap-4">
                    {({ errors, processing, progress }) => (
                        <>
                            <FormSection
                                title="Keterkaitan"
                                description="Kaitkan minimal dengan satu indikator, program, atau rencana aksi pada periode yang sama."
                            >
                                <FormField
                                    id="period_id"
                                    label="Periode ZI"
                                    error={errors.period_id}
                                    required
                                >
                                    <NativeSelect
                                        name={item ? undefined : 'period_id'}
                                        value={String(selectedPeriodId ?? '')}
                                        disabled={Boolean(item)}
                                        onChange={(event) =>
                                            router.get(create().url, {
                                                period_id: event.target.value,
                                            })
                                        }
                                    >
                                        {periodOptions.map((option) => (
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
                                    id="indicator_id"
                                    label="Indikator"
                                    error={errors.indicator_id}
                                >
                                    <NativeSelect
                                        name="indicator_id"
                                        defaultValue={
                                            initial?.indicator_id
                                                ? String(initial.indicator_id)
                                                : ''
                                        }
                                    >
                                        <option value="">
                                            — Tidak dikaitkan —
                                        </option>
                                        {areas.map((area) => (
                                            <optgroup
                                                key={area.value}
                                                label={area.label}
                                            >
                                                {area.indicators.map(
                                                    (indicator) => (
                                                        <option
                                                            key={
                                                                indicator.value
                                                            }
                                                            value={
                                                                indicator.value
                                                            }
                                                        >
                                                            {indicator.is_sub
                                                                ? '— '
                                                                : ''}
                                                            {indicator.label}
                                                        </option>
                                                    ),
                                                )}
                                            </optgroup>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="work_program_id"
                                        label="Program kerja"
                                        error={errors.work_program_id}
                                    >
                                        <NativeSelect
                                            name="work_program_id"
                                            value={programId}
                                            onChange={(event) => {
                                                setProgramId(
                                                    event.target.value,
                                                );
                                                setPlanId('');
                                            }}
                                        >
                                            <option value="">
                                                — Tidak dikaitkan —
                                            </option>
                                            {programOptions.map((option) => (
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
                                        id="action_plan_id"
                                        label="Rencana aksi"
                                        error={errors.action_plan_id}
                                    >
                                        <NativeSelect
                                            name="action_plan_id"
                                            value={planId}
                                            onChange={(event) =>
                                                setPlanId(event.target.value)
                                            }
                                        >
                                            <option value="">
                                                — Tidak dikaitkan —
                                            </option>
                                            {plans.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </FormField>
                                </div>
                            </FormSection>

                            <FormSection title="Metadata">
                                <div className="grid gap-4 md:grid-cols-[1fr_240px]">
                                    <FormField
                                        id="title"
                                        label="Judul bukti"
                                        error={errors.title}
                                        required
                                    >
                                        <Input
                                            name="title"
                                            defaultValue={item?.title}
                                            required
                                            maxLength={255}
                                        />
                                    </FormField>
                                    <FormField
                                        id="category"
                                        label="Jenis bukti"
                                        error={errors.category}
                                        required
                                    >
                                        <NativeSelect
                                            name="category"
                                            defaultValue={item?.category ?? ''}
                                            required
                                        >
                                            <option value="" disabled>
                                                Pilih jenis
                                            </option>
                                            {categoryOptions.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </FormField>
                                </div>
                                <FormField
                                    id="description"
                                    label="Deskripsi"
                                    error={errors.description}
                                >
                                    <Textarea
                                        name="description"
                                        defaultValue={item?.description ?? ''}
                                        rows={3}
                                    />
                                </FormField>
                                <FormField
                                    id="notes"
                                    label="Catatan"
                                    error={errors.notes}
                                >
                                    <Textarea
                                        name="notes"
                                        defaultValue={item?.notes ?? ''}
                                        rows={2}
                                    />
                                </FormField>
                            </FormSection>

                            {!item && (
                                <FormSection
                                    title="Berkas"
                                    description="Tipe dan ukuran diperiksa ulang di server berdasarkan isi berkas."
                                >
                                    <FormField
                                        id="file"
                                        label="Berkas bukti"
                                        error={errors.file}
                                        required
                                        hint={`Format: ${upload.extensions.join(', ').toUpperCase()}. Maks. ${Math.round(upload.max_kb / 1024)} MB.`}
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
                                </FormSection>
                            )}

                            <FormActions>
                                <Button asChild variant="outline">
                                    <Link href={item ? show(item.id) : index()}>
                                        Batal
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {item
                                        ? 'Simpan metadata'
                                        : 'Unggah & ajukan verifikasi'}
                                </Button>
                            </FormActions>
                        </>
                    )}
                </Form>
            </PageBody>
        </>
    );
}

EvidenceForm.layout = {
    breadcrumbs: [
        { title: 'Bukti Dukung', href: index() },
        { title: 'Formulir', href: index() },
    ],
};
