import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';

/**
 * Pages shared by link, without login (the public style guide, story 24):
 * the Launchpad bar and sky, but no navigation and no account menu.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-svh bg-background">
            <a href="#main" className="skip-link">
                Skip to content
            </a>
            <header className="bg-sidebar text-sidebar-foreground">
                <div className="mx-auto flex max-w-5xl items-center gap-3 px-4 py-3 font-display text-xl font-extrabold tracking-tight">
                    <span className="flex size-9 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground">
                        <AppLogoIcon className="size-5" />
                    </span>
                    Style guide
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
