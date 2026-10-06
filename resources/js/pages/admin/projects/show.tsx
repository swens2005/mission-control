import { Form, Head, Link } from '@inertiajs/react';
import OrganizationController from '@/actions/App/Http/Controllers/Admin/OrganizationController';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import ProofmarkController from '@/actions/App/Http/Controllers/Admin/ProofmarkController';
import { ActivityList } from '@/components/activity-list';
import { PageHeader } from '@/components/page-header';
import { PhaseBadge } from '@/components/phase-badge';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { formatDate } from '@/lib/format';
import { show as showLaunch } from '@/routes/admin/launch';
import { index, show } from '@/routes/admin/projects';
import type { ActivityItem, Project } from '@/types';

export default function ShowProject({
    project,
    activity,
}: {
    project: Project;
    activity: ActivityItem[];
}) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
    ]);

    return (
        <>
            <Head title={project.name} />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title={project.name}
                    description={
                        <>
                            For{' '}
                            <Link
                                href={OrganizationController.show(
                                    project.organization.id,
                                )}
                                className="text-primary underline"
                            >
                                {project.organization.name}
                            </Link>
                            {project.archived && (
                                <p className="mt-1 font-semibold">
                                    This project is archived.
                                </p>
                            )}
                        </>
                    }
                    actions={
                        <>
                            <Button asChild>
                                <Link
                                    href={showLaunch(project.id)}
                                    data-tour="open-launch-control"
                                >
                                    Launch Control
                                </Link>
                            </Button>
                            <Button variant="secondary" asChild>
                                <Link
                                    href={ProofmarkController.show(project.id)}
                                    data-tour="open-proofmark"
                                >
                                    Proofmark
                                </Link>
                            </Button>
                            <Button variant="secondary" asChild>
                                <Link href={ProjectController.edit(project.id)}>
                                    Edit
                                </Link>
                            </Button>
                            {project.archived ? (
                                <Form
                                    {...ProjectController.unarchive.form(
                                        project.id,
                                    )}
                                >
                                    <Button variant="secondary">Restore</Button>
                                </Form>
                            ) : (
                                <Form
                                    {...ProjectController.archive.form(
                                        project.id,
                                    )}
                                >
                                    <Button variant="secondary">Archive</Button>
                                </Form>
                            )}
                        </>
                    }
                />

                <dl className="grid max-w-xl grid-cols-[auto_1fr] gap-x-6 gap-y-3 rounded-lg border bg-card p-4 text-sm">
                    <dt className="text-muted-foreground">Phase</dt>
                    <dd>
                        <PhaseBadge
                            phase={project.phase}
                            label={project.phaseLabel}
                        />
                    </dd>
                    <dt className="text-muted-foreground">Target launch</dt>
                    <dd>{formatDate(project.targetLaunchOn)}</dd>
                    <dt className="text-muted-foreground">Description</dt>
                    <dd className="whitespace-pre-line">
                        {project.description || 'None yet.'}
                    </dd>
                </dl>

                <section
                    aria-labelledby="activity-heading"
                    className="max-w-2xl space-y-3"
                >
                    <h2 id="activity-heading" className="text-lg font-bold">
                        Activity
                    </h2>
                    <ActivityList entries={activity} />
                </section>
            </div>
        </>
    );
}
