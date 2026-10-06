import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Option } from '@/types';

/**
 * The four phases as a real ordered list. Each step says in text whether it
 * is done, current or still to come, so color is never the only signal.
 * `step` is 1-based; 5 means launched (every step done).
 */
export function PhaseSteps({
    steps,
    step,
    label,
}: {
    steps: Option[];
    step: number;
    label: string;
}) {
    return (
        <ol aria-label={label} className="grid gap-2 sm:grid-cols-4">
            {steps.map((item, index) => {
                const number = index + 1;
                const state =
                    number < step
                        ? 'done'
                        : number === step
                          ? 'current'
                          : 'upcoming';

                return (
                    <li
                        key={item.value}
                        aria-current={state === 'current' ? 'step' : undefined}
                        className={cn(
                            'flex items-center gap-2 rounded-xl border-2 px-3 py-2 text-sm',
                            state === 'current' &&
                                'border-brand-accent bg-card font-semibold shadow-sm',
                            state === 'done' && 'border-transparent bg-muted',
                            state === 'upcoming' &&
                                'border-dashed border-input text-muted-foreground',
                        )}
                    >
                        <span
                            aria-hidden="true"
                            className={cn(
                                'flex size-6 shrink-0 items-center justify-center rounded-full font-mono text-xs',
                                state === 'done' &&
                                    'bg-primary text-primary-foreground',
                                state === 'current' &&
                                    'lc-lamp lc-lamp-go lc-lamp-lit size-4',
                                state === 'upcoming' && 'border border-input',
                            )}
                        >
                            {state === 'done' ? (
                                <Check className="size-3.5" />
                            ) : state === 'upcoming' ? (
                                number
                            ) : null}
                        </span>
                        <span>
                            <span className="sr-only">
                                {`Step ${number} of ${steps.length}: `}
                            </span>
                            {item.label}
                            <span className="sr-only">
                                {state === 'done'
                                    ? ', done'
                                    : state === 'current'
                                      ? ', current'
                                      : ', to come'}
                            </span>
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
