import { Form, Head, Link } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';
import ClientProofmarkController from '@/actions/App/Http/Controllers/Client/ProofmarkController';
import { focusById } from '@/components/form-field';
import { PinnedReview } from '@/components/proofmark/pinned-review';
import { RoundHeader } from '@/components/proofmark/round-header';
import { RoundList } from '@/components/proofmark/round-list';
import { formatDate, formatDateTime } from '@/lib/format';
import { show } from '@/routes/client/projects';
import type { ReviewRound, ReviewRoundSummary } from '@/types';

type Props = {
    project: { id: number; name: string; targetLaunchOn: string | null };
    rounds: ReviewRoundSummary[];
    round: ReviewRound;
};

export default function ShowProofmark({ project, rounds, round }: Props) {
    return (
        <>
            <Head title={`Proofmark: ${project.name}`} />
            <div className="space-y-10">
                <div>
                    <Link
                        href={show(project.id)}
                        className="text-sm font-semibold text-primary underline"
                    >
                        {project.name}
                    </Link>
                    <h1 className="mt-3 text-4xl font-extrabold tracking-tight">
                        Proofmark
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Design review · Target launch:{' '}
                        {formatDate(project.targetLaunchOn)}
                    </p>
                </div>

                <div className="grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
                    <RoundList
                        rounds={rounds}
                        currentId={round.id}
                        href={(r) =>
                            ClientProofmarkController.show(project.id, {
                                query: { round: r.number },
                            })
                        }
                    />
                    <section
                        aria-labelledby="round-heading"
                        className="min-w-0 space-y-6"
                    >
                        <RoundHeader
                            round={round}
                            description={describe(round)}
                        >
                            {round.status === 'in_review' && (
                                <ApproveForm round={round} />
                            )}
                        </RoundHeader>
                        <PinnedReview key={round.id} round={round} />
                    </section>
                </div>
            </div>
        </>
    );
}

/**
 * Approving locks the round, so it asks first, and says how many comments
 * are still open (they stay open, for the record).
 */
function ApproveForm({ round }: { round: ReviewRound }) {
    const open = round.designs.reduce(
        (total, design) =>
            total + design.comments.filter((c) => !c.resolved).length,
        0,
    );
    const warning =
        open === 0
            ? ''
            : open === 1
              ? ' 1 comment is still open; approving keeps it for the record.'
              : ` ${open} comments are still open; approving keeps them for the record.`;

    return (
        <Form
            {...ClientProofmarkController.approve.form(round.id)}
            options={{ preserveScroll: true }}
            onBefore={() =>
                window.confirm(
                    `Approve the designs in ${round.label}?${warning} After approving, no more comments can be added.`,
                )
            }
            onSuccess={() => focusById('round-status')}
            className="space-y-2"
        >
            {({ processing }) => (
                <>
                    <button
                        className="lc-pill"
                        disabled={processing}
                        data-tour="approve-round"
                    >
                        <BadgeCheck aria-hidden="true" className="size-5" />
                        Approve {round.label}
                    </button>
                    <p className="text-sm text-muted-foreground">
                        Happy with these designs? Approve them so the studio can
                        start building.
                        {open > 0 &&
                            ` ${open} open ${open === 1 ? 'comment' : 'comments'}.`}
                    </p>
                </>
            )}
        </Form>
    );
}

function describe(round: ReviewRound): string {
    switch (round.status) {
        case 'in_review':
            return `The studio sent these designs on ${formatDateTime(round.sentAt)}. Take a look at each one.`;
        case 'approved':
            return `Approved by ${round.approvedByName ?? 'you'} on ${formatDateTime(round.approvedAt)}.`;
        default:
            return 'Replaced by a newer round. Kept for the record.';
    }
}
