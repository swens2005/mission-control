import { Form, Head, Link } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import { EmptyState } from '@/components/empty-state';
import { selectClassName } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { PhaseBadge } from '@/components/phase-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { index } from '@/routes/admin/projects';
import type { Option, OrganizationOption, Paginated, Project } from '@/types';

type Props = {
    projects: Paginated<Project>;
    filters: {
        organization: number | null;
        phase: string | null;
        archived: boolean;
    };
    organizations: OrganizationOption[];
    phases: Option[];
};

export default function ProjectsIndex({
    projects,
    filters,
    organizations,
    phases,
}: Props) {
    const filtered =
        filters.organization !== null ||
        filters.phase !== null ||
        filters.archived;

    return (
        <>
            <Head title="Projects" />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Projects"
                    description="Every website in flight, soonest launch first."
                    actions={
                        <Button asChild>
                            <Link href={ProjectController.create()}>
                                New project
                            </Link>
                        </Button>
                    }
                />

                <Form
                    {...ProjectController.index.form()}
                    role="search"
                    aria-label="Filter projects"
                    className="flex flex-wrap items-end gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="filter-organization">Client</Label>
                        <select
                            id="filter-organization"
                            name="organization"
                            defaultValue={filters.organization ?? ''}
                            className={`${selectClassName} w-56`}
                        >
                            <option value="">All clients</option>
                            {organizations.map((organization) => (
                                <option
                                    key={organization.id}
                                    value={organization.id}
                                >
                                    {organization.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="filter-phase">Phase</Label>
                        <select
                            id="filter-phase"
                            name="phase"
                            defaultValue={filters.phase ?? ''}
                            className={`${selectClassName} w-44`}
                        >
                            <option value="">All phases</option>
                            {phases.map((phase) => (
                                <option key={phase.value} value={phase.value}>
                                    {phase.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-2 pb-2">
                        <Checkbox
                            id="archived"
                            name="archived"
                            value="1"
                            defaultChecked={filters.archived}
                        />
                        <Label htmlFor="archived">Show archived</Label>
                    </div>
                    <Button variant="secondary" type="submit">
                        Apply
                    </Button>
                </Form>

                {projects.data.length === 0 ? (
                    filtered ? (
                        <EmptyState
                            title="No projects match"
                            action={
                                <Button variant="secondary" asChild>
                                    <Link href={index()}>Clear filters</Link>
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            title="No projects yet"
                            description="Add a client first, then start their project."
                            action={
                                <Button asChild>
                                    <Link href={ProjectController.create()}>
                                        New project
                                    </Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <div className="overflow-x-auto rounded-lg border bg-card">
                        <table className="w-full text-left text-sm">
                            <caption className="sr-only">
                                {`Projects, page ${projects.current_page} of ${projects.last_page}`}
                            </caption>
                            <thead className="border-b text-muted-foreground">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Project
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Client
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Phase
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Target launch
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {projects.data.map((project) => (
                                    <tr
                                        key={project.id}
                                        className="border-b last:border-0"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3 font-semibold"
                                        >
                                            <Link
                                                href={ProjectController.show(
                                                    project.id,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {project.name}
                                            </Link>
                                            {project.archived && (
                                                <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                    (archived)
                                                </span>
                                            )}
                                        </th>
                                        <td className="px-4 py-3">
                                            {project.organization.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            <PhaseBadge
                                                phase={project.phase}
                                                label={project.phaseLabel}
                                            />
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {formatDate(project.targetLaunchOn)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination page={projects} />
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projects', href: index() }],
};
