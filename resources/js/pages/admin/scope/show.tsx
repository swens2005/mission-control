import { Form, Head, Link } from '@inertiajs/react';
import { ClipboardList, FilePen, FilePlus2, Send } from 'lucide-react';
import ProposalController from '@/actions/App/Http/Controllers/Admin/ProposalController';
import ScopeController from '@/actions/App/Http/Controllers/Admin/ScopeController';
import { focusById } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { IntakeAnswers } from '@/components/scope/intake-answers';
import { ProposalBuilder } from '@/components/scope/proposal-builder';
import {
    PrintButton,
    ProposalDocument,
} from '@/components/scope/proposal-document';
import { ProposalVersions } from '@/components/scope/proposal-versions';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { formatDateTime } from '@/lib/format';
import { index, show } from '@/routes/admin/projects';
import type {
    Intake,
    LibraryGroup,
    Option,
    Project,
    Proposal,
    ProposalVersion,
} from '@/types';

type Props = {
    project: Project;
    intake: Intake | null;
    proposal: Proposal | null;
    versions: ProposalVersion[];
    library: LibraryGroup[];
    kinds: Option[];
    discountTypes: Option[];
};

export default function ShowScope({
    project,
    intake,
    proposal,
    versions,
    library,
    kinds,
    discountTypes,
}: Props) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Scope', href: ScopeController.show(project.id) },
    ]);

    return (
        <>
            <Head title={`Scope: ${project.name}`} />
            <div className="space-y-8 p-4 md:p-6">
                <div data-print="hide">
                    <PageHeader
                        title="Scope"
                        description={
                            <>
                                Intake and proposal for{' '}
                                <Link
                                    href={show(project.id)}
                                    className="text-primary underline"
                                >
                                    {project.name}
                                </Link>
                            </>
                        }
                    />
                </div>

                <div className="grid gap-10 xl:grid-cols-[22rem_minmax(0,1fr)] xl:items-start print:block">
                    <IntakePanel project={project} intake={intake} />

                    <section
                        aria-labelledby="proposal-heading"
                        className="min-w-0 space-y-5"
                    >
                        <div data-print="hide">
                            <p className="lc-label text-muted-foreground">
                                Scope · proposal
                            </p>
                            <h2
                                id="proposal-heading"
                                tabIndex={-1}
                                className="text-2xl font-extrabold"
                                data-tour="proposal-version"
                            >
                                {proposal
                                    ? `Proposal v${proposal.number} · ${proposal.statusLabel.toLowerCase()}`
                                    : 'Proposal'}
                            </h2>
                        </div>

                        {proposal === null ? (
                            <div className="lc-card lc-edge-ink max-w-xl space-y-4 p-6">
                                <p className="text-sm text-muted-foreground">
                                    Turn the intake into a priced proposal, from
                                    your Scope library or with custom lines.
                                    Nothing is shared until you send it.
                                </p>
                                <Form
                                    {...ProposalController.store.form(
                                        project.id,
                                    )}
                                    onSuccess={() =>
                                        focusById('proposal-title')
                                    }
                                >
                                    {({ processing }) => (
                                        <button
                                            className="lc-pill"
                                            disabled={processing}
                                            data-tour="start-proposal"
                                        >
                                            <FilePlus2
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                            Start a proposal
                                        </button>
                                    )}
                                </Form>
                            </div>
                        ) : proposal.status === 'draft' ? (
                            <>
                                <ProposalBuilder
                                    proposal={proposal}
                                    library={library}
                                    kinds={kinds}
                                    discountTypes={discountTypes}
                                />
                                <SendCard proposal={proposal} />
                            </>
                        ) : (
                            <SentProposal proposal={proposal} />
                        )}

                        <ProposalVersions
                            versions={versions}
                            currentId={proposal?.id ?? null}
                            hrefFor={(id) => ScopeController.version(id)}
                        />
                    </section>
                </div>
            </div>
        </>
    );
}

