import { Form, Head, Link } from '@inertiajs/react';
import { Copy, Lock, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ConfirmDialog } from '@/components/zi/confirm-dialog';
import { EmptyState } from '@/components/zi/empty-state';
import { FormField } from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { SectionCard } from '@/components/zi/section-card';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDateTime } from '@/lib/format';
import { destroy as destroyArea, update as updateArea } from '@/routes/areas';
import { store as storeIndicator } from '@/routes/areas/indicators';
import {
    create,
    destroy,
    index,
    publish,
    retire,
    update,
} from '@/routes/guidelines';
import { store as storeArea } from '@/routes/guidelines/areas';
import {
    destroy as destroyIndicator,
    update as updateIndicator,
} from '@/routes/indicators';
import type { EnumValue } from '@/types';

type Indicator = {
    id: number;
    parent_id: number | null;
    code: string;
    name: string;
    description: string | null;
    evidence_guidance: string | null;
    weight: string | null;
    sort_order: number;
    is_active: boolean;
};

type Area = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    indicators: Indicator[];
};

type Props = {
    version: {
        id: number;
        name: string;
        description: string | null;
        reference: string | null;
        status: EnumValue;
        published_at: string | null;
        copied_from: { id: number; name: string } | null;
        periods_count: number;
    };
    areas: Area[];
    can: { edit: boolean; manage: boolean };
};

