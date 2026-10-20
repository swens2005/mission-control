import { formatDate } from '@/lib/format';
import type { Intake } from '@/types';

/**
 * The intake's answers, read-only. Used by both portals (story 25).
 */
export function IntakeAnswers({
    intake,
    compact = false,
}: {
    intake: Intake;
    /** One column, for the narrow side panel next to the proposal. */
    compact?: boolean;
}) {
    const features = [
        ...intake.featureLabels,
        ...(intake.featuresOther ? [intake.featuresOther] : []),
    ];

    return (
        <div
            className={compact ? 'grid gap-4' : 'grid gap-5 lg:grid-cols-2'}
            data-tour="intake-answers"
        >
            <AnswerCard
                number="01"
                title="Goals"
                className={compact ? '' : 'lg:col-span-2'}
            >
                <p className="whitespace-pre-line">
                    {intake.goals || 'Not answered.'}
                </p>
            </AnswerCard>

            <AnswerCard number="02" title="Pages">
                {intake.pages.length > 0 ? (
                    <ol className="list-decimal space-y-1 pl-5">
                        {intake.pages.map((page, i) => (
                            <li key={i}>{page}</li>
                        ))}
                    </ol>
                ) : (
                    <p>Not answered.</p>
                )}
            </AnswerCard>

            <AnswerCard number="03" title="Features">
                {features.length > 0 ? (
                    <ul className="list-disc space-y-1 pl-5">
                        {features.map((feature, i) => (
                            <li key={i}>{feature}</li>
                        ))}
                    </ul>
                ) : (
                    <p>None ticked.</p>
                )}
            </AnswerCard>

            <AnswerCard number="04" title="Budget">
                <p>{intake.budgetLabel ?? 'Not answered.'}</p>
            </AnswerCard>

            <AnswerCard number="05" title="Deadline">
                <p>{formatDate(intake.deadlineOn, 'No deadline')}</p>
                {intake.deadlineReason && (
                    <p className="text-sm text-muted-foreground">
                        {intake.deadlineReason}
                    </p>
                )}
            </AnswerCard>
        </div>
    );
}

function AnswerCard({
    number,
    title,
    className = '',
    children,
}: {
    number: string;
    title: string;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <section className={`lc-card lc-edge-ink space-y-2 p-5 ${className}`}>
            <h3 className="lc-label text-foreground">
                <span aria-hidden="true" className="text-status-pass">
                    ●
                </span>{' '}
                {number} · {title}
            </h3>
            {children}
        </section>
    );
}
