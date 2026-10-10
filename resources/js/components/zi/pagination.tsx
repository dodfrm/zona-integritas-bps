import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

export function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.total === 0) {
        return null;
    }

    const numbered = page.links.slice(1, -1);
    const prev = page.links[0]?.url;
    const next = page.links[page.links.length - 1]?.url;
    const itemClass =
        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition-colors duration-150';

    return (
        <nav
            aria-label="Navigasi halaman"
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p className="text-sm text-muted-foreground">
                Menampilkan {page.from}–{page.to} dari {page.total} data
            </p>
            {page.last_page > 1 && (
                <div className="flex flex-wrap items-center gap-1">
                    <PageLink
                        url={prev}
                        className={itemClass}
                        label="Halaman sebelumnya"
                    >
                        <ChevronLeft aria-hidden="true" className="size-4" />
                    </PageLink>
                    {numbered.map((link, index) => (
                        <PageLink
                            key={`${link.label}-${index}`}
                            url={link.url}
                            className={cn(
                                itemClass,
                                link.active &&
                                    'border-primary bg-primary text-primary-foreground',
                            )}
                            label={`Halaman ${link.label}`}
                            current={link.active}
                        >
                            {link.label}
                        </PageLink>
                    ))}
                    <PageLink
                        url={next}
                        className={itemClass}
                        label="Halaman berikutnya"
                    >
                        <ChevronRight aria-hidden="true" className="size-4" />
                    </PageLink>
                </div>
            )}
        </nav>
    );
}

function PageLink({
    url,
    className,
    label,
    current = false,
    children,
}: {
    url: string | null | undefined;
    className: string;
    label: string;
    current?: boolean;
    children: React.ReactNode;
}) {
    if (!url) {
        return (
            <span
                className={cn(className, 'opacity-40')}
                aria-disabled="true"
                aria-label={label}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={url}
            preserveScroll
            preserveState
            className={cn(className, !current && 'hover:bg-accent')}
            aria-label={label}
            aria-current={current ? 'page' : undefined}
        >
            {children}
        </Link>
    );
}
