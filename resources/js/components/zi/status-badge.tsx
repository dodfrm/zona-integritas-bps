import {
    AlertTriangle,
    Archive,
    CheckCircle2,
    Circle,
    CircleDashed,
    Clock,
    Loader,
    PauseCircle,
    RotateCcw,
    XCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { EnumValue } from '@/types';

type Tone = 'success' | 'warning' | 'info' | 'danger' | 'neutral';

const TONES: Record<string, [Tone, LucideIcon]> = {
    // verification / review
    accepted: ['success', CheckCircle2],
    pending: ['warning', Clock],
    waiting: ['neutral', CircleDashed],
    in_review: ['info', Loader],
    revision_required: ['warning', RotateCcw],
    rejected: ['danger', XCircle],
    resubmitted: ['info', RotateCcw],
    superseded: ['neutral', Archive],
    // implementation
    not_started: ['neutral', Circle],
    planned: ['neutral', Circle],
    in_progress: ['info', Loader],
    completed: ['success', CheckCircle2],
    on_hold: ['warning', PauseCircle],
    cancelled: ['neutral', XCircle],
    // periods / guidelines / assignments
    draft: ['neutral', CircleDashed],
    active: ['success', CheckCircle2],
    published: ['success', CheckCircle2],
    closed: ['neutral', Archive],
    archived: ['neutral', Archive],
    retired: ['neutral', Archive],
    overdue: ['danger', AlertTriangle],
};

const TONE_CLASSES: Record<Tone, string> = {
    success: 'bg-success-soft text-success border-success/30',
    warning: 'bg-warning-soft text-warning border-warning/30',
    info: 'bg-info-soft text-info border-info/30',
    danger: 'bg-danger-soft text-danger border-danger/30',
    neutral: 'bg-muted text-muted-foreground border-border',
};

/**
 * Status shown with text + icon + color, so meaning never relies on color alone.
 */
export function StatusBadge({
    status,
    className,
}: {
    status: EnumValue | null | undefined;
    className?: string;
}) {
    if (!status) {
        return <span className="text-muted-foreground">—</span>;
    }

    const [tone, Icon] = TONES[status.value] ?? ['neutral', Circle];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-xs font-bold whitespace-nowrap',
                TONE_CLASSES[tone],
                className,
            )}
        >
            <Icon aria-hidden="true" className="size-3.5 shrink-0" />
            {status.label}
        </span>
    );
}

export function OverdueBadge() {
    return <StatusBadge status={{ value: 'overdue', label: 'Terlambat' }} />;
}
