import { Pause, Play, RotateCcw, SkipForward, X } from 'lucide-react';
import { forwardRef } from 'react';
import type { TourState } from '@/lib/tour/runner';
import type { Tour } from '@/lib/tour/types';

const buttonClass =
    'inline-flex min-h-9 items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold text-sidebar-foreground hover:bg-sidebar-accent focus-visible:outline-2 focus-visible:outline-sidebar-ring disabled:opacity-50';

/**
 * The autopilot's controls and captions (story 30). Captions are read out
 * politely; the visitor can pause, skip, restart or leave at any time.
 */
export const TourBar = forwardRef<
    HTMLDivElement,
    {
        tour: Tour;
        state: TourState;
        onPause: () => void;
        onResume: () => void;
        onNext: () => void;
        onRestart: () => void;
        onExit: () => void;
    }
>(function TourBar(
    { tour, state, onPause, onResume, onNext, onRestart, onExit },
    ref,
) {
    const finished = state.status === 'done';
    const stopped = state.status === 'paused' || state.status === 'lost';
    const step = Math.min(state.index + 1, state.total);

    return (
        <div
            ref={ref}
            role="region"
            aria-label="Autopilot"
            data-print="hide"
            className="lc-hud fixed inset-x-2 bottom-2 z-[60] mx-auto max-w-3xl space-y-2 shadow-lg sm:inset-x-4 sm:bottom-4"
        >
            <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <p className="lc-label text-sidebar-muted">
                    Autopilot · {tour.title}
                    {state.chapter &&
                        state.chapter !== tour.title &&
                        ` · ${state.chapter}`}
                </p>
                <p className="lc-label text-sidebar-muted tabular-nums">
                    {finished ? 'The end' : `Step ${step} of ${state.total}`}
                </p>
            </div>

            <p
                aria-live="polite"
                aria-atomic="true"
                className="min-h-[3rem] text-base leading-snug"
            >
                {state.message ??
                    state.caption ??
                    (finished
                        ? 'That’s the tour. The demo studio is yours now: take the controls.'
                        : '…')}
            </p>

            <div className="flex flex-wrap items-center gap-1">
                {!finished &&
                    (stopped ? (
                        <button
                            type="button"
                            className={buttonClass}
                            onClick={onResume}
                        >
                            <Play aria-hidden="true" className="size-4" />
                            {state.status === 'lost' ? 'Try again' : 'Resume'}
                        </button>
                    ) : (
                        <button
                            type="button"
                            className={buttonClass}
                            onClick={onPause}
                        >
                            <Pause aria-hidden="true" className="size-4" />
                            Pause
                        </button>
                    ))}
                {!finished && (
                    <button
                        type="button"
                        className={buttonClass}
                        onClick={onNext}
                    >
                        <SkipForward aria-hidden="true" className="size-4" />
                        Next
                    </button>
                )}
                <button
                    type="button"
                    className={buttonClass}
                    onClick={onRestart}
                >
                    <RotateCcw aria-hidden="true" className="size-4" />
                    Restart
                </button>
                <button
                    type="button"
                    className={`${buttonClass} ml-auto`}
                    onClick={onExit}
                    aria-keyshortcuts="Escape"
                >
                    <X aria-hidden="true" className="size-4" />
                    {finished ? 'Close' : 'Exit (Esc)'}
                </button>
            </div>
        </div>
    );
});
