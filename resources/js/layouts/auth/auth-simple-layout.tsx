import AppLogoIcon from '@/components/app-logo-icon';
import type { AuthLayoutProps } from '@/types';
import launchSite from '../../../images/mission-control/login-hero.webp';

/**
 * Login and other guest pages: the launch tower at dawn (story 48). On wide
 * screens the scene fills the page and the card sits in its open sky; on
 * narrow ones the scene is a band above the card. All text sits on the
 * card, so contrast never depends on the picture.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="auth-stage">
            <img
                src={launchSite}
                width={1600}
                height={900}
                alt=""
                fetchPriority="high"
                decoding="async"
                className="auth-hero"
            />
            <div className="auth-stand">
                <main
                    id="main"
                    tabIndex={-1}
                    className="w-full max-w-sm rounded-2xl bg-card p-6 shadow-xl focus:outline-none sm:p-8"
                >
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4 text-center">
                            <div className="flex flex-col items-center gap-2">
                                <span className="flex size-11 items-center justify-center rounded-full bg-[var(--mark-tile)]">
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
        </div>
    );
}
