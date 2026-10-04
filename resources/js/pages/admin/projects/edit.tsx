import { Head } from '@inertiajs/react';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import { ProjectForm } from '@/components/admin/project-form';
import { PageHeader } from '@/components/page-header';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, show } from '@/routes/admin/projects';
import type { Option, OrganizationOption, Project } from '@/types';

type Props = {
    project: Project;
    organizations: OrganizationOption[];
    phases: Option[];
};

export default function EditProject({ project, organizations, phases }: Props) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Edit', href: edit(project.id) },
    ]);

    return (
        <>
            <Head title={`Edit ${project.name}`} />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={`Edit ${project.name}`} />
                <ProjectForm
                    form={ProjectController.update.form(project.id)}
                    project={project}
                    organizations={organizations}
                    phases={phases}
                    submitLabel="Save changes"
                />
            </div>
        </>
    );
}
