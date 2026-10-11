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
import { index, store } from '@/routes/guidelines';
import type { Option } from '@/types';

export default function GuidelineForm({
    copyOptions,
    copyFrom,
}: {
    copyOptions: Option[];
    copyFrom: number | null;
}) {
    return (
        <>
            <Head title="Versi pedoman baru" />
            <PageBody>
                <PageHeader
                    title="Versi pedoman baru"
                    description="Versi baru dibuat sebagai draf. Salin dari versi sebelumnya bila pedoman berubah sebagian, lalu sesuaikan dan berlakukan."
                />
                <Form {...store.form()} className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <FormSection title="Identitas versi">
                                <FormField
                                    id="name"
                                    label="Nama versi"
                                    error={errors.name}
                                    required
                                    hint="Contoh: Pedoman ZI 2026 (versi internal)"
                                >
                                    <Input
                                        name="name"
                                        required
                                        maxLength={255}
                                    />
                                </FormField>
                                <FormField
                                    id="reference"
                                    label="Rujukan regulasi"
                                    error={errors.reference}
                                    hint="Isi sesuai dokumen resmi yang berlaku. Jangan menebak nomor peraturan."
                                >
                                    <Input name="reference" maxLength={255} />
                                </FormField>
                                <FormField
                                    id="description"
                                    label="Deskripsi"
                                    error={errors.description}
                                >
                                    <Textarea name="description" rows={3} />
                                </FormField>
                            </FormSection>
                            <FormSection
                                title="Sumber isi"
                                description="Opsional. Area dan indikator disalin, data versi lama tidak berubah."
                            >
                                <FormField
                                    id="copy_from_id"
                                    label="Salin dari versi"
                                    error={errors.copy_from_id}
                                >
                                    <NativeSelect
                                        name="copy_from_id"
                                        defaultValue={
                                            copyFrom ? String(copyFrom) : ''
                                        }
                                    >
                                        <option value="">
                                            — Mulai dari kosong —
                                        </option>
                                        {copyOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            </FormSection>
                            <FormActions>
                                <Button asChild variant="outline">
                                    <Link href={index()}>Batal</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Buat draf
                                </Button>
                            </FormActions>
                        </>
                    )}
                </Form>
            </PageBody>
        </>
    );
}

GuidelineForm.layout = {
    breadcrumbs: [
        { title: 'Pedoman & Indikator', href: index() },
        { title: 'Versi baru', href: index() },
    ],
};
