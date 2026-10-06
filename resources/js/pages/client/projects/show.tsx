import { Head, Link } from '@inertiajs/react';
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
}: {
    project: ClientProject;
    steps: Option[];
    activity: ActivityItem[];
    hasLaunch: boolean;
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
                    <h1 className="mt-3 text-3xl font-extrabold">
                        {project.name}
                    </h1>
                    {project.phase === 'launched' && (
                        <p className="mt-2 inline-block rounded-sm bg-foreground px-2 py-1 font-mono text-xs tracking-[0.14em] text-background uppercase">
                            Launched
                        </p>
                    )}
                </div>

                {hasLaunch && (
                    <p>
                        <Link
                            href={showLaunch(project.id)}
                            className="font-semibold text-primary underline"
                            data-tour="open-launch-control"
                        >
                            Open the launch checklist
                        </Link>
                    </p>
                )}

                <PhaseSteps
                    steps={steps}
                    step={project.step}
                    label="Project progress"
                />

                <dl className="grid max-w-xl grid-cols-[auto_1fr] gap-x-6 gap-y-3 rounded-sm border-2 border-foreground bg-card p-5">
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
                    <h2 id="updates-heading" className="text-xl font-extrabold">
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
