import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <div className="flex flex-col items-center gap-3 text-center">
                        <div className="flex size-12 items-center justify-center rounded-lg bg-sidebar text-white">
                            <AppLogoIcon className="size-7" />
                        </div>
                        <div>
                            <p className="text-lg font-bold tracking-wide">
                                ZI-TERPADU
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Pengelolaan Program Kerja, Rencana Aksi, dan
                                Bukti Dukung Zona Integritas
                            </p>
                        </div>
                    </div>
                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <div className="mb-6 space-y-1 text-center">
                            <h1 className="text-xl font-bold">{title}</h1>
                            {description && (
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
