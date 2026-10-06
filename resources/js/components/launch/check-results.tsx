import { Form } from '@inertiajs/react';
import { CircleCheck, CircleMinus, CircleX, TriangleAlert } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import CheckWaiverController from '@/actions/App/Http/Controllers/Admin/CheckWaiverController';
import { FormField, focusFirstError } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { usePortal } from '@/hooks/use-portal';
import { cn } from '@/lib/utils';
import type { CheckResultItem, CheckStatus, LaunchChecks } from '@/types';

const statusStyle: Record<
    CheckStatus,
    { icon: LucideIcon; className: string }
> = {
    pass: { icon: CircleCheck, className: 'text-status-pass' },
    warn: { icon: TriangleAlert, className: 'text-status-warn' },
    fail: { icon: CircleX, className: 'text-status-fail' },
    skipped: { icon: CircleMinus, className: 'text-muted-foreground' },
};

/**
 * The latest automated check run. Status is always written out next to its
 * icon and color. With a projectId, the studio can waive and withdraw.
 */
export function CheckResults({
    checks,
    projectId,
}: {
    checks: LaunchChecks;
    projectId?: number;
}) {
    const { portal } = usePortal();

    if (checks.latestRun === null) {
        return (
            <p className="text-muted-foreground">The checks haven't run yet.</p>
        );
    }

    return (
        <ul
            className={cn(
                'divide-y bg-card',
                portal === 'client'
                    ? 'divide-foreground rounded-sm border-2 border-foreground'
                    : 'rounded-lg border',
            )}
            data-tour="check-results"
        >
            {checks.results.map((result) => (
                <ResultRow
                    key={result.key}
                    result={result}
                    projectId={projectId}
                />
            ))}
        </ul>
    );
}

function ResultRow({
    result,
    projectId,
}: {
    result: CheckResultItem;
    projectId?: number;
}) {
    const style = statusStyle[result.status];
    const Icon = style.icon;
    const canWaive =
        projectId !== undefined &&
        (result.status === 'fail' || result.status === 'warn');

    return (
        <li className="space-y-2 p-4" data-tour="check-result">
            <div className="flex flex-wrap items-start gap-x-3 gap-y-1">
                <Icon
                    aria-hidden="true"
                    className={cn('mt-0.5 size-5 shrink-0', style.className)}
                />
                <div className="min-w-0 flex-1">
                    <h3 className="font-semibold">
                        {result.label}
                        <span className="sr-only">:</span>{' '}
                        <span className={cn('ml-1 text-sm', style.className)}>
                            {result.statusLabel}
                            {result.waiver && ', waived'}
                        </span>
                    </h3>
                    <p className="text-sm">{result.message}</p>
                    {result.details.length > 0 && (
                        <ul className="mt-1 list-disc pl-5 text-sm text-muted-foreground">
                            {result.details.map((detail) => (
                                <li key={detail} className="break-words">
                                    {detail}
                                </li>
                            ))}
                        </ul>
                    )}
                    {result.waiver && (
                        <p className="mt-2 text-sm">
                            <span className="font-semibold">Waived:</span>{' '}
                            {result.waiver.reason}{' '}
                            <span className="text-muted-foreground">
                                ({result.waiver.by})
                            </span>
                        </p>
                    )}
                </div>
            </div>

            {canWaive && result.waiver === null && (
                <details className="pl-8">
                    <summary
                        className="w-fit cursor-pointer text-sm font-semibold text-primary underline"
                        data-tour="waive"
                    >
                        Waive this check
                    </summary>
                    <Form
                        {...CheckWaiverController.store.form({
                            project: projectId,
                            check: result.key,
                        })}
                        errorBag={`waive-${result.key}`}
                        onError={focusFirstError}
                        options={{ preserveScroll: true }}
                        className="mt-3 max-w-lg space-y-3"
                    >
                        {({ processing, errors }) => (
                            <>
                                <FormField
                                    name="reason"
                                    id={`waive-${result.key}`}
                                    label={`Why is it fine to launch without fixing ${result.label}?`}
                                    error={errors.reason}
                                >
                                    {(props) => (
                                        <textarea
                                            {...props}
                                            required
                                            minLength={5}
                                            maxLength={500}
                                            rows={2}
                                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:border-destructive"
                                        />
                                    )}
                                </FormField>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={processing}
                                >
                                    Waive
                                </Button>
                            </>
                        )}
                    </Form>
                </details>
            )}

            {projectId !== undefined && result.waiver !== null && (
                <Form
                    {...CheckWaiverController.destroy.form({
                        project: projectId,
                        check: result.key,
                    })}
                    options={{ preserveScroll: true }}
                    className="pl-8"
                >
                    <Button variant="ghost" size="sm">
                        Withdraw waiver
                    </Button>
                </Form>
            )}
        </li>
    );
}

/** "Last run 6 Oct, 12:30 by Sam Visser: 10 passed." */
export function RunSummary({ checks }: { checks: LaunchChecks }) {
    const run = checks.latestRun;

    if (run === null) {
        return null;
    }

    return (
        <p className="text-sm text-muted-foreground">
            Last run {formatDateTime(run.ranAt)} by {run.ranBy}:{' '}
            <span className="font-semibold text-foreground">{run.summary}</span>
            .
            {checks.previousRunAt &&
                ` Previous run ${formatDateTime(checks.previousRunAt)}.`}
        </p>
    );
}

function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