export default function GuidelineShow({ version, areas, can }: Props) {
    const isDraft = version.status.value === 'draft';

    return (
        <>
            <Head title={version.name} />
            <PageBody>
                <PageHeader
                    title={version.name}
                    description={version.description ?? undefined}
                    meta={
                        <>
                            <StatusBadge status={version.status} />
                            {version.reference && (
                                <span className="text-xs text-muted-foreground">
                                    Rujukan: {version.reference}
                                </span>
                            )}
                            <span className="text-xs text-muted-foreground">
                                Dipakai {version.periods_count} periode
                            </span>
                            {version.copied_from && (
                                <span className="text-xs text-muted-foreground">
                                    Salinan dari {version.copied_from.name}
                                </span>
                            )}
                        </>
                    }
                    actions={
                        can.manage && (
                            <>
                                {can.edit && (
                                    <VersionDialog version={version} />
                                )}
                                {isDraft && (
                                    <ConfirmDialog
                                        trigger={<Button>Berlakukan</Button>}
                                        title="Berlakukan pedoman ini?"
                                        description="Setelah diberlakukan, area dan indikator terkunci dan dapat dipakai periode. Perubahan berikutnya harus melalui versi baru (salinan)."
                                        confirmLabel="Berlakukan"
                                        action={{
                                            url: publish(version.id).url,
                                        }}
                                    />
                                )}
                                {version.status.value === 'published' && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="outline">
                                                Nyatakan tidak berlaku
                                            </Button>
                                        }
                                        title="Nyatakan pedoman tidak berlaku?"
                                        description="Pedoman tidak dapat dipilih untuk periode baru. Periode yang sudah memakainya tidak berubah."
                                        confirmLabel="Nyatakan tidak berlaku"
                                        action={{ url: retire(version.id).url }}
                                    />
                                )}
                                <Button asChild variant="outline">
                                    <Link
                                        href={create({
                                            query: { copy_from: version.id },
                                        })}
                                    >
                                        <Copy aria-hidden="true" />
                                        Salin ke versi baru
                                    </Link>
                                </Button>
                                {isDraft && version.periods_count === 0 && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost">
                                                <Trash2 aria-hidden="true" />
                                                Hapus draf
                                            </Button>
                                        }
                                        title="Hapus versi draf?"
                                        description="Seluruh area dan indikator pada draf ini ikut terhapus."
                                        confirmLabel="Hapus"
                                        destructive
                                        action={{
                                            url: destroy(version.id).url,
                                            method: 'delete',
                                        }}
                                    />
                                )}
                            </>
                        )
                    }
                />

                {!can.edit && (
                    <p className="flex items-center gap-2 rounded-md border bg-muted/50 px-3 py-2 text-sm text-muted-foreground">
                        <Lock aria-hidden="true" className="size-4" />
                        {isDraft
                            ? 'Anda hanya dapat melihat pedoman ini.'
                            : `Pedoman ${version.status.label.toLowerCase()} sejak ${formatDateTime(version.published_at)} dan terkunci. Gunakan "Salin ke versi baru" untuk perubahan.`}
                    </p>
                )}

                <p className="rounded-md border border-warning/30 bg-warning-soft px-3 py-2 text-sm text-warning">
                    Bobot hanya disimpan sebagai konfigurasi dan tidak dipakai
                    untuk perhitungan nilai sampai rumus resmi dikonfirmasi.
                </p>

                {can.edit && (
                    <div>
                        <AreaDialog
                            trigger={
                                <Button>
                                    <Plus aria-hidden="true" />
                                    Tambah area
                                </Button>
                            }
                            action={storeArea.form(version.id)}
                            nextOrder={areas.length + 1}
                        />
                    </div>
                )}

                {areas.length === 0 && (
                    <EmptyState
                        title="Belum ada area"
                        description="Tambahkan area sesuai pedoman resmi."
                    />
                )}

                {areas.map((area) => {
                    const parents = area.indicators.filter(
                        (indicator) => indicator.parent_id === null,
                    );

                    return (
                        <SectionCard
                            key={area.id}
                            title={`${area.code}. ${area.name}`}
                            description={area.description ?? undefined}
                            actions={
                                can.edit && (
                                    <>
                                        {!area.is_active && (
                                            <StatusBadge
                                                status={{
                                                    value: 'archived',
                                                    label: 'Nonaktif',
                                                }}
                                            />
                                        )}
                                        <IndicatorDialog
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Plus aria-hidden="true" />
                                                    Indikator
                                                </Button>
                                            }
                                            action={storeIndicator.form(
                                                area.id,
                                            )}
                                            parents={parents}
                                            nextOrder={
                                                area.indicators.length + 1
                                            }
                                        />
                                        <AreaDialog
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    aria-label={`Ubah area ${area.name}`}
                                                >
                                                    <Pencil aria-hidden="true" />
                                                </Button>
                                            }
                                            action={updateArea.form(area.id)}
                                            area={area}
                                            nextOrder={area.sort_order}
                                        />
                                        <ConfirmDialog
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    aria-label={`Hapus area ${area.name}`}
                                                >
                                                    <Trash2 aria-hidden="true" />
                                                </Button>
                                            }
                                            title={`Hapus area ${area.name}?`}
                                            description="Indikator di area ini ikut terhapus. Hanya mungkin pada versi draf."
                                            confirmLabel="Hapus"
                                            destructive
                                            action={{
                                                url: destroyArea(area.id).url,
                                                method: 'delete',
                                            }}
                                        />
                                    </>
                                )
                            }
                            contentClassName="p-0"
                        >
                            {area.indicators.length === 0 ? (
                                <p className="p-4 text-sm text-muted-foreground">
                                    Belum ada indikator.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {parents
                                        .flatMap((parent) => [
                                            parent,
                                            ...area.indicators.filter(
                                                (child) =>
                                                    child.parent_id ===
                                                    parent.id,
                                            ),
                                        ])
                                        .map((indicator) => (
                                            <li
                                                key={indicator.id}
                                                className={`flex flex-col gap-1 px-4 py-2.5 sm:flex-row sm:items-start sm:justify-between ${indicator.parent_id ? 'pl-10' : ''}`}
                                            >
                                                <div className="min-w-0">
                                                    <p className="text-sm">
                                                        <span className="font-bold">
                                                            {indicator.code}
                                                        </span>{' '}
                                                        {indicator.name}
                                                        {indicator.parent_id && (
                                                            <span className="ml-1 text-xs text-muted-foreground">
                                                                (subindikator)
                                                            </span>
                                                        )}
                                                    </p>
                                                    {indicator.description && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {
                                                                indicator.description
                                                            }
                                                        </p>
                                                    )}
                                                    {indicator.evidence_guidance && (
                                                        <p className="text-xs">
                                                            Petunjuk bukti:{' '}
                                                            {
                                                                indicator.evidence_guidance
                                                            }
                                                        </p>
                                                    )}
                                                    {indicator.weight !==
                                                        null && (
                                                        <p className="text-xs text-muted-foreground">
                                                            Bobot (konfigurasi):{' '}
                                                            {indicator.weight}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex shrink-0 items-center gap-1">
                                                    {!indicator.is_active && (
                                                        <StatusBadge
                                                            status={{
                                                                value: 'archived',
                                                                label: 'Nonaktif',
                                                            }}
                                                        />
                                                    )}
                                                    {can.edit && (
                                                        <>
                                                            <IndicatorDialog
                                                                trigger={
                                                                    <Button
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        aria-label={`Ubah indikator ${indicator.code}`}
                                                                    >
                                                                        <Pencil aria-hidden="true" />
                                                                    </Button>
                                                                }
                                                                action={updateIndicator.form(
                                                                    indicator.id,
                                                                )}
                                                                parents={parents.filter(
                                                                    (parent) =>
                                                                        parent.id !==
                                                                        indicator.id,
                                                                )}
                                                                indicator={
                                                                    indicator
                                                                }
                                                                nextOrder={
                                                                    indicator.sort_order
                                                                }
                                                            />
                                                            <ConfirmDialog
                                                                trigger={
                                                                    <Button
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        aria-label={`Hapus indikator ${indicator.code}`}
                                                                    >
                                                                        <Trash2 aria-hidden="true" />
                                                                    </Button>
                                                                }
                                                                title={`Hapus indikator ${indicator.code}?`}
                                                                description="Subindikator di bawahnya ikut terhapus. Hanya mungkin pada versi draf."
                                                                confirmLabel="Hapus"
                                                                destructive
                                                                action={{
                                                                    url: destroyIndicator(
                                                                        indicator.id,
                                                                    ).url,
                                                                    method: 'delete',
                                                                }}
                                                            />
                                                        </>
                                                    )}
                                                </div>
                                            </li>
                                        ))}
                                </ul>
                            )}
                        </SectionCard>
                    );
                })}
            </PageBody>
        </>
    );
}

