import { Form } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import ProposalDraftController from '@/actions/App/Http/Controllers/Admin/ProposalDraftController';
import { FormField, focusById, focusFirstError } from '@/components/form-field';
import { Input } from '@/components/ui/input';
import { BAND, constellation } from '@/lib/constellation';
import type { AiAssistant, Proposal } from '@/types';
import emblem from '../../../images/navigator/emblem.webp';
import shootingStar from '../../../images/navigator/shooting-star.webp';

const keep = { preserveScroll: true, preserveState: true } as const;

const textareaClassName =
    'nv-field w-full px-3 py-2 font-sans text-[0.98rem] leading-relaxed aria-invalid:border-destructive';

/** A four-point star, centered on 0,0 in a 20 × 20 box. */
const STAR = 'M0-9 2.2-2.2 9 0 2.2 2.2 0 9-2.2 2.2-9 0-2.2-2.2z';

/**
 * Navigator, "Draft with AI" (story 42): Claude suggests lines from the
 * library for the intake; the studio reviews every one before anything is
 * added. Styled as a star chart (approved mock-up, 2026-11-18).
 */
export function AiDraftPanel({
    proposal,
    ai,
}: {
    proposal: Proposal;
    ai: AiAssistant;
}) {
    return ai.draft ? (
        <Review proposal={proposal} draft={ai.draft} />
    ) : (
        <Start proposal={proposal} ai={ai} />
    );
}

function Panel({
    headingId,
    busy = false,
    tour,
    band,
    dim,
    children,
}: {
    headingId: string;
    busy?: boolean;
    tour?: string;
    band?: ReactNode;
    dim?: 'cap' | 'demo';
    children: ReactNode;
}) {
    return (
        <section
            aria-labelledby={headingId}
            aria-busy={busy || undefined}
            className="nv"
            data-print="hide"
            data-tour={tour}
        >
            <div className="nv-band" aria-hidden="true" data-dim={dim}>
                {band}
                <div className="nv-tag">
                    <img src={emblem} alt="" width={34} height={34} />
                    <span>
                        <b>Navigator</b>
                        <small>part of Mission Control</small>
                    </span>
                </div>
            </div>
            <div className="nv-body">{children}</div>
        </section>
    );
}

function StarIcon() {
    return (
        <svg viewBox="-10 -10 20 20" aria-hidden="true" fill="currentColor">
            <path d={STAR} />
        </svg>
    );
}

