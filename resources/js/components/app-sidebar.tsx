import { Link } from '@inertiajs/react';
import {
    BookOpenCheck,
    Building2,
    CalendarRange,
    ClipboardCheck,
    ClipboardList,
    FileBarChart,
    FileCheck2,
    FolderKanban,
    History,
    LayoutDashboard,
    ListChecks,
    ShieldCheck,
    UserCog,
    Workflow,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import type { NavGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import { index as actionPlans } from '@/routes/action-plans';
import { index as auditLogs } from '@/routes/audit-logs';
import { index as evidence } from '@/routes/evidence';
import { index as guidelines } from '@/routes/guidelines';
import { index as organizations } from '@/routes/organizations';
import { index as periods } from '@/routes/periods';
import { index as programs } from '@/routes/programs';
import { index as reports } from '@/routes/reports';
import { index as roles } from '@/routes/roles';
import { index as tasks } from '@/routes/tasks';
import { index as users } from '@/routes/users';
import { index as verificationStages } from '@/routes/verification-stages';
import { index as verifications } from '@/routes/verifications';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { can } = usePermissions();

    // Menus follow permissions only for convenience; the server enforces access.
    const only = (condition: boolean, item: NavItem) =>
        condition ? [item] : [];

    const groups: NavGroup[] = [
        {
            label: 'Utama',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutDashboard,
                },
                { title: 'Tugas Saya', href: tasks(), icon: ListChecks },
            ],
        },
        {
            label: 'Pelaksanaan',
            items: [
                ...only(can('programs.view'), {
                    title: 'Program Kerja',
                    href: programs(),
                    icon: FolderKanban,
                }),
                ...only(can('action_plans.view'), {
                    title: 'Rencana Aksi',
                    href: actionPlans(),
                    icon: ClipboardList,
                }),
                ...only(can('evidence.view'), {
                    title: 'Bukti Dukung',
                    href: evidence(),
                    icon: FileCheck2,
                }),
                ...only(can('evidence.verify', 'evidence.request_revision'), {
                    title: 'Verifikasi',
                    href: verifications(),
                    icon: ClipboardCheck,
                }),
            ],
        },
        {
            label: 'Pemantauan',
            items: [
                ...only(can('reports.view'), {
                    title: 'Laporan',
                    href: reports(),
                    icon: FileBarChart,
                }),
                ...only(can('audit.view'), {
                    title: 'Audit Trail',
                    href: auditLogs(),
                    icon: History,
                }),
            ],
        },
        {
            label: 'Master Data',
            items: [
                ...only(can('periods.view', 'periods.manage'), {
                    title: 'Periode ZI',
                    href: periods(),
                    icon: CalendarRange,
                }),
                ...only(can('master.view', 'master.manage'), {
                    title: 'Pedoman & Indikator',
                    href: guidelines(),
                    icon: BookOpenCheck,
                }),
                ...only(can('verification_flows.manage'), {
                    title: 'Alur Verifikasi',
                    href: verificationStages(),
                    icon: Workflow,
                }),
            ],
        },
        {
            label: 'Administrasi',
            items: [
                ...only(can('organizations.view', 'organizations.manage'), {
                    title: 'Organisasi',
                    href: organizations(),
                    icon: Building2,
                }),
                ...only(can('users.view', 'users.manage'), {
                    title: 'Pengguna',
                    href: users(),
                    icon: UserCog,
                }),
                ...only(can('roles.manage'), {
                    title: 'Role & Permission',
                    href: roles(),
                    icon: ShieldCheck,
                }),
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={groups} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
