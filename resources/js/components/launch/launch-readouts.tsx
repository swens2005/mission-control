import type { Launch } from '@/types';

/**
 * Three readouts under the console, like the CV site's stat cards: a big
 * number, what it counts, and a small gauge (decoration; the text says it).
 */
export function LaunchReadouts({ launch }: { launch: Launch }) {
    const results = launch.checks.results;
    const checksOk = results.filter(
        (result) => result.status === 'pass' || result.waiver !== null,
    ).length;
    const ticked = launch.checklist.filter((item) => item.checked).length;
    const signed = launch.board.rows.filter(
        (row) => row.key.endsWith('signoff') && row.ok,
    ).length;

    const readouts = [
        {
            key: 'checks',
            value: checksOk,
            // Before the first run, show how many checks there will be.
            total: results.length || 10,
            label:
                results.length === 0
                    ? 'automated checks, not run yet'
                    : 'automated checks passed or waived',
        },
        {
            key: 'checklist',
            value: ticked,
            total: launch.checklist.length,
            label: 'checklist items ticked',
        },
        { key: 'signoffs', value: signed, total: 2, label: 'sign-offs' },
    ];

    return (
        <ul className="grid gap-4 sm:grid-cols-3">
            {readouts.map((readout) => (
                <li
                    key={readout.key}
                    className="lc-card relative overflow-hidden px-5 pt-5 pb-4"
                >
                    <Gauge value={readout.value} total={readout.total} />
                    <p className="pr-14 font-display text-4xl leading-none font-extrabold tabular-nums">
                        {readout.value}
                        <span className="text-xl text-muted-foreground">
                            /{readout.total}
                        </span>
                    </p>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {readout.label}
                    </p>
                </li>
            ))}
        </ul>
    );
}

/** A semicircle gauge with a needle, after the CV site's stat gauges. */
function Gauge({ value, total }: { value: number; total: number }) {
    const fraction = total === 0 ? 0 : Math.min(1, value / total);
    // The needle swings from left (empty) to right (full) around (25, 26),
    // stopping just short of flat so it never reads as a minus sign.
    const angle = Math.PI * (0.92 - 0.84 * fraction);
    const x = 25 + 13 * Math.cos(angle);
    const y = 26 - 13 * Math.sin(angle);

    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 50 30"
            className="absolute top-3 right-3 h-[30px] w-[50px]"
        >
            <path className="lc-gauge-track" d="M5 26 A20 20 0 0 1 45 26" />
            {fraction > 0 && (
                <path
                    className="lc-gauge-value"
                    d="M5 26 A20 20 0 0 1 45 26"
                    pathLength={1}
                    strokeDasharray={`${fraction} 1`}
                />
            )}
            <line
                x1="25"
                y1="26"
                x2={x.toFixed(2)}
                y2={y.toFixed(2)}
                className="stroke-foreground"
                strokeWidth="2.5"
                strokeLinecap="round"
            />
        </svg>
    );
}
