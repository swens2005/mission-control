import { router } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { TourBar } from '@/components/tour/tour-bar';
import { TourCursor } from '@/components/tour/tour-cursor';
import { focusById } from '@/components/form-field';
import { domAdapter } from '@/lib/tour/dom';
import type { Box, Point } from '@/lib/tour/dom';
import { TourRunner } from '@/lib/tour/runner';
import type { TourState, TourTiming } from '@/lib/tour/runner';
import type { Tour } from '@/lib/tour/types';
import { findTour } from '@/tours';

const STORAGE_KEY = 'mission-control.tour';

type Saved = { tourId: string; index: number };

type TourContextValue = {
    /** Starts a tour from the beginning, or from a step. */
    start: (tour: Tour, index?: number) => void;
    active: boolean;
};

const TourContext = createContext<TourContextValue>({
    start: () => {},
    active: false,
});

export function useTour(): TourContextValue {
    return useContext(TourContext);
}

/**
 * Hosts the autopilot above the Inertia pages (ADR 0011), so a tour keeps
 * running across visits, the login and the portal switch.
 */
export function TourProvider({ children }: { children: ReactNode }) {
    const [tour, setTour] = useState<Tour | null>(null);
    const [state, setState] = useState<TourState | null>(null);
    const [cursor, setCursor] = useState<{ point: Point; ms: number } | null>(
        null,
    );
    const [ring, setRing] = useState<Box | null>(null);
    const runnerRef = useRef<TourRunner<HTMLElement> | null>(null);
    const barRef = useRef<HTMLDivElement | null>(null);
    const timingRef = useRef<TourTiming>({
        speed: 100,
        reducedMotion: prefersReducedMotion(),
    });

    // The speed is a shared Inertia prop; tests run at 0 (story 33).
    useEffect(
        () =>
            router.on('navigate', (event) => {
                const speed = event.detail.page.props.tourSpeed;

                if (typeof speed === 'number') {
                    timingRef.current = { ...timingRef.current, speed };
                    runnerRef.current?.setTiming(timingRef.current);
                }
            }),
        [],
    );

    const begin = useCallback(
        (next: Tour, index: number, autoplay: boolean) => {
            runnerRef.current?.exit();

            const runner = new TourRunner(
                next.steps,
                domAdapter({
                    cursor: (point, ms) => setCursor({ point, ms }),
                    ring: setRing,
                }),
                timingRef.current,
                (current) => {
                    setState(current);
                    save(next.id, current);
                },
                index,
            );

            runnerRef.current = runner;
            setTour(next);
            setState(runner.current);

            if (autoplay) {
                void runner.play();
            } else {
                // Back after a reload: offer to carry on rather than
                // jumping straight into action.
                runner.hold('The page reloaded. Resume the tour?');
            }
        },
        [],
    );

    // Resume after a full page reload.
    useEffect(() => {
        const saved = load();
        const savedTour = saved ? findTour(saved.tourId) : undefined;

        if (saved && savedTour) {
            begin(savedTour, saved.index, false);
        }
    }, [begin]);

    const exit = useCallback(() => {
        // The bar is about to disappear; don't leave keyboard focus on a
        // button that no longer exists.
        const focusWasInBar =
            barRef.current?.contains(document.activeElement) ||
            document.activeElement === document.body;

        runnerRef.current?.exit();
        runnerRef.current = null;
        clear();
        setTour(null);
        setState(null);
        setCursor(null);
        setRing(null);

        if (focusWasInBar) {
            focusById('main');

            // If the tour was mid-visit, the next page replaces main; catch
            // focus there too unless that page focuses something itself.
            const off = router.on('navigate', () => {
                off();

                if (document.activeElement === document.body) {
                    focusById('main');
                }
            });
            window.setTimeout(off, 5000);
        }
    }, []);

    const active =
        state !== null && state.status !== 'exited' && state.status !== 'idle';

    // The visitor's own input pauses the tour; Esc ends it.
    useEffect(() => {
        if (!active) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (!event.isTrusted) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                exit();

                return;
            }

            if (!barRef.current?.contains(event.target as Node)) {
                runnerRef.current?.pause('You took over. Resume?');
            }
        };
        const onPointer = (event: PointerEvent) => {
            if (
                event.isTrusted &&
                !barRef.current?.contains(event.target as Node)
            ) {
                runnerRef.current?.pause('You took over. Resume?');
            }
        };

        document.addEventListener('keydown', onKey, true);
        document.addEventListener('pointerdown', onPointer, true);

        return () => {
            document.removeEventListener('keydown', onKey, true);
            document.removeEventListener('pointerdown', onPointer, true);
        };
    }, [active, exit]);

    // Keep page content clear of the bar.
    useEffect(() => {
        document.body.style.paddingBottom = active ? '9rem' : '';
    }, [active]);

    const value = useMemo<TourContextValue>(
        () => ({
            start: (next, index = 0) => begin(next, index, true),
            active,
        }),
        [begin, active],
    );

    return (
        <TourContext.Provider value={value}>
            {children}
            {active && tour && state && (
                <>
                    <TourCursor
                        cursor={cursor}
                        ring={ring}
                        playing={state.status === 'playing'}
                    />
                    <TourBar
                        ref={barRef}
                        tour={tour}
                        state={state}
                        onPause={() => runnerRef.current?.pause()}
                        onResume={() => {
                            const runner = runnerRef.current;

                            if (runner?.current.status === 'lost') {
                                void runner.play();
                            } else {
                                runner?.resume();
                            }
                        }}
                        onNext={() => void runnerRef.current?.next()}
                        onRestart={() => begin(tour, 0, true)}
                        onExit={exit}
                    />
                </>
            )}
        </TourContext.Provider>
    );
}

function prefersReducedMotion(): boolean {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
}

function save(tourId: string, state: TourState): void {
    try {
        if (state.status === 'exited' || state.status === 'done') {
            sessionStorage.removeItem(STORAGE_KEY);
        } else {
            sessionStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({ tourId, index: state.index } satisfies Saved),
            );
        }
    } catch {
        // Storage can be off (private mode); the tour just won't resume.
    }
}

function load(): Saved | null {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        const saved = raw ? (JSON.parse(raw) as Partial<Saved>) : null;

        return saved &&
            typeof saved.tourId === 'string' &&
            typeof saved.index === 'number'
            ? { tourId: saved.tourId, index: saved.index }
            : null;
    } catch {
        return null;
    }
}

function clear(): void {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
    } catch {
        // See save().
    }
}
