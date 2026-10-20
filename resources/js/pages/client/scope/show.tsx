import { Head, Link } from '@inertiajs/react';
import ClientScopeController from '@/actions/App/Http/Controllers/Client/ScopeController';
import { IntakeAnswers } from '@/components/scope/intake-answers';
import { IntakeForm } from '@/components/scope/intake-form';
import {
    PrintButton,
    ProposalDocument,
} from '@/components/scope/proposal-document';
import { ProposalVersions } from '@/components/scope/proposal-versions';
import { formatDate, formatDateTime } from '@/lib/format';
import { show } from '@/routes/client/projects';
import type { Intake, IntakeOptions, Proposal, ProposalVersion } from '@/types';

type Props = {
    project: { id: number; name: string; targetLaunchOn: string | null };
    intake: Intake | null;
    options: IntakeOptions;
    proposal: Proposal | null;
    versions: ProposalVersion[];
};

export default function ShowScope({
    project,
    intake,
    options,
    proposal,
    versions,
}: Props) {
    return (
        <>
            <Head title={`Scope: ${project.name}`} />
            <div className="space-y-10">
                <div data-print="hide">
                    <Link
                        href={show(project.id)}
                        className="text-sm font-semibold text-primary underline"
                    >
                        {project.name}
                    </Link>
                    <h1 className="mt-3 text-4xl font-extrabold tracking-tight">
                        Scope
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        What you need from the new website · Target launch:{' '}
                        {formatDate(project.targetLaunchOn)}
                    </p>
                </div>

                {proposal && (
                    <section
                        aria-labelledby="proposal-status"
                        className="max-w-3xl space-y-5"
                    >
                        <div
                            className="lc-card lc-edge-warn space-y-3 p-5"
                            data-print="hide"
                        >
                            <p className="lc-label text-muted-foreground">
                                Proposal · v{proposal.number}
                            </p>
                            <h2
                                id="proposal-status"
                                tabIndex={-1}
                                className="text-xl font-bold"
                                data-tour="proposal-status"
                            >
                                Proposal v{proposal.number} is ready for you
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {proposal.studioName} sent it
                                {proposal.sentAt &&
                                    ` on ${formatDateTime(proposal.sentAt)}`}
                                . Read it through; you can print it or save it
                                as a PDF.
                            </p>
                            <PrintButton />
                        </div>

                        <ProposalDocument proposal={proposal} />

                        <ProposalVersions
                            versions={versions}
                            currentId={proposal.id}
                            hrefFor={(id) => ClientScopeController.version(id)}
                        />
                    </section>
                )}

                {intake && (
                    <IntakeSection
                        intake={intake}
                        options={options}
                        folded={proposal !== null}
                    />
                )}
            </div>
        </>
    );
}

function IntakeSection({
    intake,
    options,
    folded,
}: {
    intake: Intake;
    options: IntakeOptions;
    /** Once there's a proposal, sent answers fold away under it. */
    folded: boolean;
}) {
    if (folded && intake.submitted) {
        return (
            <details className="max-w-3xl" data-print="hide">
                <summary className="cursor-pointer font-semibold text-primary underline">
                    Your intake answers
                </summary>
                <div className="mt-4">
                    <IntakeAnswers intake={intake} />
                </div>
            </details>
        );
    }

    return (
        <div className="space-y-10" data-print="hide">
            <section
                aria-labelledby="intake-status"
                className={`lc-card max-w-3xl space-y-2 p-5 ${intake.submitted ? 'lc-edge-pass' : 'lc-edge-warn'}`}
            >
                <p className="lc-label text-muted-foreground">
                    Intake · {intake.submitted ? 'sent' : 'to do'}
                </p>
                <h2
                    id="intake-status"
                    tabIndex={-1}
                    className="text-xl font-bold"
                >
                    {intake.submitted
                        ? 'Sent to the studio'
                        : 'Tell us what you need'}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {intake.submitted
                        ? `Sent by ${intake.submittedByName ?? 'you'} on ${formatDateTime(intake.submittedAt)}. The studio uses your answers to write a proposal. Need to change something? Ask the studio to reopen it.`
                        : 'A few questions about your goals, pages, features, budget and deadline. Save a draft any time; send it when you’re done.'}
                </p>
            </section>

            {intake.submitted ? (
                <div className="max-w-3xl">
                    <IntakeAnswers intake={intake} />
                </div>
            ) : (
                <IntakeForm intake={intake} options={options} />
            )}
        </div>
    );
}
