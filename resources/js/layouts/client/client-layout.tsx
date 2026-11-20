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
 * Launchpad: the client portal, the viewing stand (story 46). One day sky
 * behind the header and the page, the rocket on a cream tile, pill links.
 */
export default function ClientLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;
    const { homeUrl } = usePortal();
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const navItems = [
        {
            title: 'Your projects',
            href: homeUrl,
            active: isCurrentUrl(homeUrl),
        },
        {
            title: 'Account',
            href: editProfile.url(),
            active: isCurrentOrParentUrl('/settings'),
        },
    ];

    return (
        <div className="viewing-stand min-h-svh">
            <a href="#main" className="skip-link">
                Skip to content
            </a>

            <SandboxBanner />

            <header className="text-foreground" data-print="hide">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">
                    <Link
                        href={homeUrl}
                        className="flex items-center gap-3 rounded-lg font-display text-xl font-extrabold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-[var(--mark-tile)] ring-1 ring-[var(--mark-rim)]">
                            <AppLogoIcon className="size-5" />
                        </span>
                        Launchpad
                    </Link>

                    <nav
                        aria-label="Main"
                        className="order-last w-full sm:order-none sm:w-auto"
                    >
                        <ul className="flex flex-wrap gap-1.5">
                            {navItems.map((item) => (
                                <li key={item.title}>
                                    <Link
                                        href={item.href}
                                        prefetch
                                        aria-current={
                                            item.active ? 'page' : undefined
                                        }
                                        className={cn(
                                            'vs-pill block rounded-full px-3.5 py-2 text-sm font-semibold text-muted-foreground hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                            item.active &&
                                                'bg-card text-foreground',
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
                                <DropdownMenuTrigger className="vs-pill flex items-center gap-1 rounded-full px-3.5 py-2 text-sm font-semibold text-muted-foreground hover:bg-card hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring">
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

            <main
                id="main"
                tabIndex={-1}
                className="mx-auto max-w-5xl px-4 pt-4 pb-16 focus:outline-none"
            >
                {children}
            </main>
        </div>
    );
}
