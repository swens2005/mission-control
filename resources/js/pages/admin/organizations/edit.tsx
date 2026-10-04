import { Head } from '@inertiajs/react';
import OrganizationController from '@/actions/App/Http/Controllers/Admin/OrganizationController';
import { OrganizationForm } from '@/components/admin/organization-form';
import { PageHeader } from '@/components/page-header';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { edit, index, show } from '@/routes/admin/organizations';
import type { Organization } from '@/types';

export default function EditOrganization({
    organization,
}: {
    organization: Organization;
}) {
    useBreadcrumbs([
        { title: 'Clients', href: index() },
        { title: organization.name, href: show(organization.id) },
        { title: 'Edit', href: edit(organization.id) },
    ]);

    return (
        <>
            <Head title={`Edit ${organization.name}`} />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={`Edit ${organization.name}`} />
                <OrganizationForm
                    form={OrganizationController.update.form(organization.id)}
                    organization={organization}
                    submitLabel="Save changes"
                />
            </div>
        </>
    );
}
