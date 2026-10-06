import { Form } from '@inertiajs/react';
import { CircleCheck, CircleDashed } from 'lucide-react';
import { focusFirstError } from '@/components/form-field';
import { cn } from '@/lib/utils';
import type { GoBoardState } from '@/types';

type FormDefinition = { action: string; method: 'post' };

// The sign-off form disappears once signed; keep keyboard users on the board.
const focusStatus = () =>
    requestAnimationFrame(() =>
        document.getElementById('board-status')?.focus(),
    );

const bright =
    'rounded-[var(--radius)] bg-space-go px-4 py-2 font-semibold text-space focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-space-foreground disabled:opacity-70';

/**
 * The go/no-go board in deep-space colors. The status is a word first
 * (GO, NO-GO, CLEAR, LAUNCHED); color is the second signal.
 */
export function GoBoard({
    board,
    signForm,
    launchedForm,
}: {
    board: GoBoardState;
    signForm: FormDefinition;
    launchedForm?: FormDefinition;
}) {
    const positive = board.status === 'GO' || board.status === 'LAUNCHED';

    return (
        <section
            aria-labelledby="board-heading"
            className="space-y-5 rounded-[var(--radius)] border border-space-border bg-space p-5 text-space-foreground"
            data-tour="go-board"
        >
            <div>
                <h2
                    id="board-heading"
                    className="font-mono text-xs tracking-[0.14em] text-space-muted uppercase"
                >
                    Go / no-go
                </h2>
                <div
                    id="board-status"
                    role="status"
                    tabIndex={-1}
                    data-tour="go-status"
                    className="mt-2 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-space-go"
                >
                    <p
                        className={cn(
                            'font-mono text-4xl font-bold tracking-wider',
                            positive
                                ? 'text-space-go'
                                : board.status === 'CLEAR'
                                  ? 'text-space-foreground'
                                  : 'text-space-nogo',
                        )}
                    >
                        {board.status}
                    </p>
                    <p className="mt-1">{board.headline}</p>
                </div>
            </div>

            <ul className="divide-y divide-space-border border-y border-space-border">
                {board.rows.map((row) => {
                    const Icon = row.ok ? CircleCheck : CircleDashed;

                    return (
                        <li
                            key={row.key}
                            className="flex flex-wrap items-start gap-x-3 gap-y-1 py-3"
                        >
                            <Icon
                                aria-hidden="true"
                                className={cn(
                                    'mt-0.5 size-5 shrink-0',
                                    row.ok
                                        ? 'text-space-go'
                                        : 'text-space-muted',
                                )}
                            />
                            <div className="min-w-0 flex-1">
                                <p className="font-semibold">
                                    {row.label}
                                    <span className="sr-only">:</span>{' '}
                                    <span
                                        className={cn(
                                            'font-mono text-xs tracking-[0.14em] uppercase',
                                            row.ok
                                                ? 'text-space-go'
                                                : 'text-space-muted',
                                        )}
                                    >
                                        {row.ok ? 'Ready' : 'Not yet'}
                                    </span>
                                </p>
                                <p className="text-sm text-space-muted">
                                    {row.detail.charAt(0).toUpperCase() +
                                        row.detail.slice(1)}
                                </p>
                            </div>
                        </li>
                    );
                })}
            </ul>

            {board.canSign && (
                <Form
                    {...signForm}
                    onError={focusFirstError}
                    onSuccess={focusStatus}
                    options={{ preserveScroll: true }}
                    className="space-y-3"
                    data-tour="sign-off"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <label
                                    htmlFor="signoff-name"
                                    className="font-semibold"
                                >
                                    Sign off for the{' '}
                                    {board.signAs.toLowerCase()}
                                </label>
                                <p
                                    id="signoff-name-hint"
                                    className="text-sm text-space-muted"
                                >
                                    Type your name as it appears on your
                                    account: {board.expectedName}. Signing
                                    records your name, the time and your IP
                                    address.
                                </p>
                                <input
                                    id="signoff-name"
                                    name="name"
                                    required
                                    maxLength={120}
                                    autoComplete="name"
                                    aria-invalid={
                                        errors.name ? true : undefined
                                    }
                                    aria-describedby={cn(
                                        'signoff-name-hint',
                                        errors.name && 'signoff-name-error',
                                    )}
                                    className="h-10 w-full max-w-sm rounded-[var(--radius)] border border-space-muted bg-transparent px-3 text-space-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-space-go"
                                />
                                {errors.name && (
                                    <p
                                        id="signoff-name-error"
                                        className="text-sm font-semibold text-space-nogo"
                                    >
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <button className={bright} disabled={processing}>
                                Sign off
                            </button>
                        </>
                    )}
                </Form>
            )}

            {board.canMarkLaunched && launchedForm && (
                <Form
                    {...launchedForm}
                    options={{ preserveScroll: true }}
                    onSuccess={focusStatus}
                >
                    <button className={bright} data-tour="mark-launched">
                        Mark as launched
                    </button>
                </Form>
            )}
        </section>
    );
}
