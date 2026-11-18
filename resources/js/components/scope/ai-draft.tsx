import { Form } from '@inertiajs/react';
import { Sparkles, Trash2 } from 'lucide-react';
import ProposalDraftController from '@/actions/App/Http/Controllers/Admin/ProposalDraftController';
import { FormField, focusById, focusFirstError } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { AiAssistant, Proposal } from '@/types';

const keep = { preserveScroll: true, preserveState: true } as const;

const textareaClassName =
    'w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs aria-invalid:border-destructive';

const checkboxClassName = 'mt-1 size-5 shrink-0 accent-primary';

/**
 * "Draft with AI" (story 42): Claude suggests lines from the library for
 * the intake; the studio reviews every one before anything is added.
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

function Start({ proposal, ai }: { proposal: Proposal; ai: AiAssistant }) {
    return (
        <Form
            {...ProposalDraftController.store.form(proposal.id)}
            options={keep}
            onError={() => focusById('ai-draft-start')}
            onSuccess={() => focusById('ai-line-0-keep', 'ai-draft-heading')}
            className="lc-card lc-edge-ink space-y-3 p-5"
            data-print="hide"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="lc-label text-muted-foreground">
                        Scope · AI assistant
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        Claude reads the client’s intake and picks lines from
                        your library, with a quantity and a reason for each.
                        Prices always come from your library. You review every
                        line; nothing is added or sent until you say so.
                    </p>
                    {ai.remaining !== null && ai.refusal === null && (
                        <p className="text-sm text-muted-foreground">
                            This demo has {ai.remaining} AI{' '}
                            {ai.remaining === 1 ? 'draft' : 'drafts'} left.
                        </p>
                    )}
                    {(errors.ai ?? ai.refusal) && (
                        <p
                            id="ai-error"
                            className="text-sm font-medium text-destructive"
                        >
                            {errors.ai ?? ai.refusal}
                        </p>
                    )}
                    <button
                        id="ai-draft-start"
                        className="lc-pill"
                        disabled={processing || ai.refusal !== null}
                        aria-describedby={
                            (errors.ai ?? ai.refusal) ? 'ai-error' : undefined
                        }
                        data-tour="ai-draft"
                    >
                        <Sparkles aria-hidden="true" className="size-5" />
                        {processing ? 'Drafting…' : 'Draft with AI'}
                    </button>
                    <p role="status" className="text-sm text-muted-foreground">
                        {processing
                            ? 'Drafting from the intake. This can take up to half a minute.'
                            : ''}
                    </p>
                </>
            )}
        </Form>
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

    return (
        <section
            aria-labelledby="ai-draft-heading"
            className="lc-card lc-edge-warn space-y-4 p-5"
            data-print="hide"
            data-tour="ai-draft-review"
        >
            <div className="space-y-1">
                <p className="lc-label text-muted-foreground">
                    Scope · AI draft · not added yet
                </p>
                <h3
                    id="ai-draft-heading"
                    tabIndex={-1}
                    className="text-xl font-bold"
                >
                    Review the AI draft
                </h3>
                <p className="text-sm text-muted-foreground">
                    Keep, change or untick each line, then add the ones you
                    keep. Prices come from your library.
                </p>
                {draft.dropped > 0 && (
                    <p className="text-sm text-muted-foreground">
                        {draft.dropped === 1
                            ? '1 suggestion was'
                            : `${draft.dropped} suggestions were`}{' '}
                        left out: not in your library, or an impossible
                        quantity.
                    </p>
                )}
            </div>

            <Form
                {...ProposalDraftController.apply.form(proposal.id)}
                options={keep}
                onError={focusFirstError}
                onSuccess={() => focusById('lines-heading')}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        {draft.lines.length === 0 ? (
                            <p className="text-sm">
                                The assistant didn’t suggest any lines from your
                                library.
                            </p>
                        ) : (
                            <ol
                                className="space-y-3"
                                aria-label="Suggested lines"
                            >
                                {draft.lines.map((line, i) => (
                                    <li
                                        key={line.lineItemId}
                                        className="grid gap-3 rounded-xl border border-border bg-card p-4"
                                    >
                                        <input
                                            type="hidden"
                                            name={`lines[${i}][line_item_id]`}
                                            value={line.lineItemId}
                                        />
                                        <div className="flex items-start gap-3">
                                            <input
                                                type="checkbox"
                                                id={`ai-line-${i}-keep`}
                                                name={`lines[${i}][keep]`}
                                                value="1"
                                                defaultChecked
                                                className={checkboxClassName}
                                                aria-describedby={`ai-line-${i}-reason`}
                                            />
                                            <label
                                                htmlFor={`ai-line-${i}-keep`}
                                                className="min-w-0"
                                            >
                                                <span className="font-semibold">
                                                    Keep {line.name}
                                                </span>{' '}
                                                <span className="text-sm text-muted-foreground">
                                                    · {line.price}
                                                </span>
                                            </label>
                                        </div>
                                        <p
                                            id={`ai-line-${i}-reason`}
                                            className="text-sm text-muted-foreground"
                                        >
                                            Why:{' '}
                                            {line.reason || 'no reason given'}
                                        </p>
                                        <FormField
                                            name={`lines[${i}][quantity]`}
                                            id={`ai-line-${i}-quantity`}
                                            label={
                                                line.kind === 'hourly'
                                                    ? `Hours for ${line.name}`
                                                    : `Quantity of ${line.name}`
                                            }
                                            error={
                                                errors[`lines.${i}.quantity`]
                                            }
                                        >
                                            {(props) => (
                                                <Input
                                                    {...props}
                                                    defaultValue={line.quantity}
                                                    inputMode="decimal"
                                                    autoComplete="off"
                                                    className="w-32 tabular-nums"
                                                    data-tour="ai-line-quantity"
                                                />
                                            )}
                                        </FormField>
                                    </li>
                                ))}
                            </ol>
                        )}

                        {draft.introduction !== '' && (
                            <div className="grid gap-3">
                                <div className="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="ai-use-introduction"
                                        name="use_introduction"
                                        value="1"
                                        defaultChecked={!replacesIntroduction}
                                        className={checkboxClassName}
                                        aria-describedby={
                                            replacesIntroduction
                                                ? 'ai-introduction-hint'
                                                : undefined
                                        }
                                    />
                                    <label
                                        htmlFor="ai-use-introduction"
                                        className="font-semibold"
                                    >
                                        Use this introduction
                                    </label>
                                </div>
                                {replacesIntroduction && (
                                    <p
                                        id="ai-introduction-hint"
                                        className="text-sm text-muted-foreground"
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
                                className="text-sm font-medium text-destructive"
                            >
                                {errors.ai}
                            </p>
                        )}
                        <Button
                            disabled={processing}
                            aria-describedby={
                                errors.ai ? 'ai-apply-error' : undefined
                            }
                            data-tour="ai-draft-add"
                        >
                            Add the kept lines
                        </Button>
                    </>
                )}
            </Form>

            <Form
                {...ProposalDraftController.destroy.form(proposal.id)}
                options={keep}
                onSuccess={() => focusById('ai-draft-start')}
            >
                {({ processing }) => (
                    <Button
                        type="submit"
                        variant="secondary"
                        disabled={processing}
                    >
                        <Trash2 aria-hidden="true" />
                        Discard the draft
                    </Button>
                )}
            </Form>
        </section>
    );
}