function Start({ proposal, ai }: { proposal: Proposal; ai: AiAssistant }) {
    return (
        <Form
            {...ProposalDraftController.store.form(proposal.id)}
            options={keep}
            onError={() => focusById('ai-draft-start')}
            onSuccess={() => focusById('ai-line-0-keep', 'ai-draft-heading')}
        >
            {({ processing, errors }) => {
                const problem = errors.ai ?? ai.refusal;
                const resting = ai.paused && !processing;

                return (
                    <Panel
                        headingId="ai-start-heading"
                        busy={processing}
                        dim={
                            resting
                                ? 'cap'
                                : ai.refusal !== null
                                  ? 'demo'
                                  : undefined
                        }
                        band={processing ? <Drafting /> : undefined}
                    >
                        <header>
                            <p className="lc-label nv-kicker">
                                Scope · AI assistant
                                {processing && ' · drafting'}
                                {resting && ' · paused'}
                            </p>
                            <h3 id="ai-start-heading" className="nv-title">
                                {processing
                                    ? 'Plotting the course'
                                    : resting
                                      ? 'Navigator is resting until next month'
                                      : 'Let Navigator plot a first draft'}
                            </h3>
                            <p className="nv-soft max-w-[68ch]">
                                {processing ? (
                                    'Navigator is reading the intake and your library.'
                                ) : resting ? (
                                    'Every AI draft costs a little, so drafting stops at a fixed monthly budget.'
                                ) : (
                                    <>
                                        Claude reads the client’s intake and
                                        picks lines from your library, with a
                                        quantity and a reason for each.{' '}
                                        <strong>
                                            Prices always come from your
                                            library.
                                        </strong>{' '}
                                        You review every line; nothing is added
                                        or sent until you say so.
                                    </>
                                )}
                            </p>
                        </header>

                        {ai.remaining !== null &&
                            ai.refusal === null &&
                            !processing && (
                                <p className="nv-msg">
                                    <span>
                                        <b>Demo</b>
                                        This demo has {ai.remaining} AI{' '}
                                        {ai.remaining === 1
                                            ? 'draft'
                                            : 'drafts'}{' '}
                                        left.
                                    </span>
                                </p>
                            )}
                        {problem && !processing && (
                            <p
                                id="ai-error"
                                className="nv-msg"
                                data-lamp={
                                    errors.ai || ai.paused ? 'coral' : 'amber'
                                }
                            >
                                <span>
                                    <b>
                                        {errors.ai
                                            ? 'Not drafted'
                                            : ai.paused
                                              ? 'Paused'
                                              : 'Demo limit reached'}
                                    </b>
                                    {problem}
                                </span>
                            </p>
                        )}

                        <div className="nv-actions">
                            <button
                                id="ai-draft-start"
                                className="nv-btn"
                                disabled={processing || ai.refusal !== null}
                                aria-describedby={
                                    problem && !processing
                                        ? 'ai-error'
                                        : undefined
                                }
                                data-tour="ai-draft"
                            >
                                <StarIcon />
                                {processing ? 'Drafting…' : 'Draft with AI'}
                            </button>
                            {ai.refusal !== null && (
                                <span className="nv-soft">
                                    You can still build the proposal by hand
                                    below.
                                </span>
                            )}
                        </div>
                        <p role="status" className="min-h-[1.6em]">
                            {processing
                                ? 'Drafting from the intake. This can take up to half a minute.'
                                : ''}
                        </p>
                    </Panel>
                );
            }}
        </Form>
    );
}

/** The band while drafting: a shooting star and three twinkling stars. */
function Drafting() {
    return (
        <>
            <img
                className="nv-sweep"
                src={shootingStar}
                alt=""
                width={240}
                height={135}
            />
            <svg
                viewBox={`0 0 ${BAND.width} ${BAND.height}`}
                preserveAspectRatio="xMidYMid slice"
                className="nv-sky"
            >
                {[
                    [120, 70],
                    [300, 48],
                    [430, 90],
                ].map(([x, y]) => (
                    <g key={x} className="nv-star">
                        <path
                            d={STAR}
                            className="nv-twinkle"
                            transform={`translate(${x} ${y}) scale(0.55)`}
                        />
                    </g>
                ))}
            </svg>
        </>
    );
}

/** The band in review: one star per suggested line, joined in order. */
function Sky({ kept, hot }: { kept: boolean[]; hot: number | null }) {
    const stars = constellation(kept.length);

    return (
        <svg
            viewBox={`0 0 ${BAND.width} ${BAND.height}`}
            preserveAspectRatio="xMidYMid meet"
            className="nv-sky"
        >
            <g className="nv-grid">
                <circle cx="390" cy="75" r="40" />
                <circle cx="390" cy="75" r="80" />
                <path d="M390 -10v170M200 75h390M318 3l144 144M462 3 318 147" />
            </g>
            {stars.slice(1).map((star, i) => (
                <line
                    key={i}
                    x1={stars[i].x}
                    y1={stars[i].y}
                    x2={star.x}
                    y2={star.y}
                    data-off={!kept[i] || !kept[i + 1] || undefined}
                />
            ))}
            {stars.map((star, i) => (
                <g
                    key={i}
                    className="nv-star"
                    data-off={!kept[i] || undefined}
                    data-hot={hot === i || undefined}
                >
                    <path
                        d={STAR}
                        transform={`translate(${star.x} ${star.y}) scale(${star.size / 20})`}
                    />
                    <text x={star.x - 3} y={star.y - star.size / 2 - 4}>
                        {i + 1}
                    </text>
                </g>
            ))}
        </svg>
    );
}

