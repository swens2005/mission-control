import type { InertiaLinkProps } from '@inertiajs/react';
import type { ComponentType } from 'react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: ComponentType<{ className?: string }> | null;
    isActive?: boolean;
    /** Only highlight on this exact URL, not on pages below it. */
    exact?: boolean;
    /** A data-tour hook for the autopilot. */
    tour?: string;
};
