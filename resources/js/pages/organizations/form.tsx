import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import {
    FormActions,
    FormField,
    FormSection,
} from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { index, store, update } from '@/routes/organizations';
import type { Option, OrganizationBrief } from '@/types';

type Props = {
    organization?: OrganizationBrief & {
        parent_id: number | null;
        is_active: boolean;
    };
    typeOptions: Option[];
    parentOptions: Option[];
    canCreateRoot: boolean;
};

export default function OrganizationForm({
    organization,
    typeOptions,
    parentOptions,
    canCreateRoot,
}: Props) {
    const isEdit = Boolean(organization);
    const action = organization ? update.form(organization.id) : store.form();

    return (
        <>
            <Head title={isEdit ? 'Ubah organisasi' : 'Tambah organisasi'} />
            <PageBody>
                <PageHeader
                    title={
                        isEdit
                            ? `Ubah ${organization?.name}`
                            : 'Tambah organisasi'
                    }
                    description="Gunakan relasi induk untuk membentuk hierarki. Jangan mengisi kode resmi bila belum dikonfirmasi."
                />
                <Form {...action} className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <FormSection
                                title="Identitas"
                                description="Nama dan tipe organisasi."
                            >
                                <FormField
                                    id="name"
                                    label="Nama organisasi"
                                    error={errors.name}
                                    required
                                >
                                    <Input
                                        name="name"
                                        defaultValue={organization?.name}
                                        required
                                        maxLength={255}
                                    />
                                </FormField>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="type"
                                        label="Tipe"
                                        error={errors.type}
                                        required
                                    >
                                        <NativeSelect
                                            name="type"
                                            defaultValue={
                                                organization?.type ?? 'kabkota'
                                            }
                                            required
                                        >
                                            {typeOptions.map((option) => (
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
                                        id="code"
                                        label="Kode organisasi"
                                        error={errors.code}
                                        hint="Opsional. Isi hanya kode resmi yang sudah diketahui."
                                    >
                                        <Input
                                            name="code"
                                            defaultValue={
                                                organization?.code ?? ''
                                            }
                                            maxLength={20}
                                        />
                                    </FormField>
                                </div>
                            </FormSection>
                            <FormSection
                                title="Hierarki & status"
                                description="Organisasi tidak dapat menjadi induk dirinya atau turunannya."
                            >
                                <FormField
                                    id="parent_id"
                                    label="Organisasi induk"
                                    error={errors.parent_id}
                                    hint={
                                        canCreateRoot
                                            ? 'Kosongkan untuk organisasi akar (mis. BPS Pusat).'
                                            : 'Organisasi akar hanya dapat dibuat oleh pengguna bercakupan nasional.'
                                    }
                                >
                                    <NativeSelect
                                        name="parent_id"
                                        defaultValue={
                                            organization?.parent_id
                                                ? String(organization.parent_id)
                                                : ''
                                        }
                                    >
                                        <option value="">
                                            — Tanpa induk —
                                        </option>
                                        {parentOptions.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="0"
                                    />
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        value="1"
                                        defaultChecked={
                                            organization?.is_active ?? true
                                        }
                                    />
                                    <Label htmlFor="is_active">
                                        Organisasi aktif
                                    </Label>
                                </div>
                            </FormSection>
                            <FormActions>
                                <Button asChild variant="outline">
                                    <Link href={index()}>Batal</Link>
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

OrganizationForm.layout = {
    breadcrumbs: [
        { title: 'Organisasi', href: index() },
        { title: 'Formulir', href: index() },
    ],
};
