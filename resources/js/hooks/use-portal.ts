import { usePage } from '@inertiajs/react';
import { dashboard } from '@/routes/admin';
import { home } from '@/routes/client';
import type { Portal } from '@/types';

/**
 * The signed-in user's portal and its home page.
 */
export function usePortal(): { portal: Portal | null; homeUrl: string } {
    const { portal } = usePage().props;

    return {
        portal,
        homeUrl: portal === 'client' ? home.url() : dashboard.url(),
    };
}
