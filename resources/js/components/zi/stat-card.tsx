import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

const TONES = {
    neutral: 'text-foreground',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
    info: 'text-info',
} as const;

export function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    tone = 'neutral',
    href,
}: {
    label: string;
    value: number | string;
    hint?: string;
    icon?: LucideIcon;
    tone?: keyof typeof TONES;
    href?: string;
}) {
    const body = (
        <>
            <div className="flex items-center justify-between gap-2">
                <p className="text-xs font-bold text-muted-foreground uppercase">
                    {label}
                </p>
                {Icon && (
                    <Icon
                        aria-hidden="true"
                        className={cn('size-4', TONES[tone])}
                    />
                )}
            </div>
            <p className={cn('text-2xl font-bold tabular-nums', TONES[tone])}>
                {value}
            </p>
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
        </>
    );

    const className =
        'flex flex-col gap-1 rounded-lg border bg-card p-3 transition-colors duration-150';

    return href ? (
        <Link
            href={href}
            className={cn(
                className,
                'hover:border-primary/50 hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
            )}
        >
            {body}
        </Link>
    ) : (
        <div className={className}>{body}</div>
    );
}
