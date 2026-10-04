import { Link } from '@inertiajs/react';
import { Building2, FolderKanban, LayoutGrid } from 'lucide-react';
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
import { usePortal } from '@/hooks/use-portal';
import { index as organizations } from '@/routes/admin/organizations';
import { index as projects } from '@/routes/admin/projects';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { portal, homeUrl } = usePortal();

    const mainNavItems: NavItem[] = [
        {
            title: portal === 'client' ? 'Launchpad' : 'Mission Control',
            href: homeUrl,
            icon: LayoutGrid,
            exact: true,
        },
        ...(portal === 'admin'
            ? [
                  { title: 'Clients', href: organizations(), icon: Building2 },
                  { title: 'Projects', href: projects(), icon: FolderKanban },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeUrl} prefetch>
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
