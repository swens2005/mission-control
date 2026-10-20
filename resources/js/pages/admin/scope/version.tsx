import { Head, Link } from '@inertiajs/react';
import ScopeController from '@/actions/App/Http/Controllers/Admin/ScopeController';
import {
    PrintButton,
    ProposalDocument,
} from '@/components/scope/proposal-document';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { index, show } from '@/routes/admin/projects';
import type { Project, Proposal } from '@/types';

/**
 * One proposal version, read-only and printable (story 28).
 */
export default function ProposalVersionPage({
    project,
    proposal,
}: {
    project: Project;
    proposal: Proposal;
}) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Scope', href: ScopeController.show(project.id) },
        {
            title: `v${proposal.number}`,
            href: ScopeController.version(proposal.id),
        },
    ]);

    return (
        <>
            <Head title={`Proposal v${proposal.number}: ${project.name}`} />
            <div className="max-w-4xl space-y-6 p-4 md:p-6">
                <div
                    className="flex flex-wrap items-center justify-between gap-4"
                    data-print="hide"
                >
                    <div>
                        <Link
                            href={ScopeController.show(project.id)}
                            className="text-sm font-semibold text-primary underline"
                        >
                            Back to Scope
                        </Link>
                        <h1 className="mt-2 text-2xl font-bold">
                            Proposal v{proposal.number} · {proposal.statusLabel}
                        </h1>
                    </div>
                    {proposal.status !== 'draft' && <PrintButton />}
                </div>
                <ProposalDocument proposal={proposal} />
            </div>
        </>
    );
}
