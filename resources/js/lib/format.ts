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
