import type { Auth, Portal } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            /** Autopilot speed in percent; 0 in tests (ADR 0011). */
            tourSpeed: number;
            portal: Portal | null;
            workspace: {
                name: string;
                isSandbox: boolean;
                expiresAt: string | null;
            } | null;
            [key: string]: unknown;
        };
    }
}
