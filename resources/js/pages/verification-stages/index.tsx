import { Form, Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { FormField } from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { SectionCard } from '@/components/zi/section-card';
import { StatusBadge } from '@/components/zi/status-badge';
import { index, store, update } from '@/routes/verification-stages';
import type { Option } from '@/types';

type Stage = {
    id: number;
    name: string;
    description: string | null;
    sequence: number;
    executor_level: string;
    executor_level_label: string;
    evidence_category: string | null;
    evidence_category_label: string | null;
    is_active: boolean;
};

export default function VerificationStagesIndex({
    stages,
    levelOptions,
    categoryOptions,
}: {
    stages: Stage[];
    levelOptions: Option[];
    categoryOptions: Option[];
}) {
    return (
        <>
            <Head title="Alur Verifikasi" />
            <PageBody>
                <PageHeader
                    title="Alur verifikasi"
                    description="Tahap dijalankan berurutan. Tahap berikutnya baru terbuka setelah tahap sebelumnya menerima bukti. Perubahan hanya berlaku untuk pengajuan berikutnya; riwayat keputusan tidak ditulis ulang."
                />
                <p className="rounded-md border border-info/30 bg-info-soft px-3 py-2 text-sm text-info">
                    Tahap tingkat provinsi/pusat hanya berjalan bila organisasi
                    pemilik bukti memiliki induk bertipe tersebut. Bila tidak
                    ada tahap aktif yang berlaku, sistem tetap menjalankan
                    verifikasi internal oleh organisasi pemilik. Aktifkan tahap
                    provinsi/pusat hanya setelah proses bisnisnya ditetapkan.
                </p>
                {stages.map((stage) => (
                    <SectionCard
                        key={stage.id}
                        title={`${stage.sequence}. ${stage.name}`}
                        description={`${stage.executor_level_label} · ${stage.evidence_category_label ?? 'Semua jenis bukti'}`}
                        actions={
                            <StatusBadge
                                status={
                                    stage.is_active
                                        ? { value: 'active', label: 'Aktif' }
                                        : { value: 'draft', label: 'Nonaktif' }
                                }
                            />
                        }
                    >
                        <StageForm
                            stage={stage}
                            levelOptions={levelOptions}
                            categoryOptions={categoryOptions}
                        />
                    </SectionCard>
                ))}
                <SectionCard
                    title="Tambah tahap"
                    description="Mis. tahap verifikasi tambahan untuk jenis bukti tertentu."
                >
                    <StageForm
                        levelOptions={levelOptions}
                        categoryOptions={categoryOptions}
                        nextSequence={
                            Math.max(
                                0,
                                ...stages.map((stage) => stage.sequence),
                            ) + 1
                        }
                    />
                </SectionCard>
            </PageBody>
        </>
    );
}

function StageForm({
    stage,
    levelOptions,
    categoryOptions,
    nextSequence,
}: {
    stage?: Stage;
    levelOptions: Option[];
    categoryOptions: Option[];
    nextSequence?: number;
}) {
    const key = stage?.id ?? 'new';

    return (
        <Form
            {...(stage ? update.form(stage.id) : store.form())}
            options={{ preserveScroll: true }}
            resetOnSuccess={!stage}
            className="grid gap-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-4 md:grid-cols-[100px_1fr_1fr_1fr]">
                        <FormField
                            id={`seq-${key}`}
                            label="Urutan"
                            error={errors.sequence}
                            required
                        >
                            <Input
                                name="sequence"
                                type="number"
                                min={1}
                                max={100}
                                defaultValue={stage?.sequence ?? nextSequence}
                                required
                            />
                        </FormField>
                        <FormField
                            id={`name-${key}`}
                            label="Nama tahap"
                            error={errors.name}
                            required
                        >
                            <Input
                                name="name"
                                defaultValue={stage?.name}
                                required
                                maxLength={255}
                            />
                        </FormField>
                        <FormField
                            id={`level-${key}`}
                            label="Pelaksana"
                            error={errors.executor_level}
                            required
                        >
                            <NativeSelect
                                name="executor_level"
                                defaultValue={stage?.executor_level ?? 'owner'}
                                required
                            >
                                {levelOptions.map((option) => (
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
                            id={`cat-${key}`}
                            label="Berlaku untuk jenis"
                            error={errors.evidence_category}
                        >
                            <NativeSelect
                                name="evidence_category"
                                defaultValue={stage?.evidence_category ?? ''}
                            >
                                <option value="">Semua jenis bukti</option>
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
                        id={`desc-${key}`}
                        label="Keterangan"
                        error={errors.description}
                    >
                        <Textarea
                            name="description"
                            defaultValue={stage?.description ?? ''}
                            rows={2}
                        />
                    </FormField>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0" />
                            <Checkbox
                                id={`active-${key}`}
                                name="is_active"
                                value="1"
                                defaultChecked={stage?.is_active ?? false}
                            />
                            <Label htmlFor={`active-${key}`}>Tahap aktif</Label>
                        </div>
                        {errors.is_active && (
                            <p
                                role="alert"
                                className="text-sm font-bold text-danger"
                            >
                                {errors.is_active}
                            </p>
                        )}
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <Spinner />
                            ) : (
                                !stage && <Plus aria-hidden="true" />
                            )}
                            {stage ? 'Simpan tahap' : 'Tambah tahap'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

VerificationStagesIndex.layout = {
    breadcrumbs: [{ title: 'Alur Verifikasi', href: index() }],
};
