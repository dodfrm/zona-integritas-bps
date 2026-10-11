import { cn } from '@/lib/utils';

/**
 * Progress with an explicit definition. Renders "Belum ada data" instead of a fake 0%.
 */
export function ProgressBar({
    value,
    label,
    detail,
    className,
}: {
    value: number | null;
    label: string;
    detail?: string;
    className?: string;
}) {
    return (
        <div className={cn('grid gap-1', className)}>
            <div className="flex items-baseline justify-between gap-2 text-xs">
                <span className="text-muted-foreground">{label}</span>
                <span className="font-bold tabular-nums">
                    {value === null ? 'Belum ada data' : `${value}%`}
                    {detail && (
                        <span className="ml-1 font-normal text-muted-foreground">
                            ({detail})
                        </span>
                    )}
                </span>
            </div>
            <div
                role="progressbar"
                aria-label={label}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-valuenow={value ?? undefined}
                aria-valuetext={value === null ? 'Belum ada data' : `${value}%`}
                className="h-2 overflow-hidden rounded-full bg-muted"
            >
                <div
                    className="h-full rounded-full bg-primary transition-[width] duration-300"
                    style={{ width: `${value ?? 0}%` }}
                />
            </div>
        </div>
    );
}
