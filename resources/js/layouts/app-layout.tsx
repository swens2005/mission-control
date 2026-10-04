import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import ClientLayout from '@/layouts/client/client-layout';
import { usePortal } from '@/hooks/use-portal';
import type { BreadcrumbItem } from '@/types';

/**
 * Mission Control (sidebar) for admins, Launchpad (top bar) for clients.
 */
export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { portal } = usePortal();

    if (portal === 'client') {
        return <ClientLayout>{children}</ClientLayout>;
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            {children}
        </AppSidebarLayout>
    );
}
