import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export type NavGroup = {
    label: string;
    items: NavItem[];
};

export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <>
            {groups
                .filter((group) => group.items.length > 0)
                .map((group) => (
                    <SidebarGroup key={group.label} className="px-2 py-1">
                        <SidebarGroupLabel className="text-sidebar-foreground/60">
                            {group.label}
                        </SidebarGroupLabel>
                        <SidebarMenu>
                            {group.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentOrParentUrl(
                                            item.href,
                                        )}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && (
                                                <item.icon aria-hidden="true" />
                                            )}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
        </>
    );
}
