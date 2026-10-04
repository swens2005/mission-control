import type { ProjectPhase } from '@/types';

/**
 * The project's phase as text in a pill. The colored dot is decoration;
 * the label carries the meaning.
 */
export function PhaseBadge({
    phase,
    label,
}: {
    phase: ProjectPhase;
    label: string;
}) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-2.5 py-0.5 text-xs font-medium whitespace-nowrap">
            <span
                aria-hidden="true"
                className={
                    phase === 'launched'
                        ? 'size-2 rounded-full bg-primary'
                        : 'size-2 rounded-full bg-brand-accent'
                }
            />
            {label}
        </span>
    );
}
