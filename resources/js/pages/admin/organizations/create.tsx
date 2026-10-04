import { Head } from '@inertiajs/react';
import OrganizationController from '@/actions/App/Http/Controllers/Admin/OrganizationController';
import { OrganizationForm } from '@/components/admin/organization-form';
import { PageHeader } from '@/components/page-header';
import { create, index } from '@/routes/admin/organizations';

export default function CreateOrganization() {
    return (
        <>
            <Head title="Add client" />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Add client" />
                <OrganizationForm
                    form={OrganizationController.store.form()}
                    submitLabel="Add client"
                />
            </div>
        </>
    );
}

CreateOrganization.layout = {
    breadcrumbs: [
        { title: 'Clients', href: index() },
        { title: 'Add client', href: create() },
    ],
};
