import { Link } from '@inertiajs/react';
import {
    BookOpen,
    FolderOpen,
    LayoutGrid,
    LibraryBig,
    KeyRound,
    MonitorPlay,
    Shield,
    UserRound,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
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
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'User Management',
        href: usersIndex(),
        icon: Users,
        children: [
            {
                title: 'Users',
                href: usersIndex(),
                icon: UserRound,
            },
            {
                title: 'Roles',
                href: '/roles',
                icon: Shield,
            },
            {
                title: 'Permissions',
                href: '/permissions',
                icon: KeyRound,
            },
        ],
    },
    {
        title: 'LMS Content',
        href: '/learning-materials',
        icon: BookOpen,
        children: [
            {
                title: 'Categories',
                href: '/categories',
                icon: FolderOpen,
            },
            {
                title: 'Learning Materials',
                href: '/learning-materials',
                icon: LibraryBig,
            },
            {
                title: 'Learning Library',
                href: '/learning-library',
                icon: MonitorPlay,
            },
        ],
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
