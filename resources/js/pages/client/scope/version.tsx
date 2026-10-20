import { Head, Link } from '@inertiajs/react';
import ClientScopeController from '@/actions/App/Http/Controllers/Client/ScopeController';
import {
    PrintButton,
    ProposalDocument,
} from '@/components/scope/proposal-document';
import type { Proposal } from '@/types';

/**
 * An earlier proposal version in Launchpad, read-only and printable.
 */
export default function ProposalVersionPage({
    project,
    proposal,
}: {
    project: { id: number; name: string };
    proposal: Proposal;
}) {
    return (
        <>
            <Head title={`Proposal v${proposal.number}: ${project.name}`} />
            <div className="space-y-6">
                <div
                    className="flex flex-wrap items-end justify-between gap-4"
                    data-print="hide"
                >
                    <div>
                        <Link
                            href={ClientScopeController.show(project.id)}
                            className="text-sm font-semibold text-primary underline"
                        >
                            Back to Scope
                        </Link>
                        <h1 className="mt-3 text-4xl font-extrabold tracking-tight">
                            Proposal v{proposal.number}
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            {proposal.statusLabel}
                        </p>
                    </div>
                    <PrintButton />
                </div>
                <ProposalDocument proposal={proposal} />
            </div>
        </>
    );
}
