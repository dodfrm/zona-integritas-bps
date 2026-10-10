import { Inbox } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    title,
    description,
    action,
    icon: Icon = Inbox,
}: {
    title: string;
    description?: ReactNode;
    action?: ReactNode;
    icon?: LucideIcon;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-10 text-center">
            <Icon aria-hidden="true" className="size-8 text-muted-foreground" />
            <p className="font-bold text-foreground">{title}</p>
            {description && (
                <p className="max-w-md text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="pt-2">{action}</div>}
        </div>
    );
}
