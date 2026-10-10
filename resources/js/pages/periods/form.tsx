import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    FormActions,
    FormField,
    FormSection,
} from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { index, show, store, update } from '@/routes/periods';
import type { Option } from '@/types';

type Props = {
    period?: {
        id: number;
        organization_id: number;
        guideline_version_id: number;
        name: string;
        year: number;
        start_date: string | null;
        end_date: string | null;
        notes: string | null;
        can_change_guideline: boolean;
    };
    organizationOptions: Option[];
    guidelineOptions: Option[];
};

export default function PeriodForm({
    period,
    organizationOptions,
    guidelineOptions,
}: Props) {
    const action = period ? update.form(period.id) : store.form();

    return (
        <>
            <Head title={period ? 'Ubah periode' : 'Buat periode'} />
            <PageBody>
                <PageHeader
                    title={period ? `Ubah ${period.name}` : 'Buat periode ZI'}
                    description="Periode baru dibuat sebagai draf. Pedoman yang dipilih menjadi acuan area & indikator untuk seluruh data periode ini."
                />
                {guidelineOptions.length === 0 && (
                    <p
                        role="alert"
                        className="rounded-md border border-warning/30 bg-warning-soft px-3 py-2 text-sm text-warning"
                    >
                        Belum ada pedoman berstatus berlaku. Berlakukan pedoman
                        terlebih dahulu di menu Pedoman & Indikator.
                    </p>
                )}
                <Form {...action} className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <FormSection
                                title="Periode"
                                description="Nama, tahun, dan organisasi pelaksana."
                            >
                                {!period && (
                                    <FormField
                                        id="organization_id"
                                        label="Organisasi pelaksana"
                                        error={errors.organization_id}
                                        required
                                    >
                                        <NativeSelect
                                            name="organization_id"
                                            defaultValue=""
                                            required
                                        >
                                            <option value="" disabled>
                                                Pilih organisasi
                                            </option>
                                            {organizationOptions.map(
                                                (option) => (
                                                    <option
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ),
                                            )}
                                        </NativeSelect>
                                    </FormField>
                                )}
                                <div className="grid gap-4 md:grid-cols-[1fr_140px]">
                                    <FormField
                                        id="name"
                                        label="Nama periode"
                                        error={errors.name}
                                        required
                                        hint="Contoh: Pembangunan ZI 2026"
                                    >
                                        <Input
                                            name="name"
                                            defaultValue={period?.name}
                                            required
                                            maxLength={255}
                                        />
                                    </FormField>
                                    <FormField
                                        id="year"
                                        label="Tahun"
                                        error={errors.year}
                                        required
                                    >
                                        <Input
                                            name="year"
                                            type="number"
                                            min={2000}
                                            max={2100}
                                            defaultValue={
                                                period?.year ??
                                                new Date().getFullYear()
                                            }
                                            required
                                        />
                                    </FormField>
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="start_date"
                                        label="Tanggal mulai"
                                        error={errors.start_date}
                                    >
                                        <Input
                                            name="start_date"
                                            type="date"
                                            defaultValue={
                                                period?.start_date ?? ''
                                            }
                                        />
                                    </FormField>
                                    <FormField
                                        id="end_date"
                                        label="Tanggal selesai"
                                        error={errors.end_date}
                                    >
                                        <Input
                                            name="end_date"
                                            type="date"
                                            defaultValue={
                                                period?.end_date ?? ''
                                            }
                                        />
                                    </FormField>
                                </div>
                            </FormSection>
                            <FormSection
                                title="Pedoman"
                                description="Versi pedoman dikunci setelah periode memiliki program kerja."
                            >
                                <FormField
                                    id="guideline_version_id"
                                    label="Versi pedoman"
                                    error={errors.guideline_version_id}
                                    required
                                    hint={
                                        period && !period.can_change_guideline
                                            ? 'Tidak dapat diganti: periode sudah aktif atau memiliki program kerja.'
                                            : undefined
                                    }
                                >
                                    <NativeSelect
                                        name="guideline_version_id"
                                        defaultValue={
                                            period
                                                ? String(
                                                      period.guideline_version_id,
                                                  )
                                                : ''
                                        }
                                        required
                                        disabled={
                                            period
                                                ? !period.can_change_guideline
                                                : false
                                        }
                                    >
                                        <option value="" disabled>
                                            Pilih pedoman
                                        </option>
                                        {guidelineOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                {period && !period.can_change_guideline && (
                                    <input
                                        type="hidden"
                                        name="guideline_version_id"
                                        value={period.guideline_version_id}
                                    />
                                )}
                                <FormField
                                    id="notes"
                                    label="Catatan"
                                    error={errors.notes}
                                >
                                    <Textarea
                                        name="notes"
                                        defaultValue={period?.notes ?? ''}
                                        rows={3}
                                    />
                                </FormField>
                            </FormSection>
                            <FormActions>
                                <Button asChild variant="outline">
                                    <Link
                                        href={
                                            period ? show(period.id) : index()
                                        }
                                    >
                                        Batal
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Simpan
                                </Button>
                            </FormActions>
                        </>
                    )}
                </Form>
            </PageBody>
        </>
    );
}

PeriodForm.layout = {
    breadcrumbs: [
        { title: 'Periode ZI', href: index() },
        { title: 'Formulir', href: index() },
    ],
};
