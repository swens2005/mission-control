import { Head, Link } from '@inertiajs/react';
import { PenTool, Rocket } from 'lucide-react';
import ClientProofmarkController from '@/actions/App/Http/Controllers/Client/ProofmarkController';
import { ActivityList } from '@/components/activity-list';
import { PhaseSteps } from '@/components/phase-steps';
import { formatDate } from '@/lib/format';
import { home } from '@/routes/client';
import { show as showLaunch } from '@/routes/client/launch';
import type { ActivityItem, ClientProject, Option } from '@/types';

export default function ShowProject({
    project,
    steps,
    activity,
    hasLaunch,
    hasProofmark,
}: {
    project: ClientProject;
    steps: Option[];
    activity: ActivityItem[];
    hasLaunch: boolean;
    hasProofmark: boolean;
}) {
    return (
        <>
            <Head title={project.name} />
            <div className="space-y-8">
                <div>
                    <Link
                        href={home()}
                        className="text-sm font-semibold text-primary underline"
                    >
                        All projects
                    </Link>
                    <p className="lc-label mt-4 text-muted-foreground">
                        Mission · {project.phaseLabel}
                    </p>
                    <h1 className="mt-1 text-4xl font-extrabold tracking-tight">
                        {project.name}
                    </h1>
                    {project.phase === 'launched' && (
                        <p className="lc-label mt-3 inline-flex items-center gap-2 rounded-full bg-sidebar px-3 py-1.5 text-sidebar-foreground">
                            <span
                                aria-hidden="true"
                                className="lc-lamp lc-lamp-go lc-lamp-lit size-2.5"
                            />
                            Launched
                        </p>
                    )}
                </div>

                {hasProofmark && (
                    <Link
                        href={ClientProofmarkController.show(project.id)}
                        className="lc-pill w-fit"
                        data-tour="open-proofmark"
                    >
                        <PenTool aria-hidden="true" className="size-5" />
                        Open Proofmark
                    </Link>
                )}

                {hasLaunch && (
                    <Link
                        href={showLaunch(project.id)}
                        className="lc-pill w-fit"
                        data-tour="open-launch-control"
                    >
                        <Rocket aria-hidden="true" className="size-5" />
                        Open Launch Control
                    </Link>
                )}

                <PhaseSteps
                    steps={steps}
                    step={project.step}
                    label="Project progress"
                />

                <dl className="lc-card lc-edge-pass grid max-w-2xl grid-cols-[auto_1fr] gap-x-6 gap-y-3 p-6">
                    <dt className="text-muted-foreground">Current phase</dt>
                    <dd className="font-semibold">{project.phaseLabel}</dd>
                    <dt className="text-muted-foreground">Target launch</dt>
                    <dd>{formatDate(project.targetLaunchOn)}</dd>
                    <dt className="text-muted-foreground">About</dt>
                    <dd className="whitespace-pre-line">
                        {project.description || 'No description yet.'}
                    </dd>
                </dl>

                <section
                    aria-labelledby="updates-heading"
                    className="max-w-2xl space-y-3"
                >
                    <h2
                        id="updates-heading"
                        className="text-2xl font-extrabold"
                    >
                        Updates
                    </h2>
                    <ActivityList
                        entries={activity}
                        emptyText="No updates yet."
                    />
                </section>
            </div>
        </>
    );
}
