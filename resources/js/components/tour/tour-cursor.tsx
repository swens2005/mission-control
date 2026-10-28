import type { Box, Point } from '@/lib/tour/dom';

/**
 * The autopilot's fake cursor and the ring around what it acts on. Both are
 * positioned with the style prop (CSSOM), which the strict CSP allows
 * (ADR 0006); hidden from screen readers, which get the captions instead.
 */
export function TourCursor({
    cursor,
    ring,
    playing,
}: {
    cursor: { point: Point; ms: number } | null;
    ring: Box | null;
    playing: boolean;
}) {
    return (
        <div aria-hidden="true" data-print="hide">
            {ring && playing && (
                <div
                    className="pointer-events-none fixed z-[59] rounded-xl outline-3 outline-offset-0 outline-sidebar-primary"
                    style={{
                        top: ring.top,
                        left: ring.left,
                        width: ring.width,
                        height: ring.height,
                    }}
                />
            )}
            {cursor && (
                <svg
                    viewBox="0 0 24 24"
                    className="pointer-events-none fixed top-0 left-0 z-[61] size-7 drop-shadow"
                    style={{
                        transform: `translate(${cursor.point.x}px, ${cursor.point.y}px)`,
                        transitionProperty: 'transform',
                        transitionDuration: `${cursor.ms}ms`,
                        transitionTimingFunction: 'cubic-bezier(.3,.7,.3,1)',
                    }}
                >
                    <path
                        d="M3 2l17 10-7.5 1.5L9 21z"
                        className="fill-sidebar stroke-sidebar-foreground"
                        strokeWidth="1.5"
                        strokeLinejoin="round"
                    />
                </svg>
            )}
        </div>
    );
}
