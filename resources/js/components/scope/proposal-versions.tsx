import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { formatDate } from '@/lib/format';
import type { ProposalVersion } from '@/types';

/**
 * The list of versions, newest first, each with its own read-only page.
 */
export function ProposalVersions({
    versions,
    currentId,
    hrefFor,
}: {
    versions: ProposalVersion[];
    /** The version shown on this page, marked rather than linked. */
    currentId: number | null;
    hrefFor: (id: number) => NonNullable<InertiaLinkProps['href']>;
}) {
    if (versions.length < 2) {
        return null;
    }

    return (
        <section
            aria-labelledby="versions-heading"
            className="space-y-2"
            data-print="hide"
        >
            <h3
                id="versions-heading"
                className="lc-label text-muted-foreground"
            >
                Versions
            </h3>
            <ul className="divide-y rounded-2xl border bg-card text-sm">
                {versions.map((version) => (
                    <li
                        key={version.id}
                        className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-2.5"
                    >
                        {version.id === currentId ? (
                            <span aria-current="page" className="font-semibold">
                                v{version.number} (shown above)
                            </span>
                        ) : (
                            <Link
                                href={hrefFor(version.id)}
                                className="font-semibold text-primary underline"
                            >
                                v{version.number}
                            </Link>
                        )}
                        <span className="text-muted-foreground tabular-nums">
                            {version.statusLabel}
                            {version.sentAt &&
                                ` · ${formatDate(version.sentAt.slice(0, 10))}`}
                            {version.total && ` · ${version.total}`}
                        </span>
                    </li>
                ))}
            </ul>
        </section>
    );
}
