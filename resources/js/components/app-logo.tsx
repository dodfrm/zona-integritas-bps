import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate text-sm font-bold tracking-wide">
                    ZI-TERPADU
                </span>
                <span className="truncate text-[11px] text-sidebar-foreground/70">
                    Zona Integritas BPS
                </span>
            </div>
        </>
    );
}
