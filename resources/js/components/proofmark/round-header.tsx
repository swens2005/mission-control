import type { ReactNode } from 'react';
import type { ReviewRound } from '@/types';
import { RoundHud } from './round-status';

/**
 * "Light table · Round v2" with a sentence about the round, the HUD, and
 * room for actions (send, approve).
 */
export function RoundHeader({
    round,
    description,
    children,
}: {
    round: ReviewRound;
    description: ReactNode;
    children?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-end justify-between gap-4">
            <div className="min-w-0 space-y-1">
                <p className="lc-label text-muted-foreground">Light table</p>
                <h2
                    id="round-heading"
                    tabIndex={-1}
                    className="text-2xl font-extrabold"
                >
                    Round {round.label}
                </h2>
                <div className="text-sm text-muted-foreground">
                    {description}
                </div>
                {children && <div className="pt-3">{children}</div>}
            </div>
            <RoundHud
                label={round.label}
                status={round.status}
                statusLabel={round.statusLabel}
            />
        </div>
    );
}
