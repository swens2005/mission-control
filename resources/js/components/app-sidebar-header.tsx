import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * Page header with breadcrumbs and a small HUD readout, in the style of the
 * codelaunch.nl altimeter.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { workspace } = usePage().props;

    return (
        <header className="flex min-h-16 shrink-0 flex-wrap items-center justify-between gap-x-6 gap-y-2 border-b border-border px-4 py-3 md:px-6">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            {workspace && (
                <dl
                    aria-label="Status"
                    className="flex items-center gap-x-5 text-muted-foreground"
                >
                    <div className="flex items-baseline gap-2">
                        <dt className="hud-label">Studio</dt>
                        <dd className="max-w-[16ch] truncate font-mono text-xs text-foreground">
                            {workspace.name}
                        </dd>
                    </div>
                    <div className="flex items-baseline gap-2">
                        <dt className="hud-label">Status</dt>
                        <dd className="flex items-center gap-1.5 font-mono text-xs text-foreground">
                            <span
                                aria-hidden="true"
                                className="size-2 rounded-full bg-brand-accent"
                            />
                            {workspace.isSandbox ? 'Demo' : 'Nominal'}
                        </dd>
                    </div>
                </dl>
            )}
        </header>
    );
}
