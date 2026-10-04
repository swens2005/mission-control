import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SandboxBanner } from '@/components/sandbox-banner';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePortal } from '@/hooks/use-portal';
import { cn } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';

/**
 * Launchpad: the client portal. A riso mission-poster top bar (ADR 0005).
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

            <header className="border-b-2 border-foreground bg-card">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">
                    <Link
                        href={homeUrl}
                        className="flex items-center gap-3 font-display text-xl font-extrabold tracking-tight uppercase [font-stretch:125%]"
                    >
                        <span
                            aria-hidden="true"
                            className="riso-overprint inline-block size-6"
                        />
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
                                            'block rounded-sm px-3 py-2 text-sm font-semibold underline-offset-4 hover:underline',
                                            item.active &&
                                                'bg-foreground text-background hover:no-underline',
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
                                <DropdownMenuTrigger className="flex items-center gap-1 rounded-sm px-2 py-2 text-sm font-semibold">
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
                <div aria-hidden="true" className="riso-halftone h-2" />
            </header>

            <main
                id="main"
                tabIndex={-1}
                className="mx-auto max-w-5xl px-4 py-8 focus:outline-none"
            >
                {children}
            </main>
        </div>
    );
}
