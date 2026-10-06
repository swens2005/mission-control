import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
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
            <div className="space-y-12">
                <div>
                    <p className="lc-label text-muted-foreground">
                        Mission briefing · {organizationName}
                    </p>
                    <h1 className="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">
                        Your projects
                    </h1>
                    <p className="mt-3 max-w-[52ch] text-lg text-muted-foreground">
                        Everything your studio is building for you, and anything
                        that needs a decision from your side.
                    </p>
                </div>

                <section
                    aria-labelledby="waiting-heading"
                    className="space-y-4"
                >
                    <h2
                        id="waiting-heading"
                        className="text-2xl font-extrabold"
                    >
                        Waiting on you
                    </h2>
                    {waiting.length === 0 ? (
                        <div className="lc-card lc-edge-pass flex items-center gap-4 p-5">
                            <span
                                aria-hidden="true"
                                className="lc-lamp lc-lamp-go lc-lamp-lit size-4"
                            />
                            <p>
                                Nothing needs you right now. We'll show
                                approvals and sign-offs here when they're ready.
                            </p>
                        </div>
                    ) : (
                        <ul className="grid gap-4 md:grid-cols-2">
                            {waiting.map((item, index) => (
                                <li
                                    key={`${item.url}-${index}`}
                                    className="lc-card lc-edge-warn flex flex-col gap-3 p-5"
                                >
                                    <p className="lc-label flex items-center gap-2 text-muted-foreground">
                                        <span
                                            aria-hidden="true"
                                            className="lc-lamp lc-lamp-warn size-2.5"
                                        />
                                        {item.module} · {item.projectName}
                                    </p>
                                    <Link
                                        href={item.url}
                                        className="group flex items-center gap-2 font-display text-lg font-bold text-primary underline-offset-4 hover:underline"
                                    >
                                        {item.title}
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4 transition-transform group-hover:translate-x-1"
                                        />
                                    </Link>
                                    {item.dueOn && (
                                        <p className="font-mono text-xs tracking-[0.1em] text-muted-foreground uppercase">
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
                    className="space-y-4"
                >
                    <h2
                        id="projects-heading"
                        className="text-2xl font-extrabold"
                    >
                        Projects
                    </h2>
                    {projects.length === 0 ? (
                        <EmptyState
                            title="No projects yet"
                            description="Your studio will add your project here."
                        />
                    ) : (
                        <ul className="space-y-5">
                            {projects.map((project, index) => (
                                <li
                                    key={project.id}
                                    className="lc-card lc-edge-pass space-y-5 p-6"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="lc-label flex items-center gap-2 text-muted-foreground">
                                                <span
                                                    aria-hidden="true"
                                                    className="lc-lamp lc-lamp-go size-2.5"
                                                />
                                                Project{' '}
                                                {String(index + 1).padStart(
                                                    2,
                                                    '0',
                                                )}
                                            </p>
                                            <h3 className="mt-1 text-xl font-extrabold">
                                                <Link
                                                    href={ProjectController.show(
                                                        project.id,
                                                    )}
                                                    className="underline-offset-4 hover:underline"
                                                >
                                                    {project.name}
                                                </Link>
                                            </h3>
                                        </div>
                                        <p className="font-mono text-xs tracking-[0.1em] text-muted-foreground uppercase">
                                            Target launch{' '}
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
