import { cloneElement, isValidElement } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type ControlProps = {
    id?: string;
    'aria-invalid'?: boolean;
    'aria-describedby'?: string;
};

/**
 * Label + control + hint + inline error, wired with aria-invalid / aria-describedby.
 */
export function FormField({
    id,
    label,
    error,
    hint,
    required = false,
    className,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: ReactNode;
    required?: boolean;
    className?: string;
    children: ReactElement<ControlProps>;
}) {
    const describedBy =
        [hint ? `${id}-hint` : null, error ? `${id}-error` : null]
            .filter(Boolean)
            .join(' ') || undefined;

    const control = isValidElement(children)
        ? cloneElement(children, {
              id,
              'aria-invalid': error ? true : undefined,
              'aria-describedby': describedBy,
          })
        : children;

    return (
        <div className={cn('grid gap-1.5', className)}>
            <Label htmlFor={id} className="font-bold">
                {label}
                {required && (
                    <span className="text-danger" aria-hidden="true">
                        *
                    </span>
                )}
                {required && <span className="sr-only">(wajib)</span>}
            </Label>
            {control}
            {hint && (
                <p id={`${id}-hint`} className="text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
            {error && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="text-sm font-bold text-danger"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

export function FormSection({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <fieldset className="grid gap-4 rounded-lg border bg-card p-4 md:grid-cols-[220px_1fr] md:gap-6">
            <legend className="sr-only">{title}</legend>
            <div aria-hidden="true">
                <p className="font-bold">{title}</p>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <div className="grid gap-4">{children}</div>
        </fieldset>
    );
}

export function FormActions({ children }: { children: ReactNode }) {
    return (
        <div className="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-end gap-2 border-t bg-background/95 px-4 py-3 backdrop-blur md:mx-0 md:rounded-lg md:border">
            {children}
        </div>
    );
}
