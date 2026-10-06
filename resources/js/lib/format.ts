/**
 * A calendar date (YYYY-MM-DD) as "12 Nov 2026". Parsed as a local date, so
 * it never shifts a day because of the viewer's time zone.
 */
export function formatDate(date: string | null, fallback = 'Not set'): string {
    if (!date) {
        return fallback;
    }

    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/** An ISO timestamp as "6 Oct 2026, 14:05" in the viewer's time zone. */
export function formatDateTime(
    timestamp: string | null,
    fallback = 'an unknown date',
): string {
    if (!timestamp) {
        return fallback;
    }

    return new Date(timestamp).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