function Review({
    proposal,
    draft,
}: {
    proposal: Proposal;
    draft: NonNullable<AiAssistant['draft']>;
}) {
    const replacesIntroduction = (proposal.introduction ?? '').trim() !== '';
    const [kept, setKept] = useState(() => draft.lines.map(() => true));
    const [hot, setHot] = useState<number | null>(null);

    return (
        <Panel
            headingId="ai-draft-heading"
            tour="ai-draft-review"
            band={<Sky kept={kept} hot={hot} />}
        >
            <header>
                <p className="lc-label nv-kicker">
                    Scope · AI draft · not added yet
                </p>
                <h3 id="ai-draft-heading" tabIndex={-1} className="nv-title">
                    Review the AI draft
                </h3>
                <p className="nv-soft">
                    Keep, change or switch off each line, then add the ones you
                    keep. Prices come from your library.
                </p>
                {draft.dropped > 0 && (
                    <p className="nv-soft mt-1.5 text-sm">
                        {draft.dropped === 1
                            ? '1 suggestion was'
                            : `${draft.dropped} suggestions were`}{' '}
                        left out: not in your library, or an impossible
                        quantity.
                    </p>
                )}
            </header>

            <Form
                {...ProposalDraftController.apply.form(proposal.id)}
                options={keep}
                onError={focusFirstError}
                onSuccess={() => focusById('lines-heading')}
                className="grid gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        {draft.lines.length === 0 ? (
                            <p>
                                The assistant didn’t suggest any lines from your
                                library.
                            </p>
                        ) : (
                            <ol
                                className="grid gap-2.5"
                                aria-label="Suggested lines"
                            >
                                {draft.lines.map((line, i) => (
                                    <li
                                        key={line.lineItemId}
                                        className="nv-line"
                                        data-off={!kept[i] || undefined}
                                        onMouseEnter={() => setHot(i)}
                                        onMouseLeave={() => setHot(null)}
                                        onFocus={() => setHot(i)}
                                        onBlur={() => setHot(null)}
                                    >
                                        <span
                                            className="nv-badge"
                                            aria-hidden="true"
                                        >
                                            {i + 1}
                                        </span>
                                        <input
                                            type="hidden"
                                            name={`lines[${i}][line_item_id]`}
                                            value={line.lineItemId}
                                        />
                                        <div className="flex flex-wrap items-center justify-between gap-x-3.5 gap-y-1.5">
                                            <span className="grid">
                                                <span className="nv-line-name">
                                                    {line.name}
                                                </span>
                                                <span className="nv-soft font-mono text-xs">
                                                    {line.price}
                                                </span>
                                            </span>
                                            <label className="flex cursor-pointer items-center gap-2.5 font-semibold">
                                                <input
                                                    type="checkbox"
                                                    role="switch"
                                                    id={`ai-line-${i}-keep`}
                                                    name={`lines[${i}][keep]`}
                                                    value="1"
                                                    checked={kept[i]}
                                                    onChange={(event) => {
                                                        const on =
                                                            event.target
                                                                .checked;
                                                        setKept((all) =>
                                                            all.map((k, j) =>
                                                                j === i
                                                                    ? on
                                                                    : k,
                                                            ),
                                                        );
                                                    }}
                                                    className="nv-switch"
                                                    aria-describedby={`ai-line-${i}-reason`}
                                                />
                                                <span>
                                                    Keep{' '}
                                                    <span className="sr-only">
                                                        {line.name}
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                        <p
                                            id={`ai-line-${i}-reason`}
                                            className="nv-why text-[0.95rem]"
                                        >
                                            <em>
                                                Why
                                                <span className="sr-only">
                                                    :{' '}
                                                </span>
                                            </em>
                                            {line.reason || 'no reason given'}
                                        </p>
                                        <div className="flex flex-wrap items-end gap-x-3 gap-y-2">
                                            <FormField
                                                name={`lines[${i}][quantity]`}
                                                id={`ai-line-${i}-quantity`}
                                                label={
                                                    line.kind === 'hourly'
                                                        ? `Hours for ${line.name}`
                                                        : `Quantity of ${line.name}`
                                                }
                                                error={
                                                    errors[
                                                        `lines.${i}.quantity`
                                                    ]
                                                }
                                            >
                                                {(props) => (
                                                    <Input
                                                        {...props}
                                                        defaultValue={
                                                            line.quantity
                                                        }
                                                        inputMode="decimal"
                                                        autoComplete="off"
                                                        className="nv-field w-32"
                                                        data-tour="ai-line-quantity"
                                                    />
                                                )}
                                            </FormField>
                                            <span className="nv-state pb-2">
                                                {kept[i] ? 'Kept' : 'Not kept'}
                                            </span>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        )}

                        <div
                            role="group"
                            aria-labelledby="ai-totals-label"
                            className="grid gap-2"
                        >
                            <p id="ai-totals-label" className="sr-only">
                                Draft totals
                            </p>
                            <dl className="nv-totals">
                                <div className="nv-readout">
                                    <dt>Subtotal</dt>
                                    <dd>{draft.totals.subtotal}</dd>
                                </div>
                                <div className="nv-readout">
                                    <dt>VAT 21%</dt>
                                    <dd>{draft.totals.vat}</dd>
                                </div>
                                <div className="nv-readout" data-total>
                                    <dt>Total</dt>
                                    <dd>{draft.totals.total}</dd>
                                </div>
                            </dl>
                            <p className="nv-soft text-sm">
                                Every suggested line at its drafted quantity,
                                before any discount. The proposal’s own totals
                                update once you add the lines.
                            </p>
                        </div>

                        {draft.introduction !== '' && (
                            <div className="grid gap-3">
                                <label className="flex cursor-pointer items-center gap-2.5 font-semibold">
                                    <input
                                        type="checkbox"
                                        role="switch"
                                        id="ai-use-introduction"
                                        name="use_introduction"
                                        value="1"
                                        defaultChecked={!replacesIntroduction}
                                        className="nv-switch"
                                        aria-describedby={
                                            replacesIntroduction
                                                ? 'ai-introduction-hint'
                                                : undefined
                                        }
                                    />
                                    Use this introduction
                                </label>
                                {replacesIntroduction && (
                                    <p
                                        id="ai-introduction-hint"
                                        className="nv-soft text-sm"
                                    >
                                        This replaces the introduction you
                                        already wrote.
                                    </p>
                                )}
                                <FormField
                                    name="introduction"
                                    id="ai-introduction"
                                    label="Suggested introduction"
                                    error={errors.introduction}
                                >
                                    {(props) => (
                                        <textarea
                                            {...props}
                                            rows={6}
                                            defaultValue={draft.introduction}
                                            className={textareaClassName}
                                        />
                                    )}
                                </FormField>
                            </div>
                        )}

                        {errors.ai && (
                            <p
                                id="ai-apply-error"
                                className="nv-msg"
                                data-lamp="coral"
                            >
                                <span>
                                    <b>Not added</b>
                                    {errors.ai}
                                </span>
                            </p>
                        )}
                        <div className="nv-actions">
                            <button
                                type="submit"
                                className="nv-btn"
                                data-tone="go"
                                disabled={processing}
                                aria-describedby={
                                    errors.ai ? 'ai-apply-error' : undefined
                                }
                                data-tour="ai-draft-add"
                            >
                                Add the kept lines
                            </button>
                        </div>
                    </>
                )}
            </Form>

            <Form
                {...ProposalDraftController.destroy.form(proposal.id)}
                options={keep}
                onSuccess={() => focusById('ai-draft-start')}
            >
                {({ processing }) => (
                    <button
                        type="submit"
                        className="nv-btn"
                        data-tone="cream"
                        disabled={processing}
                    >
                        Discard the draft
                    </button>
                )}
            </Form>
        </Panel>
    );
}
