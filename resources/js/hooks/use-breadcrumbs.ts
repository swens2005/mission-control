import { setLayoutProps } from '@inertiajs/react';
import { useEffect } from 'react';
import type { BreadcrumbItem } from '@/types';

/**
 * Breadcrumbs that depend on page props (e.g. a client's name). Static ones
 * can use `Page.layout = { breadcrumbs: [...] }` instead.
 */
export function useBreadcrumbs(breadcrumbs: BreadcrumbItem[]): void {
    const key = JSON.stringify(breadcrumbs);

    useEffect(() => {
        setLayoutProps({ breadcrumbs: JSON.parse(key) as BreadcrumbItem[] });
    }, [key]);
}
