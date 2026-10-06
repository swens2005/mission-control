import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLogoIcon from '@/components/app-logo-icon';
import { SandboxBanner } from '@/components/sandbox-banner';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePortal } from '@/hooks/use-portal';
import { cn } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';

/**
 * Launchpad: the client portal. Same codelaunch.nl look as Mission Control
 * (ADR 0008): a navy bar with the rocket, and a day sky over the page.
 */
export default function ClientLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;
    const { homeUrl } = usePortal();
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const navItems = [
        { title: 'Projects', href: homeUrl, active: isCurrentUrl(homeUrl) },
        {
            title: 'Account',
            href: editProfile.url(),
            active: isCurrentOrParentUrl('/settings'),
        },
    ];

    return (
        <div className="min-h-svh bg-background">
            <a href="#main" className="skip-link">
                Skip to content
            </a>

            <SandboxBanner />

            <header className="bg-sidebar text-sidebar-foreground">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">
                    <Link
                        href={homeUrl}
                        className="flex items-center gap-3 rounded-lg font-display text-xl font-extrabold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sidebar-ring"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground">
                            <AppLogoIcon className="size-5" />
                        </span>
                        Launchpad
                    </Link>

                    <nav
                        aria-label="Main"
                        className="order-last w-full sm:order-none sm:w-auto"
                    >
                        <ul className="flex gap-1">
                            {navItems.map((item) => (
                                <li key={item.title}>
                                    <Link
                                        href={item.href}
                                        prefetch
                                        aria-current={
                                            item.active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'block rounded-lg px-3 py-2 text-sm font-semibold text-sidebar-foreground hover:bg-sidebar-accent focus-visible:outline-2 focus-visible:outline-sidebar-ring',
                                            item.active &&
                                                'bg-sidebar-accent text-sidebar-accent-foreground shadow-[inset_0_-3px_0_var(--sidebar-primary)]',
                                        )}
                                    >
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {auth.user && (
                        <div className="ml-auto">
                            <DropdownMenu>
                                <DropdownMenuTrigger className="flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-sidebar-accent focus-visible:outline-2 focus-visible:outline-sidebar-ring">
                                    <span className="max-w-[18ch] truncate">
                                        {auth.user.name}
                                    </span>
                                    <ChevronDown
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="end"
                                    className="min-w-56"
                                >
                                    <UserMenuContent user={auth.user} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    )}
                </div>
            </header>

            <div className="sky-fade">
                <main
                    id="main"
                    tabIndex={-1}
                    className="mx-auto max-w-5xl px-4 py-10 focus:outline-none"
                >
                    {children}
                </main>
            </div>
        </div>
    );
}
