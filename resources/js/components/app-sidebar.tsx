import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import {
    ActivityIcon,
    ClientsIcon,
    FlightDeckIcon,
    LibraryIcon,
    ProjectsIcon,
} from '@/components/mission-icons';
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
import { index as activity } from '@/routes/admin/activity';
import { index as lineItems } from '@/routes/admin/line-items';
import { index as organizations } from '@/routes/admin/organizations';
import { index as projects } from '@/routes/admin/projects';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { portal, homeUrl } = usePortal();

    const mainNavItems: NavItem[] = [
        {
            title: portal === 'client' ? 'Launchpad' : 'Flight deck',
            href: homeUrl,
            icon: FlightDeckIcon,
            exact: true,
        },
        ...(portal === 'admin'
            ? [
                  {
                      title: 'Clients',
                      href: organizations(),
                      icon: ClientsIcon,
                  },
                  {
                      title: 'Projects',
                      href: projects(),
                      icon: ProjectsIcon,
                      tour: 'nav-projects',
                  },
                  {
                      title: 'Scope library',
                      href: lineItems(),
                      icon: LibraryIcon,
                      tour: 'scope-library',
                  },
                  { title: 'Activity', href: activity(), icon: ActivityIcon },
              ]
            : []),
    ];

    return (
        <Sidebar
            collapsible="icon"
            variant="sidebar"
            role="navigation"
            aria-label="Main"
        >
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
