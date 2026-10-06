import { cn } from '@/lib/utils';
import type { RoundStatus } from '@/types';

/** Card edge per round status; the status is always written out too. */
export const roundEdge: Record<RoundStatus, string> = {
    draft: 'lc-edge-ink',
    in_review: 'lc-edge-warn',
    approved: 'lc-edge-pass',
    superseded: 'lc-edge-skip',
};

const lamp: Record<RoundStatus, string> = {
    draft: 'lc-lamp',
    in_review: 'lc-lamp lc-lamp-warn lc-lamp-lit',
    approved: 'lc-lamp lc-lamp-go lc-lamp-lit',
    superseded: 'lc-lamp',
};

/**
 * "v2 · In review" in the HUD style, with a lamp as a second signal.
 */
export function RoundHud({
    label,
    status,
    statusLabel,
    className,
}: {
    label: string;
    status: RoundStatus;
    statusLabel: string;
    className?: string;
}) {
    return (
        <div
            id="round-status"
            tabIndex={-1}
            className={cn('lc-hud inline-flex items-center gap-4', className)}
            data-tour="round-status"
        >
            <div>
                <p className="lc-label opacity-80">Round</p>
                <p className="lc-hud-digits">{label}</p>
            </div>
            <p className="flex items-center gap-2 font-mono text-sm font-semibold tracking-wide uppercase">
                <span aria-hidden="true" className={lamp[status]} />
                {statusLabel}
            </p>
        </div>
    );
}
