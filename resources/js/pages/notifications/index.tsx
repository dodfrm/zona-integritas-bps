import { Head, Link, router } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/zi/empty-state';
import { PageBody, PageHeader } from '@/components/zi/page-header';
import { Pagination } from '@/components/zi/pagination';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index, read, readAll } from '@/routes/notifications';
import type { Paginated } from '@/types';

type Item = {
    id: string;
    title: string;
    message: string;
    url: string | null;
    read_at: string | null;
    created_at: string;
};

export default function NotificationsIndex({
    notifications,
}: {
    notifications: Paginated<Item>;
}) {
    const hasUnread = notifications.data.some((item) => !item.read_at);

    return (
        <>
            <Head title="Notifikasi" />
            <PageBody>
                <PageHeader
                    title="Notifikasi"
                    description="Notifikasi internal aplikasi. Pengiriman email/WhatsApp belum dikonfigurasi."
                    actions={
                        hasUnread && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.post(
                                        readAll().url,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <CheckCheck aria-hidden="true" />
                                Tandai semua dibaca
                            </Button>
                        )
                    }
                />
                {notifications.data.length === 0 ? (
                    <EmptyState title="Belum ada notifikasi" icon={Bell} />
                ) : (
                    <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                        {notifications.data.map((item) => (
                            <li
                                key={item.id}
                                className={cn(
                                    'flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between',
                                    !item.read_at && 'bg-info-soft/40',
                                )}
                            >
                                <div className="min-w-0">
                                    <p className="font-bold">
                                        {!item.read_at && (
                                            <span
                                                className="mr-2 inline-block size-2 rounded-full bg-primary"
                                                aria-hidden="true"
                                            />
                                        )}
                                        {item.title}
                                        {!item.read_at && (
                                            <span className="sr-only">
                                                {' '}
                                                (belum dibaca)
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-sm">{item.message}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {formatDateTime(item.created_at)}
                                    </p>
                                </div>
                                <Button asChild size="sm" variant="ghost">
                                    <Link
                                        href={read(item.id)}
                                        method="post"
                                        as="button"
                                    >
                                        {item.url ? 'Buka' : 'Tandai dibaca'}
                                    </Link>
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination page={notifications} />
            </PageBody>
        </>
    );
}

NotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifikasi', href: index() }],
};
