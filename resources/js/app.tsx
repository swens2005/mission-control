import { createInertiaApp, router } from '@inertiajs/react';
import { setNonce } from 'get-nonce';
import { FlashToaster } from '@/components/flash-toaster';
import { TourProvider } from '@/components/tour/tour-provider';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Mission Control';

// Radix's scroll lock (dialogs, menus) injects a <style> tag; give it this
// page's CSP nonce, which Vite put on our own script tags (story 07).
setNonce(
    document.querySelector<HTMLScriptElement>('script[nonce]')?.nonce ?? '',
);

// Story 49: a tab left open across a deploy can ask for a page file the
// deploy replaced, and the click then does nothing. Reload once to get the
// new build; at most once a minute, so a truly missing file can't loop.
window.addEventListener('vite:preloadError', (event) => {
    const key = 'mission-control-reloaded-at';

    try {
        const last = Number(window.sessionStorage.getItem(key) ?? 0);

        if (Date.now() - last < 60_000) {
            return;
        }

        window.sessionStorage.setItem(key, String(Date.now()));
    } catch {
        // No storage (private window): better a dead click than a loop.
        return;
    }

    event.preventDefault();
    window.location.reload();
});

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('public/'):
                return PublicLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {/* Above the pages, so a tour survives visits (ADR 0011). */}
                <TourProvider>
                    {app}
                    <FlashToaster />
                </TourProvider>
            </TooltipProvider>
        );
    },
    // Styled in app.css: Inertia's own CSS is injected as a <style> tag,
    // which the strict CSP (story 07) blocks.
    progress: {
        includeCSS: false,
        showSpinner: false,
    },
});

// Keep <html data-portal> in sync on client-side navigation (e.g. after
// logging out), so the right theme applies without a full page load.
router.on('navigate', (event) => {
    document.documentElement.dataset.portal =
        event.detail.page.props.portal ?? 'guest';
});
