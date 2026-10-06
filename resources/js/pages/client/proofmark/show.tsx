import { Head, Link } from '@inertiajs/react';
import ClientProofmarkController from '@/actions/App/Http/Controllers/Client/ProofmarkController';
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
                        />
                        <PinnedReview key={round.id} round={round} />
                    </section>
                </div>
            </div>
        </>
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
