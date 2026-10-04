import { createInertiaApp, router } from '@inertiajs/react';
import { setNonce } from 'get-nonce';
import { FlashToaster } from '@/components/flash-toaster';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Mission Control';

// Radix's scroll lock (dialogs, menus) injects a <style> tag; give it this
// page's CSP nonce, which Vite put on our own script tags (story 07).
setNonce(
    document.querySelector<HTMLScriptElement>('script[nonce]')?.nonce ?? '',
);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
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
                {app}
                <FlashToaster />
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
