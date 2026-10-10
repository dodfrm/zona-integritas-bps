import { Form, Head } from '@inertiajs/react';
import { Lock, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { ConfirmDialog } from '@/components/zi/confirm-dialog';
import { FormField } from '@/components/zi/form-field';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { SectionCard } from '@/components/zi/section-card';
import { destroy, index, store, update } from '@/routes/roles';

type Permission = { value: string; label: string; group: string };
type Role = {
    id: number;
    name: string;
    label: string;
    is_system: boolean;
    is_locked: boolean;
    users_count: number;
    permissions: string[];
};

export default function RolesIndex({
    roles,
    permissions,
}: {
    roles: Role[];
    permissions: Permission[];
}) {
    const groups = Array.from(
        new Set(permissions.map((permission) => permission.group)),
    );

    return (
        <>
            <Head title="Role & Permission" />
            <PageBody>
                <PageHeader
                    title="Role & Permission"
                    description="Role adalah kumpulan permission berbasis tindakan. Hak akses data tetap dibatasi oleh organisasi dan cakupan masing-masing pengguna."
                />

                <SectionCard
                    title="Tambah role"
                    description="Gunakan bila role awal tidak sesuai dengan tata kelola yang ditetapkan."
                >
                    <Form
                        {...store.form()}
                        resetOnSuccess
                        className="flex flex-col gap-3 sm:flex-row sm:items-end"
                    >
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    id="role-name"
                                    label="Nama teknis role"
                                    error={errors.name}
                                    hint="Huruf kecil, angka, garis bawah. Contoh: verifikator_area"
                                    className="sm:w-80"
                                >
                                    <Input
                                        name="name"
                                        required
                                        maxLength={50}
                                        pattern="[a-z][a-z0-9_]*"
                                    />
                                </FormField>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="sm:mb-6"
                                >
                                    {processing ? (
                                        <Spinner />
                                    ) : (
                                        <Plus aria-hidden="true" />
                                    )}
                                    Tambah role
                                </Button>
                            </>
                        )}
                    </Form>
                </SectionCard>

                {roles.map((role) => (
                    <SectionCard
                        key={role.id}
                        title={role.label}
                        description={`${role.name} · ${role.users_count} pengguna · ${role.permissions.length} permission`}
                        actions={
                            !role.is_system &&
                            role.users_count === 0 && (
                                <ConfirmDialog
                                    trigger={
                                        <Button size="sm" variant="ghost">
                                            <Trash2 aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    }
                                    title={`Hapus role ${role.name}?`}
                                    description="Role yang tidak dipakai pengguna akan dihapus permanen. Tindakan ini tercatat di audit trail."
                                    confirmLabel="Hapus role"
                                    destructive
                                    action={{
                                        url: destroy(role.id).url,
                                        method: 'delete',
                                    }}
                                />
                            )
                        }
                    >
                        {role.is_locked ? (
                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Lock aria-hidden="true" className="size-4" />
                                Super Admin selalu memiliki seluruh permission
                                dan tidak dapat diubah.
                            </p>
                        ) : (
                            <Form
                                {...update.form(role.id)}
                                options={{ preserveScroll: true }}
                                className="grid gap-4"
                            >
                                {({ processing }) => (
                                    <>
                                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                            {groups.map((group) => (
                                                <fieldset
                                                    key={group}
                                                    className="grid content-start gap-1.5 rounded-md border p-3"
                                                >
                                                    <legend className="px-1 text-xs font-bold text-muted-foreground uppercase">
                                                        {group}
                                                    </legend>
                                                    {permissions
                                                        .filter(
                                                            (permission) =>
                                                                permission.group ===
                                                                group,
                                                        )
                                                        .map((permission) => (
                                                            <label
                                                                key={
                                                                    permission.value
                                                                }
                                                                className="flex items-start gap-2 text-sm"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    className="mt-0.5 size-4 shrink-0 accent-primary"
                                                                    name="permissions[]"
                                                                    value={
                                                                        permission.value
                                                                    }
                                                                    defaultChecked={role.permissions.includes(
                                                                        permission.value,
                                                                    )}
                                                                />
                                                                <span>
                                                                    {
                                                                        permission.label
                                                                    }
                                                                    <span className="block text-xs text-muted-foreground">
                                                                        {
                                                                            permission.value
                                                                        }
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        ))}
                                                </fieldset>
                                            ))}
                                        </div>
                                        <div className="flex justify-end">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : (
                                                    <ShieldCheck aria-hidden="true" />
                                                )}
                                                Simpan permission {role.label}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        )}
                    </SectionCard>
                ))}
            </PageBody>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Role & Permission', href: index() }],
};
