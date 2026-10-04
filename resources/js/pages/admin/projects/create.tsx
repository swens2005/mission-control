import { Head } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import { ProjectForm } from '@/components/admin/project-form';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { create, index } from '@/routes/admin/projects';
import type { Option, OrganizationOption } from '@/types';

type Props = {
    organizations: OrganizationOption[];
    phases: Option[];
    organizationId: number | null;
};

export default function CreateProject({
    organizations,
    phases,
    organizationId,
}: Props) {
    return (
        <>
            <Head title="New project" />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="New project" />
                {organizations.length === 0 ? (
                    <EmptyState
                        title="Add a client first"
                        description="Every project belongs to a client."
                    />
                ) : (
                    <ProjectForm
                        form={ProjectController.store.form()}
                        organizations={organizations}
                        phases={phases}
                        organizationId={organizationId}
                        submitLabel="Create project"
                    />
                )}
            </div>
        </>
    );
}

CreateProject.layout = {
    breadcrumbs: [
        { title: 'Projects', href: index() },
        { title: 'New project', href: create() },
    ],
};