function SendCard({ proposal }: { proposal: Proposal }) {
    return (
        <Form
            {...ProposalController.send.form(proposal.id)}
            options={{ preserveScroll: true }}
            onBefore={() =>
                window.confirm(
                    `Send v${proposal.number} (${proposal.totals.total} incl. VAT) to the client? This version can’t change after sending; changes make a new version.`,
                )
            }
            onError={() => focusById('send-proposal')}
            onSuccess={() => focusById('proposal-heading')}
            className="lc-card lc-edge-pass space-y-3 p-5"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="lc-label text-muted-foreground">
                        Ready? · send to the client
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        The client sees it in Launchpad and can print it.
                        Sending freezes v{proposal.number}: its lines and totals
                        stay exactly as sent.
                    </p>
                    {errors.send && (
                        <p
                            id="send-error"
                            className="text-sm font-medium text-destructive"
                        >
                            {errors.send}
                        </p>
                    )}
                    <button
                        id="send-proposal"
                        className="lc-pill"
                        disabled={processing}
                        aria-describedby={
                            errors.send ? 'send-error' : undefined
                        }
                        data-tour="send-proposal"
                    >
                        <Send aria-hidden="true" className="size-5" />
                        Send v{proposal.number} to the client
                    </button>
                </>
            )}
        </Form>
    );
}

function SentProposal({ proposal }: { proposal: Proposal }) {
    return (
        <div className="space-y-5">
            <div
                className="lc-card lc-edge-pass space-y-3 p-5"
                data-print="hide"
            >
                <p className="text-sm text-muted-foreground">
                    Sent to the client
                    {proposal.sentAt &&
                        ` on ${formatDateTime(proposal.sentAt)}`}
                    {proposal.sentByName && ` by ${proposal.sentByName}`}. This
                    version is frozen. To change anything, make a new version;
                    the client keeps seeing v{proposal.number} until you send
                    it.
                </p>
                <div className="flex flex-wrap gap-3">
                    <Form
                        {...ProposalController.revise.form(proposal.id)}
                        onSuccess={() => focusById('proposal-title')}
                    >
                        {({ processing }) => (
                            <Button
                                variant="secondary"
                                disabled={processing}
                                data-tour="make-changes"
                            >
                                <FilePen aria-hidden="true" />
                                Make changes (v{proposal.number + 1})
                            </Button>
                        )}
                    </Form>
                    <PrintButton />
                </div>
            </div>
            <ProposalDocument proposal={proposal} />
        </div>
    );
}

function IntakePanel({
    project,
    intake,
}: {
    project: Project;
    intake: Intake | null;
}) {
    if (intake === null) {
        return (
            <section
                className="lc-card lc-edge-ink space-y-4 p-6"
                data-print="hide"
            >
                <p className="lc-label text-muted-foreground">
                    Intake · not asked
                </p>
                <h2 className="text-xl font-bold">No intake yet</h2>
                <p className="text-sm text-muted-foreground">
                    Ask {project.organization.name} about their goals, pages,
                    features, budget and deadline. They fill it in from
                    Launchpad.
                </p>
                <Form
                    {...ScopeController.askIntake.form(project.id)}
                    onSuccess={() => focusById('intake-status')}
                >
                    {({ processing }) => (
                        <button
                            className="lc-pill"
                            disabled={processing}
                            data-tour="ask-intake"
                        >
                            <ClipboardList
                                aria-hidden="true"
                                className="size-5"
                            />
                            Ask the client
                        </button>
                    )}
                </Form>
            </section>
        );
    }

    return (
        <section
            aria-labelledby="intake-status"
            className="space-y-5"
            data-print="hide"
        >
            <div
                className={`lc-card space-y-3 p-5 ${intake.submitted ? 'lc-edge-pass' : 'lc-edge-warn'}`}
            >
                <p className="lc-label text-muted-foreground">
                    Intake · {intake.submitted ? 'received' : 'waiting'}
                </p>
                <h2
                    id="intake-status"
                    tabIndex={-1}
                    className="text-xl font-bold"
                >
                    {intake.submitted
                        ? 'Intake received'
                        : 'Waiting for the client'}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {intake.submitted
                        ? `Sent by ${intake.submittedByName ?? 'the client'} on ${formatDateTime(intake.submittedAt)}.`
                        : `Asked on ${formatDateTime(intake.requestedAt)}. The answers appear here once the client sends them.`}
                </p>
                {intake.submitted && (
                    <Form
                        {...ScopeController.reopenIntake.form(intake.id)}
                        options={{ preserveScroll: true }}
                        onBefore={() =>
                            window.confirm(
                                'Reopen the intake? The client can change the answers and has to send them again.',
                            )
                        }
                        onSuccess={() => focusById('intake-status')}
                    >
                        {({ processing }) => (
                            <Button variant="secondary" disabled={processing}>
                                Reopen for changes
                            </Button>
                        )}
                    </Form>
                )}
            </div>

            {intake.submitted && <IntakeAnswers intake={intake} compact />}
        </section>
    );
}
