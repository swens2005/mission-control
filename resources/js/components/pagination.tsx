import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

function cleanLabel(label: string): string {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace(/pagination\./, '')
        .trim();
}

export function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="flex flex-wrap items-center gap-1"
        >
            {page.links.map((link, index) => {
                const label = cleanLabel(link.label);

                return link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={cn(
                            'rounded-md px-3 py-1.5 text-sm hover:bg-muted',
                            link.active &&
                                'bg-foreground text-background hover:bg-foreground',
                        )}
                    >
                        {label}
                    </Link>
                ) : (
                    <span
                        key={index}
                        className="px-3 py-1.5 text-sm text-muted-foreground"
                    >
                        {label}
                    </span>
                );
            })}
        </nav>
    );
}
