import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

/**
 * Confirmation before risky actions (archive, status change, publish, delete).
 */
export function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel = 'Ya, lanjutkan',
    destructive = false,
    action,
}: {
    trigger: ReactNode;
    title: string;
    description: ReactNode;
    confirmLabel?: string;
    destructive?: boolean;
    action: {
        url: string;
        method?: 'post' | 'put' | 'patch' | 'delete';
        data?: Record<string, string | number | boolean | null>;
    };
}) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const confirm = () => {
        router.visit(action.url, {
            method: action.method ?? 'post',
            data: action.data ?? {},
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="outline" disabled={processing}>
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        variant={destructive ? 'destructive' : 'default'}
                        onClick={confirm}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
