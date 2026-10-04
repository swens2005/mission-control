import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

/**
 * Login and other guest pages: the codelaunch.nl sky, from day to space.
 * All text sits on the card, so contrast never depends on the gradient.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="sky flex min-h-svh flex-col items-center justify-center p-4 sm:p-10">
            <main
                id="main"
                className="w-full max-w-sm rounded-2xl bg-card p-6 shadow-xl sm:p-8"
            >
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4 text-center">
                        <div className="flex flex-col items-center gap-2">
                            <span className="flex size-11 items-center justify-center rounded-full bg-foreground text-brand-accent">
                                <AppLogoIcon className="size-6" />
                            </span>
                            <p className="hud-label text-muted-foreground">
                                Mission Control
                            </p>
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-bold">{title}</h1>
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}
