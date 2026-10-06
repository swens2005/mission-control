import { Form } from '@inertiajs/react';
import { Rocket } from 'lucide-react';
import { focusFirstError } from '@/components/form-field';
import { cn } from '@/lib/utils';
import type { GoBoardState } from '@/types';

type FormDefinition = { action: string; method: 'post' };

// The sign form disappears once signed; keep keyboard users on the board.
const focusStatus = () =>
    requestAnimationFrame(() =>
        document.getElementById('board-status')?.focus(),
    );

const lampFor: Record<GoBoardState['status'], string> = {
    GO: 'lc-lamp-go',
    LAUNCHED: 'lc-lamp-go',
    CLEAR: 'lc-lamp-clear',
    'NO-GO': 'lc-lamp-nogo',
};

/**
 * The go/no-go board as the codelaunch.nl cockpit: a riveted console with
 * a briefing screen, a big status lamp, a T-minus readout and one lamp per
 * system. Status words are always written out; lamps are decoration.
 */
export function MissionConsole({
    projectName,
    url,
    targetLaunchOn,
    board,
    signForm,
    launchedForm,
}: {
    projectName: string;
    url: string;
    targetLaunchOn: string | null;
    board: GoBoardState;
    signForm: FormDefinition;
    launchedForm?: FormDefinition;
}) {
    const tMinus = daysUntil(targetLaunchOn);

    return (
        <section
            aria-labelledby="board-heading"
            className="lc-console space-y-5"
            data-tour="go-board"
        >
            <div className="grid gap-5 md:grid-cols-[1.4fr_1fr]">
                <div className="lc-screen">
                    <h2
                        id="board-heading"
                        className="lc-label text-screen-label"
                    >
                        Mission briefing · Go / no-go
                    </h2>
                    <p className="mt-2 font-display text-2xl leading-tight font-extrabold text-screen-ink">
                        {projectName}
                    </p>
                    <p className="mt-1 text-sm break-all">{url}</p>
                    <div
                        id="board-status"
                        role="status"
                        tabIndex={-1}
                        data-tour="go-status"
                        className="mt-3 font-mono text-[13px] font-medium text-screen-status focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-screen-ink"
                    >
                        <span aria-hidden="true">&gt; </span>
                        <span className="font-bold">{board.status}</span>
                        <span aria-hidden="true"> · </span>
                        <span className="sr-only">: </span>
                        {board.headline}
                        <span aria-hidden="true" className="lc-cursor" />
                    </div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-5 md:flex-col md:items-stretch md:justify-center">
                    <div className="flex items-center gap-4">
                        <span
                            aria-hidden="true"
                            className={cn(
                                'lc-lamp lc-lamp-big',
                                lampFor[board.status],
                            )}
                        />
                        <p className="font-mono text-3xl font-bold tracking-wider">
                            {board.status}
                        </p>
                    </div>
                    <div className="lc-hud min-w-[9rem]">
                        <p className="lc-label text-sidebar-muted">
                            {tMinus === null
                                ? 'Launch date'
                                : tMinus >= 0
                                  ? 'T-minus'
                                  : 'T-plus'}
                        </p>
                        <p>
                            <span className="lc-hud-digits">
                                {tMinus === null
                                    ? '--'
                                    : String(Math.abs(tMinus)).padStart(2, '0')}
                            </span>{' '}
                            <span className="text-sm text-sidebar-muted">
                                {tMinus === null
                                    ? 'not set'
                                    : Math.abs(tMinus) === 1
                                      ? 'day'
                                      : 'days'}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <p className="lc-label mb-2 text-muted-foreground">Systems</p>
                <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    {board.rows.map((row) => (
                        <li
                            key={row.key}
                            className="flex items-start gap-3 rounded-2xl border border-console-edge bg-console-shade p-3"
                        >
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'lc-lamp mt-1',
                                    row.ok && 'lc-lamp-go lc-lamp-lit',
                                )}
                            />
                            <div className="min-w-0">
                                <p className="text-sm leading-tight font-bold">
                                    {row.label}
                                    <span className="sr-only">
                                        : {row.ok ? 'ready' : 'not yet'}.
                                    </span>
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {sentence(row.detail)}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>

            {board.canSign && (
                <Form
                    {...signForm}
                    onError={focusFirstError}
                    onSuccess={focusStatus}
                    options={{ preserveScroll: true }}
                    className="flex flex-wrap items-end gap-3 border-t border-dashed border-console-edge pt-5"
                    data-tour="sign-off"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid min-w-0 flex-1 basis-64 gap-1.5">
                                <label
                                    htmlFor="signoff-name"
                                    className="font-bold"
                                >
                                    Sign off for the{' '}
                                    {board.signAs.toLowerCase()}
                                </label>
                                <p
                                    id="signoff-name-hint"
                                    className="text-sm text-muted-foreground"
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
                                    className="h-11 w-full rounded-xl border border-input bg-card px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:border-destructive"
                                />
                                {errors.name && (
                                    <p
                                        id="signoff-name-error"
                                        className="text-sm font-medium text-destructive"
                                    >
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <button className="lc-pill" disabled={processing}>
                                <span
                                    aria-hidden="true"
                                    className="lc-lamp lc-lamp-clear"
                                />
                                Sign off
                            </button>
                        </>
                    )}
                </Form>
            )}

            {board.canMarkLaunched && launchedForm && (
                <Form
                    {...launchedForm}
                    onSuccess={focusStatus}
                    options={{ preserveScroll: true }}
                    className="border-t border-dashed border-console-edge pt-5"
                >
                    <button
                        className="lc-pill text-lg"
                        data-tour="mark-launched"
                    >
                        <Rocket aria-hidden="true" className="size-5" />
                        Mark as launched
                    </button>
                </Form>
            )}
        </section>
    );
}

/** Whole days from today to a YYYY-MM-DD date; negative once it's past. */
function daysUntil(date: string | null): number | null {
    if (!date) {
        return null;
    }

    const [year, month, day] = date.split('-').map(Number);
    const target = new Date(year, month - 1, day);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return Math.round((target.getTime() - today.getTime()) / 86_400_000);
}

function sentence(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}
