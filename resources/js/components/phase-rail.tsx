import { cn } from '@/lib/utils';
import type { Option } from '@/types';

/**
 * The five phases as a rail of bars, from Scope to Launched (story 46).
 * A real ordered list: each step says in text whether it is done, current
 * or still to come, so the bar colors are never the only signal. On phones
 * the five labels collapse into one written-out line.
 */
export function PhaseRail({
    steps,
    step,
    label,
    className,
}: {
    steps: Option[];
    /** 1-based; the last step means launched. */
    step: number;
    label: string;
    className?: string;
}) {
    const current = steps[step - 1]?.label ?? '';

    return (
        <div className={className}>
            <ol className="phase-rail" aria-label={label}>
                {steps.map((item, index) => {
                    const number = index + 1;
                    const state =
                        number < step
                            ? 'done'
                            : number === step
                              ? 'current'
                              : 'to come';

                    return (
                        <li
                            key={item.value}
                            className={cn(
                                state === 'done' && 'phase-done',
                                state === 'current' && 'phase-now',
                            )}
                            aria-current={
                                state === 'current' ? 'step' : undefined
                            }
                        >
                            <span aria-hidden="true" className="phase-bar" />
                            <span className="phase-label">
                                {item.label}
                                <span className="sr-only">, {state}</span>
                            </span>
                        </li>
                    );
                })}
            </ol>
            {/* Phones: one line instead of five squeezed labels. */}
            <p aria-hidden="true" className="phase-rail-line">
                {current} · step {step} of {steps.length}
            </p>
        </div>
    );
}
