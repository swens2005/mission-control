import type { ActivityItem } from '@/types';

const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 365 * 24 * 3600],
    ['month', 30 * 24 * 3600],
    ['week', 7 * 24 * 3600],
    ['day', 24 * 3600],
    ['hour', 3600],
    ['minute', 60],
];

const relative = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
const exact = new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

/** "2 hours ago", or "just now" within the last minute. */
export function timeAgo(iso: string, now = Date.now()): string {
    const seconds = Math.round((new Date(iso).getTime() - now) / 1000);

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}

/**
 * An activity feed. Each entry shows a relative time, with the exact time in
 * the <time> element's datetime and title for screen readers and hover.
 */
export function ActivityList({
    entries,
    showProject = false,
    emptyText = 'Nothing has happened yet.',
}: {
    entries: ActivityItem[];
    showProject?: boolean;
    emptyText?: string;
}) {
    if (entries.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyText}</p>;
    }

    return (
        <ol className="space-y-3">
            {entries.map((entry) => (
                <li
                    key={entry.id}
                    className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-l-2 border-brand-accent pl-3 text-sm"
                >
                    <p>
                        <span className="font-semibold">{entry.actor}</span>{' '}
                        {entry.description}
                        {showProject && entry.project && (
                            <span className="text-muted-foreground">
                                {' · '}
                                {entry.project.name}
                            </span>
                        )}
                    </p>
                    <time
                        dateTime={entry.createdAt}
                        title={exact.format(new Date(entry.createdAt))}
                        className="font-mono text-xs whitespace-nowrap text-muted-foreground"
                    >
                        {timeAgo(entry.createdAt)}
                    </time>
                </li>
            ))}
        </ol>
    );
}
