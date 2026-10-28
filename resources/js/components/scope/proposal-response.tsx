import { Form } from '@inertiajs/react';
import { BadgeCheck, MessageSquareText } from 'lucide-react';
import { useState } from 'react';
import ClientScopeController from '@/actions/App/Http/Controllers/Client/ScopeController';
import { FormField, focusById, focusFirstError } from '@/components/form-field';
import { PrintButton } from '@/components/scope/proposal-document';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/format';
import type { Proposal } from '@/types';

/**
 * The client's side of a sent proposal (story 29): where it stands, and
 * accepting it or asking for changes.
 */
export function ProposalResponse({
    proposal,
    canRespond,
}: {
    proposal: Proposal;
    canRespond: boolean;
}) {
    const { acceptance, changes } = proposal;
    const edge = acceptance ? 'lc-edge-pass' : 'lc-edge-warn';

    return (
        <div className={`lc-card ${edge} space-y-4 p-5`} data-print="hide">
            <p className="lc-label text-muted-foreground">
                Proposal · v{proposal.number} ·{' '}
                {acceptance
                    ? 'accepted'
                    : changes
                      ? 'changes requested'
                      : 'waiting for you'}
            </p>
            <h2
                id="proposal-status"
                tabIndex={-1}
                className="text-xl font-bold"
                data-tour="proposal-status"
            >
                {acceptance
                    ? `v${proposal.number} · Accepted by ${acceptance.byName ?? 'you'}`
                    : changes
                      ? 'You asked for changes'
                      : `Proposal v${proposal.number} is ready for you`}
            </h2>

            {acceptance ? (
                <p className="text-sm text-muted-foreground">
                    Accepted on {formatDateTime(acceptance.at)}, signed as “
                    {acceptance.signature}”. This version is final; the studio
                    starts work from it.
                </p>
            ) : changes ? (
                <div className="space-y-2 text-sm">
                    <p className="text-muted-foreground">
                        {changes.byName ?? 'You'} wrote on{' '}
                        {formatDateTime(changes.at)}:
                    </p>
                    <blockquote className="border-l-4 border-border pl-3 whitespace-pre-line">
                        {changes.note}
                    </blockquote>
                    <p className="text-muted-foreground">
                        The studio is preparing a new version.
                    </p>
                </div>
            ) : (
                <p className="text-sm text-muted-foreground">
                    {proposal.studioName} sent it
                    {proposal.sentAt &&
                        ` on ${formatDateTime(proposal.sentAt)}`}
                    . Read it through below. When it’s right, accept it; if
                    something should change, tell the studio.
                </p>
            )}

            <div className="flex flex-wrap gap-3">
                {canRespond && (
                    <a href="#respond" className="lc-pill">
                        <BadgeCheck aria-hidden="true" className="size-5" />
                        Go to accept
                    </a>
                )}
                <PrintButton />
            </div>
        </div>
    );
}

/**
 * Accept (typed name and tick box) or ask for changes, below the document
 * so the client reads it first.
 */
export function ProposalRespond({ proposal }: { proposal: Proposal }) {
    const [askingChanges, setAskingChanges] = useState(false);

    return (
        <section
            id="respond"
            aria-label="Your answer"
            className="lc-card lc-edge-pass space-y-5 p-5"
            data-print="hide"
        >
            <Form
                {...ClientScopeController.accept.form(proposal.id)}
                errorBag="accept"
                options={{ preserveScroll: true }}
                onError={focusFirstError}
                onSuccess={() => focusById('proposal-status')}
                className="space-y-4"
                data-tour="accept-proposal"
            >
                {({ processing, errors }) => (
                    <>
                        <h3 className="font-bold">Accept the proposal</h3>
                        <FormField
                            name="signature"
                            id="accept-signature"
                            label="Your full name"
                            hint="Typing your name counts as your signature."
                            error={errors.signature}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    required
                                    maxLength={120}
                                    autoComplete="name"
                                    className="max-w-sm"
                                    data-tour="accept-name"
                                />
                            )}
                        </FormField>
                        <div className="space-y-1">
                            <div className="flex items-start gap-3">
                                <input
                                    id="accept-agree"
                                    type="checkbox"
                                    name="agree"
                                    value="1"
                                    required
                                    autoComplete="off"
                                    className="mt-0.5 size-5 shrink-0 accent-primary"
                                    aria-invalid={
                                        errors.agree ? true : undefined
                                    }
                                    aria-describedby={
                                        errors.agree
                                            ? 'accept-agree-error'
                                            : undefined
                                    }
                                    data-tour="accept-confirm"
                                />
                                <label htmlFor="accept-agree">
                                    I accept this proposal, v{proposal.number},
                                    for {proposal.totals.total} incl. VAT.
                                </label>
                            </div>
                            {errors.agree && (
                                <p
                                    id="accept-agree-error"
                                    className="text-sm font-medium text-destructive"
                                >
                                    {errors.agree}
                                </p>
                            )}
                        </div>
                        <button
                            className="lc-pill"
                            disabled={processing}
                            data-tour="accept-submit"
                        >
                            <BadgeCheck aria-hidden="true" className="size-5" />
                            Accept proposal v{proposal.number}
                        </button>
                    </>
                )}
            </Form>

            {askingChanges ? (
                <Form
                    {...ClientScopeController.requestChanges.form(proposal.id)}
                    errorBag="changes"
                    options={{ preserveScroll: true }}
                    onError={focusFirstError}
                    onSuccess={() => focusById('proposal-status')}
                    className="space-y-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                name="note"
                                id="changes-note"
                                label="What should change?"
                                hint="The studio reads this and sends a new version."
                                error={errors.note}
                            >
                                {(props) => (
                                    <textarea
                                        {...props}
                                        required
                                        rows={4}
                                        maxLength={2000}
                                        autoComplete="off"
                                        className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs aria-invalid:border-destructive"
                                    />
                                )}
                            </FormField>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    variant="secondary"
                                    disabled={processing}
                                >
                                    Send to the studio
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setAskingChanges(false);
                                        focusById('ask-changes');
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            ) : (
                <Button
                    id="ask-changes"
                    variant="secondary"
                    data-tour="request-changes"
                    onClick={() => {
                        setAskingChanges(true);
                        focusById('changes-note');
                    }}
                >
                    <MessageSquareText aria-hidden="true" />
                    Ask for changes
                </Button>
            )}
        </section>
    );
}
