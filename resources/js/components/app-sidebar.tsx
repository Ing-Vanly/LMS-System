import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    CalendarDays,
    CalendarRange,
    FolderOpen,
    GraduationCap,
    LayoutGrid,
    LibraryBig,
    KeyRound,
    MonitorPlay,
    Network,
    School,
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

type NavigationGroup = {
    label: string;
    items: NavItem[];
};

const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'My Classes',
        href: '/classes',
        icon: GraduationCap,
    },
    {
        title: 'Academic Management',
        href: '/academic/faculties',
        icon: School,
        children: [
            {
                title: 'Faculties',
                href: '/academic/faculties',
                icon: Building2,
            },
            {
                title: 'Programs',
                href: '/academic/programs',
                icon: Network,
            },
            {
                title: 'Academic Years',
                href: '/academic/academic-years',
                icon: CalendarRange,
            },
            {
                title: 'Semesters',
                href: '/academic/semesters',
                icon: CalendarDays,
            },
            {
                title: 'Classes',
                href: '/academic/classes',
                icon: GraduationCap,
            },
        ],
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

const professorNavItems: NavItem[] = [
    {
        title: 'My Classes',
        href: '/classes',
        icon: GraduationCap,
    },
    {
        title: 'Learning Materials',
        href: '/learning-materials',
        icon: LibraryBig,
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
        ],
    },
];

const studentNavItems: NavItem[] = [
    {
        title: 'My Classes',
        href: '/classes',
        icon: GraduationCap,
    },
    {
        title: 'Learning Library',
        href: '/learning-library',
        icon: MonitorPlay,
    },
];

// These groups are intentionally all visible until role and permission checks
// are added. Keeping the definitions separate makes that future filtering small.
const navigationGroups: NavigationGroup[] = [
    { label: 'Admin', items: adminNavItems },
    { label: 'Professor', items: professorNavItems },
    { label: 'Student', items: studentNavItems },
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
                {navigationGroups.map((group) => (
                    <NavMain
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
