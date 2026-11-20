import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

/** The sidebar brand: the rocket mark on its tile, the name and the studio. */
export default function AppLogo() {
    const { workspace } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-[var(--mark-tile)] ring-1 ring-[var(--mark-rim)]">
                <AppLogoIcon className="size-6" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate font-display text-sm leading-tight font-extrabold">
                    Mission Control
                </span>
                {workspace && (
                    <span className="truncate font-mono text-[10px] tracking-[0.14em] text-sidebar-muted uppercase">
                        {workspace.name}
                    </span>
                )}
            </div>
        </>
    );
}
