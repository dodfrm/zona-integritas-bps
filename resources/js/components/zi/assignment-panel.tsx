import { Form, router, usePage } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/zi/empty-state';
import { FormField } from '@/components/zi/form-field';
import { SectionCard } from '@/components/zi/section-card';
import { StatusBadge } from '@/components/zi/status-badge';
import { formatDate, formatDateTime } from '@/lib/format';
import { store, update } from '@/routes/assignments';
import type { Assignment, Option } from '@/types';

export function AssignmentPanel({
    assignments,
    targetType,
    targetId,
    typeOptions,
    userOptions,
    canAssign,
}: {
    assignments: Assignment[];
    targetType: 'work_program' | 'action_plan' | 'evidence';
    targetId: number;
    typeOptions: Option[];
    userOptions: Option[];
    canAssign: boolean;
}) {
    const { auth } = usePage().props;
    const [open, setOpen] = useState(false);

    const changeStatus = (assignment: Assignment, status: string) =>
        router.patch(
            update(assignment.id).url,
            { status },
            { preserveScroll: true },
        );

    return (
        <SectionCard
            title="Penugasan"
            description="Siapa ditugaskan, oleh siapa, dan kapan. Penugasan tidak menambah hak akses."
            actions={
                canAssign && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button size="sm" variant="outline">
                                <UserPlus aria-hidden="true" />
                                Tugaskan
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Tambah penugasan</DialogTitle>
                                <DialogDescription>
                                    Pengguna yang ditugaskan akan menerima
                                    notifikasi internal.
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                {...store.form()}
                                options={{ preserveScroll: true }}
                                onSuccess={() => setOpen(false)}
                                className="grid gap-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="target_type"
                                            value={targetType}
                                        />
                                        <input
                                            type="hidden"
                                            name="target_id"
                                            value={targetId}
                                        />
                                        <FormField
                                            id="assignment-type"
                                            label="Jenis penugasan"
                                            error={errors.type}
                                            required
                                        >
                                            <NativeSelect name="type" required>
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
                                            id="assignment-user"
                                            label="Pengguna"
                                            error={errors.assignee_id}
                                            required
                                        >
                                            <NativeSelect
                                                name="assignee_id"
                                                required
                                                defaultValue=""
                                            >
                                                <option value="" disabled>
                                                    Pilih pengguna
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
                                            id="assignment-due"
                                            label="Tenggat (opsional)"
                                            error={errors.due_date}
                                        >
                                            <Input
                                                type="date"
                                                name="due_date"
                                            />
                                        </FormField>
                                        <FormField
                                            id="assignment-notes"
                                            label="Catatan"
                                            error={errors.notes}
                                        >
                                            <Textarea name="notes" rows={3} />
                                        </FormField>
                                        <DialogFooter>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />}
                                                Simpan penugasan
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                )
            }
        >
            {assignments.length === 0 ? (
                <EmptyState title="Belum ada penugasan" />
            ) : (
                <ul className="divide-y">
                    {assignments.map((assignment) => {
                        const isActive = assignment.status.value === 'active';
                        const isMine = assignment.assignee?.id === auth.user.id;

                        return (
                            <li
                                key={assignment.id}
                                className="flex flex-col gap-2 py-2.5 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0 text-sm">
                                    <p className="font-bold">
                                        {assignment.assignee?.name ?? '—'}{' '}
                                        <span className="font-normal text-muted-foreground">
                                            · {assignment.type.label}
                                        </span>
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Ditugaskan oleh{' '}
                                        {assignment.assigner?.name ?? 'sistem'}{' '}
                                        pada{' '}
                                        {formatDateTime(assignment.created_at)}
                                        {assignment.due_date &&
                                            ` · tenggat ${formatDate(assignment.due_date)}`}
                                    </p>
                                    {assignment.notes && (
                                        <p className="mt-1 text-xs whitespace-pre-line">
                                            {assignment.notes}
                                        </p>
                                    )}
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <StatusBadge status={assignment.status} />
                                    {isActive && (isMine || canAssign) && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                changeStatus(
                                                    assignment,
                                                    'completed',
                                                )
                                            }
                                        >
                                            Tandai selesai
                                        </Button>
                                    )}
                                    {isActive && canAssign && (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                changeStatus(
                                                    assignment,
                                                    'cancelled',
                                                )
                                            }
                                        >
                                            Batalkan
                                        </Button>
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </SectionCard>
    );
}
