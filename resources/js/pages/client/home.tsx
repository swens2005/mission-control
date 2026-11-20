import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import ProjectController from '@/actions/App/Http/Controllers/Client/ProjectController';
import { EmptyState } from '@/components/empty-state';
import { PhaseRail } from '@/components/phase-rail';
import { formatDate } from '@/lib/format';
import type { ClientProject, Option, WaitingItem } from '@/types';
import blueprint from '../../../images/mission-control/rocket/1-blueprint.webp';
import building from '../../../images/mission-control/rocket/2-building.webp';
import fueled from '../../../images/mission-control/rocket/3-fueled.webp';
import liftoff from '../../../images/mission-control/rocket/4-liftoff.webp';
import viewingStand from '../../../images/mission-control/viewing-stand.webp';

type Props = {
    organizationName: string | null;
    projects: ClientProject[];
    steps: Option[];
    waiting: WaitingItem[];
};

/** The client's rocket grows with the project: four stages for five phases. */
const STAGES: Record<ClientProject['phase'], { src: string; alt: string }> = {
    scope: { src: blueprint, alt: 'Your rocket, still a blueprint' },
    palette: { src: building, alt: 'Your rocket, being built' },
    proofmark: { src: building, alt: 'Your rocket, being built' },
    launch: { src: fueled, alt: 'Your rocket, fueled on the pad' },
    launched: { src: liftoff, alt: 'Your rocket, lifting off' },
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
                <section className="vs-hero" aria-labelledby="home-heading">
                    <div>
                        <p className="lc-label text-muted-foreground">
                            Mission briefing · {organizationName}
                        </p>
                        <h1
                            id="home-heading"
                            className="mt-2 text-4xl leading-none font-extrabold tracking-tight sm:text-6xl"
                        >
                            Your projects
                        </h1>
                        <p className="mt-3 max-w-[46ch] text-lg text-muted-foreground">
                            Everything your studio is building for you, and
                            anything that needs a decision from your side.
                        </p>
                    </div>
                    <figure>
                        <img
                            src={viewingStand}
                            alt="A rocket lifting off at a launch site at dawn, watched from a small viewing stand"
                            width={1200}
                            height={675}
                        />
                    </figure>
                </section>

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
                                All clear: nothing needs you right now. We'll
                                show approvals and sign-offs here when they're
                                ready.
                            </p>
                        </div>
                    ) : (
                        <ul className="grid gap-4 md:grid-cols-2">
                            {waiting.map((item, index) => (
                                <li
                                    key={`${item.url}-${index}`}
                                    className="lc-card lc-edge-warn flex flex-col gap-2 px-5 py-4"
                                >
                                    <p className="lc-label flex items-center gap-2 text-status-warn">
                                        <span
                                            aria-hidden="true"
                                            className="lc-lamp lc-lamp-warn lc-lamp-lit size-2.5"
                                        />
                                        {item.module} · {item.projectName}
                                    </p>
                                    <Link
                                        href={item.url}
                                        data-tour="waiting-item"
                                        className="group flex items-center gap-2 font-display text-lg font-extrabold text-primary underline-offset-4 hover:underline"
                                    >
                                        {item.title}
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4 shrink-0 transition-transform group-hover:translate-x-1 motion-reduce:transition-none"
                                        />
                                    </Link>
                                    {item.dueOn && (
                                        <p className="font-mono text-xs tracking-[0.12em] text-muted-foreground uppercase">
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
                        <ul className="space-y-4">
                            {projects.map((project, index) => (
                                <ProjectCard
                                    key={project.id}
                                    project={project}
                                    number={index + 1}
                                    steps={steps}
                                    waiting={waiting.filter(
                                        (item) => item.projectId === project.id,
                                    )}
                                />
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

function ProjectCard({
    project,
    number,
    steps,
    waiting,
}: {
    project: ClientProject;
    number: number;
    steps: Option[];
    waiting: WaitingItem[];
}) {
    const stage = STAGES[project.phase];

    return (
        <li className="lc-card lc-edge-pass vs-project p-5">
            <div className="vs-stage">
                <img
                    src={stage.src}
                    alt={stage.alt}
                    width={512}
                    height={512}
                    loading="lazy"
                />
            </div>
            <div className="min-w-0">
                <p className="lc-label text-muted-foreground">
                    Project {String(number).padStart(2, '0')}
                </p>
                <h3 className="mt-1 font-display text-2xl leading-tight font-extrabold">
                    <Link
                        href={ProjectController.show(project.id)}
                        className="underline-offset-4 hover:underline"
                        data-tour="client-project"
                    >
                        {project.name}
                    </Link>
                </h3>
                <p className="mt-1 font-mono text-xs tracking-[0.12em] text-muted-foreground uppercase">
                    Target launch {formatDate(project.targetLaunchOn)}
                </p>
                <PhaseRail
                    className="mt-4"
                    steps={steps}
                    step={project.step}
                    label={`${project.name} progress`}
                />
                <p className="mt-3">
                    {project.summary}
                    {waiting.length === 1 &&
                        ` Waiting on you: ${waiting[0].title}.`}
                    {waiting.length > 1 &&
                        ` ${waiting.length} things are waiting on you.`}
                </p>
            </div>
        </li>
    );
}
