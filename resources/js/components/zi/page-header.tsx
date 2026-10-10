import type { ReactNode } from 'react';

export function PageHeader({
    title,
    description,
    actions,
    meta,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    meta?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0 space-y-1">
                <h1 className="text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                    {title}
                </h1>
                {description && (
                    <p className="max-w-3xl text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
                {meta && (
                    <div className="flex flex-wrap items-center gap-2 pt-1">
                        {meta}
                    </div>
                )}
            </div>
            {actions && (
                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {actions}
                </div>
            )}
        </div>
    );
}

export function PageBody({ children }: { children: ReactNode }) {
    return (
        <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-5 p-4 md:p-6">
            {children}
        </div>
    );
}
