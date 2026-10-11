import { Form, Head, Link, router } from '@inertiajs/react';
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
import { create, index, show, store, update } from '@/routes/action-plans';
import type { Option } from '@/types';

type Props = {
    plan?: {
        id: number;
        work_program_id: number;
        indicator_id: number | null;
        title: string;
        description: string | null;
        output_target: string | null;
        start_date: string | null;
        due_date: string | null;
        pic_user_id: number | null;
    };
    programOptions: Option[];
    selectedProgramId: number | null;
    indicatorOptions: Option[];
    userOptions: Option[];
};

export default function ActionPlanForm({
    plan,
    programOptions,
    selectedProgramId,
    indicatorOptions,
    userOptions,
}: Props) {
    const action = plan ? update.form(plan.id) : store.form();

    if (!plan && programOptions.length === 0) {
        return (
            <PageBody>
                <PageHeader title="Tambah rencana aksi" />
                <EmptyState
                    title="Tidak ada program kerja yang dapat diisi"
                    description="Rencana aksi dibuat di bawah program kerja aktif pada periode draf/aktif dalam cakupan Anda."
                />
            </PageBody>
        );
    }

    return (
        <>
            <Head title={plan ? 'Ubah rencana aksi' : 'Tambah rencana aksi'} />
            <PageBody>
                <PageHeader
                    title={plan ? `Ubah ${plan.title}` : 'Tambah rencana aksi'}
                    description="Status dan progres diperbarui dari halaman detail agar setiap perubahan tercatat di riwayat."
                />
                <Form {...action} className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <FormSection title="Program & indikator">
                                <FormField
                                    id="work_program_id"
                                    label="Program kerja"
                                    error={errors.work_program_id}
                                    required
                                >
                                    <NativeSelect
                                        name={
                                            plan ? undefined : 'work_program_id'
                                        }
                                        value={
                                            selectedProgramId
                                                ? String(selectedProgramId)
                                                : ''
                                        }
                                        disabled={Boolean(plan)}
                                        required
                                        onChange={(event) =>
                                            router.get(
                                                create().url,
                                                {
                                                    work_program_id:
                                                        event.target.value,
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <option value="" disabled>
                                            Pilih program kerja
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
                                {selectedProgramId && (
                                    <FormField
                                        id="indicator_id"
                                        label="Indikator"
                                        error={errors.indicator_id}
                                        hint="Opsional. Daftar mengikuti indikator program (atau seluruh indikator pedoman periode)."
                                    >
                                        <NativeSelect
                                            name="indicator_id"
                                            defaultValue={
                                                plan?.indicator_id
                                                    ? String(plan.indicator_id)
                                                    : ''
                                            }
                                        >
                                            <option value="">
                                                — Tidak spesifik —
                                            </option>
                                            {indicatorOptions.map((option) => (
                                                <option
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </FormField>
                                )}
                            </FormSection>
                            {selectedProgramId && (
                                <>
                                    <FormSection title="Rencana aksi">
                                        <FormField
                                            id="title"
                                            label="Judul rencana aksi"
                                            error={errors.title}
                                            required
                                        >
                                            <Input
                                                name="title"
                                                defaultValue={plan?.title}
                                                required
                                                maxLength={255}
                                            />
                                        </FormField>
                                        <FormField
                                            id="output_target"
                                            label="Target keluaran"
                                            error={errors.output_target}
                                            hint="Contoh: SK Tim ZI ditetapkan; laporan monev triwulan."
                                        >
                                            <Textarea
                                                name="output_target"
                                                defaultValue={
                                                    plan?.output_target ?? ''
                                                }
                                                rows={2}
                                            />
                                        </FormField>
                                        <FormField
                                            id="description"
                                            label="Deskripsi"
                                            error={errors.description}
                                        >
                                            <Textarea
                                                name="description"
                                                defaultValue={
                                                    plan?.description ?? ''
                                                }
                                                rows={3}
                                            />
                                        </FormField>
                                    </FormSection>
                                    <FormSection title="Jadwal & penanggung jawab">
                                        <div className="grid gap-4 md:grid-cols-3">
                                            <FormField
                                                id="start_date"
                                                label="Tanggal mulai"
                                                error={errors.start_date}
                                            >
                                                <Input
                                                    name="start_date"
                                                    type="date"
                                                    defaultValue={
                                                        plan?.start_date ?? ''
                                                    }
                                                />
                                            </FormField>
                                            <FormField
                                                id="due_date"
                                                label="Tenggat waktu"
                                                error={errors.due_date}
                                            >
                                                <Input
                                                    name="due_date"
                                                    type="date"
                                                    defaultValue={
                                                        plan?.due_date ?? ''
                                                    }
                                                />
                                            </FormField>
                                            <FormField
                                                id="pic_user_id"
                                                label="Penanggung jawab"
                                                error={errors.pic_user_id}
                                            >
                                                <NativeSelect
                                                    name="pic_user_id"
                                                    defaultValue={
                                                        plan?.pic_user_id
                                                            ? String(
                                                                  plan.pic_user_id,
                                                              )
                                                            : ''
                                                    }
                                                >
                                                    <option value="">
                                                        — Belum ditetapkan —
                                                    </option>
                                                    {userOptions.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </NativeSelect>
                                            </FormField>
                                        </div>
                                    </FormSection>
                                </>
                            )}
                            <FormActions>
                                <Button asChild variant="outline">
                                    <Link href={plan ? show(plan.id) : index()}>
                                        Batal
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !selectedProgramId}
                                >
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

ActionPlanForm.layout = {
    breadcrumbs: [
        { title: 'Rencana Aksi', href: index() },
        { title: 'Formulir', href: index() },
    ],
};
