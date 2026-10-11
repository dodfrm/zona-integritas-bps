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
import { create, index, show, store, update } from '@/routes/programs';
import type { AreaOption, Option } from '@/types';

type Props = {
    program?: {
        id: number;
        period_id: number;
        area_id: number | null;
        indicator_ids: string[];
        code: string | null;
        name: string;
        description: string | null;
        objective: string | null;
        pic_user_id: number | null;
        start_date: string | null;
        target_date: string | null;
        status: string;
        notes: string | null;
    };
    periodOptions: Option[];
    selectedPeriodId: number | null;
    areas: AreaOption[];
    userOptions: Option[];
    statusOptions: Option[];
};

export default function ProgramForm({
    program,
    periodOptions,
    selectedPeriodId,
    areas,
    userOptions,
    statusOptions,
}: Props) {
    const [areaId, setAreaId] = useState(
        program?.area_id ? String(program.area_id) : '',
    );
    const [indicatorIds, setIndicatorIds] = useState<string[]>(
        program?.indicator_ids ?? [],
    );
    const action = program ? update.form(program.id) : store.form();
    const visibleAreas = areaId
        ? areas.filter((area) => area.value === areaId)
        : areas;

    if (!program && periodOptions.length === 0) {
        return (
            <PageBody>
                <PageHeader title="Tambah program kerja" />
                <EmptyState
                    title="Tidak ada periode yang dapat diisi"
                    description="Program kerja hanya dapat dibuat pada periode berstatus draf/aktif di organisasi dalam cakupan Anda."
                />
            </PageBody>
        );
    }

    return (
        <>
            <Head
                title={program ? 'Ubah program kerja' : 'Tambah program kerja'}
            />
            <PageBody>
                <PageHeader
                    title={
                        program
                            ? `Ubah ${program.name}`
                            : 'Tambah program kerja'
                    }
                    description="Area dan indikator diambil dari pedoman yang dipakai periode. Periode dan organisasi tidak dapat diubah setelah dibuat."
                />
                <Form {...action} className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <FormSection
                                title="Periode & keterkaitan"
                                description="Hubungkan program dengan area dan indikator ZI."
                            >
                                <FormField
                                    id="period_id"
                                    label="Periode ZI"
                                    error={errors.period_id}
                                    required
                                >
                                    <NativeSelect
                                        name={program ? undefined : 'period_id'}
                                        value={String(selectedPeriodId ?? '')}
                                        disabled={Boolean(program)}
                                        onChange={(event) =>
                                            router.get(
                                                create().url,
                                                {
                                                    period_id:
                                                        event.target.value,
                                                },
                                                { preserveScroll: true },
                                            )
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
                                    id="area_id"
                                    label="Area"
                                    error={errors.area_id}
                                >
                                    <NativeSelect
                                        name="area_id"
                                        value={areaId}
                                        onChange={(event) => {
                                            setAreaId(event.target.value);
                                            setIndicatorIds([]);
                                        }}
                                    >
                                        <option value="">
                                            — Lintas area / belum ditentukan —
                                        </option>
                                        {areas.map((area) => (
                                            <option
                                                key={area.value}
                                                value={area.value}
                                            >
                                                {area.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                <fieldset
                                    className="grid gap-2"
                                    aria-describedby={
                                        errors.indicator_ids
                                            ? 'indicator-error'
                                            : undefined
                                    }
                                >
                                    <legend className="text-sm font-bold">
                                        Indikator terkait
                                    </legend>
                                    <div className="max-h-72 overflow-y-auto rounded-md border p-2">
                                        {visibleAreas.every(
                                            (area) =>
                                                area.indicators.length === 0,
                                        ) ? (
                                            <p className="p-2 text-sm text-muted-foreground">
                                                Belum ada indikator aktif pada
                                                pedoman ini.
                                            </p>
                                        ) : (
                                            visibleAreas.map((area) =>
                                                area.indicators.length ===
                                                0 ? null : (
                                                    <div
                                                        key={area.value}
                                                        className="mb-2"
                                                    >
                                                        {!areaId && (
                                                            <p className="px-1 text-xs font-bold text-muted-foreground uppercase">
                                                                {area.label}
                                                            </p>
                                                        )}
                                                        {area.indicators.map(
                                                            (indicator) => (
                                                                <label
                                                                    key={
                                                                        indicator.value
                                                                    }
                                                                    className={`flex items-start gap-2 rounded px-1 py-1 text-sm hover:bg-accent ${indicator.is_sub ? 'pl-6' : ''}`}
                                                                >
                                                                    <input
                                                                        type="checkbox"
                                                                        className="mt-0.5 size-4 shrink-0 accent-primary"
                                                                        name="indicator_ids[]"
                                                                        value={
                                                                            indicator.value
                                                                        }
                                                                        checked={indicatorIds.includes(
                                                                            indicator.value,
                                                                        )}
                                                                        onChange={(
                                                                            event,
                                                                        ) =>
                                                                            setIndicatorIds(
                                                                                event
                                                                                    .target
                                                                                    .checked
                                                                                    ? [
                                                                                          ...indicatorIds,
                                                                                          indicator.value,
                                                                                      ]
                                                                                    : indicatorIds.filter(
                                                                                          (
                                                                                              id,
                                                                                          ) =>
                                                                                              id !==
                                                                                              indicator.value,
                                                                                      ),
                                                                            )
                                                                        }
                                                                    />
                                                                    {
                                                                        indicator.label
                                                                    }
                                                                </label>
                                                            ),
                                                        )}
                                                    </div>
                                                ),
                                            )
                                        )}
                                    </div>
                                    {errors.indicator_ids && (
                                        <p
                                            id="indicator-error"
                                            role="alert"
                                            className="text-sm font-bold text-danger"
                                        >
                                            {errors.indicator_ids}
                                        </p>
                                    )}
                                </fieldset>
                            </FormSection>

                            <FormSection
                                title="Program"
                                description="Isi ringkas dan terukur."
                            >
                                <div className="grid gap-4 md:grid-cols-[160px_1fr]">
                                    <FormField
                                        id="code"
                                        label="Kode (opsional)"
                                        error={errors.code}
                                    >
                                        <Input
                                            name="code"
                                            defaultValue={program?.code ?? ''}
                                            maxLength={30}
                                        />
                                    </FormField>
                                    <FormField
                                        id="name"
                                        label="Nama program"
                                        error={errors.name}
                                        required
                                    >
                                        <Input
                                            name="name"
                                            defaultValue={program?.name}
                                            required
                                            maxLength={255}
                                        />
                                    </FormField>
                                </div>
                                <FormField
                                    id="objective"
                                    label="Tujuan"
                                    error={errors.objective}
                                >
                                    <Textarea
                                        name="objective"
                                        defaultValue={program?.objective ?? ''}
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
                                            program?.description ?? ''
                                        }
                                        rows={3}
                                    />
                                </FormField>
                            </FormSection>

                            <FormSection
                                title="Pelaksanaan"
                                description="Penanggung jawab, jadwal, dan status."
                            >
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="pic_user_id"
                                        label="Penanggung jawab"
                                        error={errors.pic_user_id}
                                        hint="Pengguna pada organisasi pemilik periode."
                                    >
                                        <NativeSelect
                                            name="pic_user_id"
                                            defaultValue={
                                                program?.pic_user_id
                                                    ? String(
                                                          program.pic_user_id,
                                                      )
                                                    : ''
                                            }
                                        >
                                            <option value="">
                                                — Belum ditetapkan —
                                            </option>
                                            {userOptions.map((option) => (
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
                                        id="status"
                                        label="Status program"
                                        error={errors.status}
                                        required
                                    >
                                        <NativeSelect
                                            name="status"
                                            defaultValue={
                                                program?.status ?? 'planned'
                                            }
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
                                        id="start_date"
                                        label="Tanggal mulai"
                                        error={errors.start_date}
                                    >
                                        <Input
                                            name="start_date"
                                            type="date"
                                            defaultValue={
                                                program?.start_date ?? ''
                                            }
                                        />
                                    </FormField>
                                    <FormField
                                        id="target_date"
                                        label="Target selesai"
                                        error={errors.target_date}
                                    >
                                        <Input
                                            name="target_date"
                                            type="date"
                                            defaultValue={
                                                program?.target_date ?? ''
                                            }
                                        />
                                    </FormField>
                                </div>
                                <FormField
                                    id="notes"
                                    label="Catatan"
                                    error={errors.notes}
                                >
                                    <Textarea
                                        name="notes"
                                        defaultValue={program?.notes ?? ''}
                                        rows={2}
                                    />
                                </FormField>
                            </FormSection>

                            <FormActions>
                                <Button asChild variant="outline">
                                    <Link
                                        href={
                                            program ? show(program.id) : index()
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

ProgramForm.layout = {
    breadcrumbs: [
        { title: 'Program Kerja', href: index() },
        { title: 'Formulir', href: index() },
    ],
};
