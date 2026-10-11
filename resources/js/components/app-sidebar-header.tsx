import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { index as notifications } from '@/routes/notifications';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth, unreadNotifications } = usePage().props;

    return (
        <header className="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b bg-background/95 px-4 backdrop-blur">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger
                    className="-ml-1"
                    aria-label="Buka/tutup menu"
                />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-3">
                {auth.user.organization && (
                    <span className="hidden max-w-64 truncate text-xs text-muted-foreground md:inline">
                        {auth.user.organization.name}
                    </span>
                )}
                <Link
                    href={notifications()}
                    className="relative inline-flex size-9 items-center justify-center rounded-md transition-colors duration-150 hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label={
                        unreadNotifications > 0
                            ? `Notifikasi, ${unreadNotifications} belum dibaca`
                            : 'Notifikasi'
                    }
                >
                    <Bell aria-hidden="true" className="size-5" />
                    {unreadNotifications > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 min-w-5 rounded-full bg-danger px-1 text-center text-[11px] leading-5 font-bold text-white">
                            {unreadNotifications > 99
                                ? '99+'
                                : unreadNotifications}
                        </span>
                    )}
                </Link>
            </div>
        </header>
    );
}
