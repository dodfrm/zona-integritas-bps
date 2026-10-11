import { Form, Head, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PasswordInput from '@/components/password-input';
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
import { index, store, update } from '@/routes/users';
import type { Option } from '@/types';

type ExtraOrganization = {
    organization_id: number | string;
    include_descendants: boolean;
};

type Props = {
    user?: {
        id: number;
        name: string;
        email: string;
        organization_id: number | null;
        access_scope: string;
        is_active: boolean;
        roles: string[];
        extra_organizations: ExtraOrganization[];
        is_self: boolean;
    };
    organizationOptions: Option[];
    roleOptions: Option[];
    scopeOptions: Option[];
};

export default function UserForm({
    user,
    organizationOptions,
    roleOptions,
    scopeOptions,
}: Props) {
    const isEdit = Boolean(user);
    const action = user ? update.form(user.id) : store.form();
    const [extras, setExtras] = useState<ExtraOrganization[]>(
        user?.extra_organizations ?? [],
    );

    return (
        <>
            <Head title={isEdit ? 'Ubah pengguna' : 'Tambah pengguna'} />
            <PageBody>
                <PageHeader
                    title={isEdit ? `Ubah ${user?.name}` : 'Tambah pengguna'}
                    description="Akun dibuat oleh administrator; tidak ada pendaftaran mandiri. Berikan cakupan seminimal mungkin sesuai tugas."
                />
                <Form
                    {...action}
                    className="grid gap-4"
                    resetOnSuccess={['password', 'password_confirmation']}
                >
                    {({ errors, processing }) => (
                        <>
                            <FormSection
                                title="Akun"
                                description="Identitas login pengguna."
                            >
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="name"
                                        label="Nama lengkap"
                                        error={errors.name}
                                        required
                                    >
                                        <Input
                                            name="name"
                                            defaultValue={user?.name}
                                            required
                                            maxLength={255}
                                            autoComplete="off"
                                        />
                                    </FormField>
                                    <FormField
                                        id="email"
                                        label="Email"
                                        error={errors.email}
                                        required
                                    >
                                        <Input
                                            name="email"
                                            type="email"
                                            defaultValue={user?.email}
                                            required
                                            maxLength={255}
                                            autoComplete="off"
                                        />
                                    </FormField>
                                </div>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="password"
                                        label={
                                            isEdit
                                                ? 'Kata sandi baru'
                                                : 'Kata sandi awal'
                                        }
                                        error={errors.password}
                                        required={!isEdit}
                                        hint={
                                            isEdit
                                                ? 'Kosongkan bila tidak diubah.'
                                                : 'Sampaikan secara aman; minta pengguna menggantinya setelah login.'
                                        }
                                    >
                                        <PasswordInput
                                            name="password"
                                            autoComplete="new-password"
                                            required={!isEdit}
                                        />
                                    </FormField>
                                    <FormField
                                        id="password_confirmation"
                                        label="Ulangi kata sandi"
                                        error={errors.password_confirmation}
                                    >
                                        <PasswordInput
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                        />
                                    </FormField>
                                </div>
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
                                        defaultChecked={user?.is_active ?? true}
                                        disabled={user?.is_self}
                                    />
                                    {user?.is_self && (
                                        <input
                                            type="hidden"
                                            name="is_active"
                                            value="1"
                                        />
                                    )}
                                    <Label htmlFor="is_active">
                                        Akun aktif (dapat login)
                                    </Label>
                                </div>
                            </FormSection>

                            <FormSection
                                title="Organisasi & cakupan"
                                description="Menentukan data organisasi mana yang dapat diakses, terlepas dari role."
                            >
                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField
                                        id="organization_id"
                                        label="Organisasi"
                                        error={errors.organization_id}
                                        required
                                    >
                                        <NativeSelect
                                            name="organization_id"
                                            defaultValue={
                                                user?.organization_id
                                                    ? String(
                                                          user.organization_id,
                                                      )
                                                    : ''
                                            }
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
                                    <FormField
                                        id="access_scope"
                                        label="Cakupan akses"
                                        error={errors.access_scope}
                                        required
                                    >
                                        <NativeSelect
                                            name="access_scope"
                                            defaultValue={
                                                user?.access_scope ?? 'own'
                                            }
                                            required
                                        >
                                            {scopeOptions.map((option) => (
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
                                <div className="grid gap-2">
                                    <p className="text-sm font-bold">
                                        Organisasi tambahan (penugasan
                                        eksplisit)
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Opsional. Berikan akses ke organisasi
                                        tertentu di luar organisasi pengguna.
                                    </p>
                                    {extras.map((extra, position) => (
                                        <div
                                            key={position}
                                            className="flex flex-col gap-2 rounded-md border p-2 sm:flex-row sm:items-center"
                                        >
                                            <NativeSelect
                                                aria-label={`Organisasi tambahan ${position + 1}`}
                                                name={`extra_organizations[${position}][organization_id]`}
                                                value={String(
                                                    extra.organization_id,
                                                )}
                                                onChange={(event) =>
                                                    setExtras(
                                                        extras.map((row, i) =>
                                                            i === position
                                                                ? {
                                                                      ...row,
                                                                      organization_id:
                                                                          event
                                                                              .target
                                                                              .value,
                                                                  }
                                                                : row,
                                                        ),
                                                    )
                                                }
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
                                            <label className="flex shrink-0 items-center gap-2 text-sm">
                                                <input
                                                    type="hidden"
                                                    name={`extra_organizations[${position}][include_descendants]`}
                                                    value="0"
                                                />
                                                <input
                                                    type="checkbox"
                                                    className="size-4 accent-primary"
                                                    name={`extra_organizations[${position}][include_descendants]`}
                                                    value="1"
                                                    checked={
                                                        extra.include_descendants
                                                    }
                                                    onChange={(event) =>
                                                        setExtras(
                                                            extras.map(
                                                                (row, i) =>
                                                                    i ===
                                                                    position
                                                                        ? {
                                                                              ...row,
                                                                              include_descendants:
                                                                                  event
                                                                                      .target
                                                                                      .checked,
                                                                          }
                                                                        : row,
                                                            ),
                                                        )
                                                    }
                                                />
                                                Termasuk turunan
                                            </label>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Hapus organisasi tambahan"
                                                onClick={() =>
                                                    setExtras(
                                                        extras.filter(
                                                            (_, i) =>
                                                                i !== position,
                                                        ),
                                                    )
                                                }
                                            >
                                                <Trash2 aria-hidden="true" />
                                            </Button>
                                        </div>
                                    ))}
                                    {errors[
                                        'extra_organizations.0.organization_id'
                                    ] && (
                                        <p
                                            role="alert"
                                            className="text-sm font-bold text-danger"
                                        >
                                            {
                                                errors[
                                                    'extra_organizations.0.organization_id'
                                                ]
                                            }
                                        </p>
                                    )}
                                    <div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setExtras([
                                                    ...extras,
                                                    {
                                                        organization_id: '',
                                                        include_descendants: false,
                                                    },
                                                ])
                                            }
                                        >
                                            <Plus aria-hidden="true" />
                                            Tambah organisasi
                                        </Button>
                                    </div>
                                </div>
                            </FormSection>

                            <FormSection
                                title="Role"
                                description="Kewenangan fungsional. Detail permission tiap role diatur di menu Role & Permission."
                            >
                                <fieldset
                                    className="grid gap-2 sm:grid-cols-2"
                                    aria-describedby={
                                        errors.roles ? 'roles-error' : undefined
                                    }
                                >
                                    <legend className="sr-only">
                                        Role pengguna
                                    </legend>
                                    {roleOptions.map((role) => (
                                        <label
                                            key={role.value}
                                            className="flex items-center gap-2 rounded-md border p-2 text-sm hover:bg-accent"
                                        >
                                            <input
                                                type="checkbox"
                                                className="size-4 accent-primary"
                                                name="roles[]"
                                                value={role.value}
                                                defaultChecked={user?.roles.includes(
                                                    role.value,
                                                )}
                                            />
                                            {role.label}
                                        </label>
                                    ))}
                                </fieldset>
                                {errors.roles && (
                                    <p
                                        id="roles-error"
                                        role="alert"
                                        className="text-sm font-bold text-danger"
                                    >
                                        {errors.roles}
                                    </p>
                                )}
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

UserForm.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index() },
        { title: 'Formulir', href: index() },
    ],
};
