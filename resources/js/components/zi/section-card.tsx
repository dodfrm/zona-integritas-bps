import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function SectionCard({
    title,
    description,
    actions,
    children,
    className,
    contentClassName,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
}) {
    const headingId = `section-${title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;

    return (
        <section
            aria-labelledby={headingId}
            className={cn(
                'rounded-lg border bg-card text-card-foreground',
                className,
            )}
        >
            <div className="flex flex-wrap items-start justify-between gap-2 border-b px-4 py-3">
                <div className="min-w-0">
                    <h2 id={headingId} className="text-base font-bold">
                        {title}
                    </h2>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {actions && (
                    <div className="flex flex-wrap items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>
            <div className={cn('p-4', contentClassName)}>{children}</div>
        </section>
    );
}

export function DetailList({
    items,
}: {
    items: { label: string; value: ReactNode }[];
}) {
    return (
        <dl className="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            {items.map((item) => (
                <div key={item.label} className="min-w-0">
                    <dt className="text-xs font-bold text-muted-foreground uppercase">
                        {item.label}
                    </dt>
                    <dd className="mt-0.5 text-sm break-words whitespace-pre-line">
                        {item.value ?? '—'}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