type FormAction = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

function EditDialog({
    trigger,
    title,
    description,
    action,
    children,
}: {
    trigger: ReactNode;
    title: string;
    description?: string;
    action: FormAction;
    children: (errors: Record<string, string>) => ReactNode;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            {children(errors)}
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Simpan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function ActiveCheckbox({ id, checked }: { id: string; checked: boolean }) {
    return (
        <div className="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0" />
            <Checkbox
                id={id}
                name="is_active"
                value="1"
                defaultChecked={checked}
            />
            <Label htmlFor={id}>
                Aktif (dapat dipilih pada program/rencana aksi)
            </Label>
        </div>
    );
}

function VersionDialog({ version }: { version: Props['version'] }) {
    return (
        <EditDialog
            trigger={
                <Button variant="outline">
                    <Pencil aria-hidden="true" />
                    Ubah identitas
                </Button>
            }
            title="Ubah identitas versi"
            action={update.form(version.id)}
        >
            {(errors) => (
                <>
                    <FormField
                        id="version-name"
                        label="Nama versi"
                        error={errors.name}
                        required
                    >
                        <Input
                            name="name"
                            defaultValue={version.name}
                            required
                            maxLength={255}
                        />
                    </FormField>
                    <FormField
                        id="version-reference"
                        label="Rujukan regulasi"
                        error={errors.reference}
                    >
                        <Input
                            name="reference"
                            defaultValue={version.reference ?? ''}
                            maxLength={255}
                        />
                    </FormField>
                    <FormField
                        id="version-description"
                        label="Deskripsi"
                        error={errors.description}
                    >
                        <Textarea
                            name="description"
                            defaultValue={version.description ?? ''}
                            rows={3}
                        />
                    </FormField>
                </>
            )}
        </EditDialog>
    );
}

function AreaDialog({
    trigger,
    action,
    area,
    nextOrder,
}: {
    trigger: ReactNode;
    action: FormAction;
    area?: Area;
    nextOrder: number;
}) {
    return (
        <EditDialog
            trigger={trigger}
            title={area ? 'Ubah area' : 'Tambah area'}
            action={action}
        >
            {(errors) => (
                <>
                    <div className="grid gap-4 sm:grid-cols-[120px_1fr]">
                        <FormField
                            id={`area-code-${area?.id ?? 'new'}`}
                            label="Kode"
                            error={errors.code}
                            required
                        >
                            <Input
                                name="code"
                                defaultValue={area?.code}
                                required
                                maxLength={20}
                            />
                        </FormField>
                        <FormField
                            id={`area-name-${area?.id ?? 'new'}`}
                            label="Nama area"
                            error={errors.name}
                            required
                        >
                            <Input
                                name="name"
                                defaultValue={area?.name}
                                required
                                maxLength={255}
                            />
                        </FormField>
                    </div>
                    <FormField
                        id={`area-desc-${area?.id ?? 'new'}`}
                        label="Deskripsi"
                        error={errors.description}
                    >
                        <Textarea
                            name="description"
                            defaultValue={area?.description ?? ''}
                            rows={3}
                        />
                    </FormField>
                    <FormField
                        id={`area-order-${area?.id ?? 'new'}`}
                        label="Urutan tampilan"
                        error={errors.sort_order}
                        required
                    >
                        <Input
                            name="sort_order"
                            type="number"
                            min={0}
                            defaultValue={nextOrder}
                            required
                        />
                    </FormField>
                    <ActiveCheckbox
                        id={`area-active-${area?.id ?? 'new'}`}
                        checked={area?.is_active ?? true}
                    />
                </>
            )}
        </EditDialog>
    );
}

function IndicatorDialog({
    trigger,
    action,
    indicator,
    parents,
    nextOrder,
}: {
    trigger: ReactNode;
    action: FormAction;
    indicator?: Indicator;
    parents: Indicator[];
    nextOrder: number;
}) {
    const key = indicator?.id ?? 'new';

    return (
        <EditDialog
            trigger={trigger}
            title={indicator ? 'Ubah indikator' : 'Tambah indikator'}
            description="Gunakan nama dan kode sesuai pedoman resmi."
            action={action}
        >
            {(errors) => (
                <>
                    <FormField
                        id={`ind-parent-${key}`}
                        label="Induk (untuk subindikator)"
                        error={errors.parent_id}
                    >
                        <NativeSelect
                            name="parent_id"
                            defaultValue={
                                indicator?.parent_id
                                    ? String(indicator.parent_id)
                                    : ''
                            }
                        >
                            <option value="">— Indikator utama —</option>
                            {parents.map((parent) => (
                                <option key={parent.id} value={parent.id}>
                                    {parent.code} {parent.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <div className="grid gap-4 sm:grid-cols-[120px_1fr]">
                        <FormField
                            id={`ind-code-${key}`}
                            label="Kode"
                            error={errors.code}
                            required
                        >
                            <Input
                                name="code"
                                defaultValue={indicator?.code}
                                required
                                maxLength={30}
                            />
                        </FormField>
                        <FormField
                            id={`ind-name-${key}`}
                            label="Nama indikator"
                            error={errors.name}
                            required
                        >
                            <Input
                                name="name"
                                defaultValue={indicator?.name}
                                required
                                maxLength={500}
                            />
                        </FormField>
                    </div>
                    <FormField
                        id={`ind-desc-${key}`}
                        label="Deskripsi"
                        error={errors.description}
                    >
                        <Textarea
                            name="description"
                            defaultValue={indicator?.description ?? ''}
                            rows={2}
                        />
                    </FormField>
                    <FormField
                        id={`ind-guide-${key}`}
                        label="Petunjuk bukti dukung"
                        error={errors.evidence_guidance}
                    >
                        <Textarea
                            name="evidence_guidance"
                            defaultValue={indicator?.evidence_guidance ?? ''}
                            rows={2}
                        />
                    </FormField>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id={`ind-weight-${key}`}
                            label="Bobot (opsional)"
                            error={errors.weight}
                            hint="Tidak dipakai untuk perhitungan."
                        >
                            <Input
                                name="weight"
                                type="number"
                                step="0.0001"
                                min={0}
                                defaultValue={indicator?.weight ?? ''}
                            />
                        </FormField>
                        <FormField
                            id={`ind-order-${key}`}
                            label="Urutan"
                            error={errors.sort_order}
                            required
                        >
                            <Input
                                name="sort_order"
                                type="number"
                                min={0}
                                defaultValue={nextOrder}
                                required
                            />
                        </FormField>
                    </div>
                    <ActiveCheckbox
                        id={`ind-active-${key}`}
                        checked={indicator?.is_active ?? true}
                    />
                </>
            )}
        </EditDialog>
    );
}

GuidelineShow.layout = {
    breadcrumbs: [
        { title: 'Pedoman & Indikator', href: index() },
        { title: 'Detail', href: index() },
    ],
};
