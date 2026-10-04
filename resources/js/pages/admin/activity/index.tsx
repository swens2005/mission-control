import { Form, Head } from '@inertiajs/react';
import ActivityController from '@/actions/App/Http/Controllers/Admin/ActivityController';
import { ActivityList } from '@/components/activity-list';
import { selectClassName } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/activity';
import type { ActivityItem, OrganizationOption, Paginated } from '@/types';

type Props = {
    entries: Paginated<ActivityItem>;
    filters: { project: number | null };
    projects: OrganizationOption[];
};

export default function ActivityIndex({ entries, filters, projects }: Props) {
    return (
        <>
            <Head title="Activity" />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Activity"
                    description="Everything that happened in your studio, newest first."
                />

                <Form
                    {...ActivityController.index.form()}
                    role="search"
                    aria-label="Filter activity"
                    className="flex flex-wrap items-end gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="filter-project">Project</Label>
                        <select
                            id="filter-project"
                            name="project"
                            defaultValue={filters.project ?? ''}
                            className={`${selectClassName} w-64 max-w-full`}
                        >
                            <option value="">All projects</option>
                            {projects.map((project) => (
                                <option key={project.id} value={project.id}>
                                    {project.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button variant="secondary" type="submit">
                        Apply
                    </Button>
                </Form>

                <div className="rounded-lg border bg-card p-4">
                    <ActivityList entries={entries.data} showProject />
                </div>

                <Pagination page={entries} />
            </div>
        </>
    );
}

ActivityIndex.layout = {
    breadcrumbs: [{ title: 'Activity', href: index() }],
};
