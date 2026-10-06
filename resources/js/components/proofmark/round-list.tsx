import { Link } from '@inertiajs/react';
import type { RouteDefinition } from '@/wayfinder';
import { cn } from '@/lib/utils';
import type { ReviewRoundSummary } from '@/types';
import { roundEdge } from './round-status';

/**
 * The round switcher: every round, newest first, the current one marked
 * with aria-current.
 */
export function RoundList({
    rounds,
    currentId,
    href,
}: {
    rounds: ReviewRoundSummary[];
    currentId: number | null;
    href: (round: ReviewRoundSummary) => RouteDefinition<'get'>;
}) {
    return (
        <nav aria-labelledby="rounds-heading" data-tour="round-list">
            <h2
                id="rounds-heading"
                className="lc-label mb-3 text-muted-foreground"
            >
                Rounds
            </h2>
            <ol className="flex gap-3 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible">
                {rounds.map((round) => {
                    const current = round.id === currentId;

                    return (
                        <li key={round.id} className="shrink-0">
                            <Link
                                href={href(round)}
                                preserveScroll
                                aria-current={current ? 'page' : undefined}
                                className={cn(
                                    'lc-card flex min-w-36 items-baseline gap-3 px-4 py-3 hover:bg-muted focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                    roundEdge[round.status],
                                    current &&
                                        'ring-2 ring-foreground ring-offset-2 ring-offset-background',
                                )}
                            >
                                <span className="font-mono text-2xl font-semibold">
                                    {round.label}
                                </span>
                                <span className="text-sm">
                                    <span className="block font-semibold">
                                        {round.statusLabel}
                                    </span>
                                    <span className="block text-muted-foreground">
                                        {round.designCount === 1
                                            ? '1 design'
                                            : `${round.designCount} designs`}
                                    </span>
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
