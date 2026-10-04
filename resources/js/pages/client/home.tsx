import { Head, Link } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Client/ProjectController';
import { EmptyState } from '@/components/empty-state';
import { PhaseSteps } from '@/components/phase-steps';
import { formatDate } from '@/lib/format';
import type { ClientProject, Option, WaitingItem } from '@/types';

type Props = {
    organizationName: string | null;
    projects: ClientProject[];
    steps: Option[];
    waiting: WaitingItem[];
};

export default function Home({
    organizationName,
    projects,
    steps,
    waiting,
}: Props) {
    return (
        <>
            <Head title="Launchpad" />
            <div className="space-y-10">
                <div>
                    <p className="font-mono text-xs tracking-[0.14em] text-muted-foreground uppercase">
                        {organizationName}
                    </p>
                    <h1 className="mt-1 text-3xl font-extrabold">
                        Your projects
                    </h1>
                </div>

                <section
                    aria-labelledby="waiting-heading"
                    className="space-y-3"
                >
                    <h2 id="waiting-heading" className="text-xl font-extrabold">
                        Waiting on you
                    </h2>
                    {waiting.length === 0 ? (
                        <div className="flex items-center gap-4 rounded-sm border-2 border-foreground bg-card p-4">
                            <span
                                aria-hidden="true"
                                className="riso-halftone size-10 shrink-0 rounded-full"
                            />
                            <p>
                                Nothing needs you right now. We'll show
                                approvals and sign-offs here when they're ready.
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-2">
                            {waiting.map((item, index) => (
                                <li
                                    key={`${item.url}-${index}`}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-sm border-2 border-foreground bg-card p-4"
                                >
                                    <div>
                                        <Link
                                            href={item.url}
                                            className="font-semibold text-primary underline"
                                        >
                                            {item.title}
                                        </Link>
                                        <p className="text-sm text-muted-foreground">
                                            {item.module} · {item.projectName}
                                        </p>
                                    </div>
                                    {item.dueOn && (
                                        <p className="text-sm">
                                            Due {formatDate(item.dueOn)}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section
                    aria-labelledby="projects-heading"
                    className="space-y-3"
                >
                    <h2
                        id="projects-heading"
                        className="text-xl font-extrabold"
                    >
                        Projects
                    </h2>
                    {projects.length === 0 ? (
                        <EmptyState
                            title="No projects yet"
                            description="Your studio will add your project here."
                        />
                    ) : (
                        <ul className="space-y-4">
                            {projects.map((project) => (
                                <li
                                    key={project.id}
                                    className="space-y-4 rounded-sm border-2 border-foreground bg-card p-5"
                                >
                                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                                        <h3 className="text-lg font-extrabold">
                                            <Link
                                                href={ProjectController.show(
                                                    project.id,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {project.name}
                                            </Link>
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            Target launch:{' '}
                                            {formatDate(project.targetLaunchOn)}
                                        </p>
                                    </div>
                                    <PhaseSteps
                                        steps={steps}
                                        step={project.step}
                                        label={`${project.name} progress`}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}
