import { Form, Head, Link } from '@inertiajs/react';
import OrganizationController from '@/actions/App/Http/Controllers/Admin/OrganizationController';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/organizations';
import type { OrganizationRow, Paginated } from '@/types';

type Props = {
    organizations: Paginated<OrganizationRow>;
    filters: { search: string; archived: boolean };
};

export default function OrganizationsIndex({ organizations, filters }: Props) {
    const filtered = filters.search !== '' || filters.archived;

    return (
        <>
            <Head title="Clients" />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader
                    title="Clients"
                    description="The organizations your studio builds for."
                    actions={
                        <Button asChild>
                            <Link href={OrganizationController.create()}>
                                Add client
                            </Link>
                        </Button>
                    }
                />

                <Form
                    {...OrganizationController.index.form()}
                    role="search"
                    aria-label="Filter clients"
                    className="flex flex-wrap items-end gap-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="search">Search by name</Label>
                        <Input
                            id="search"
                            name="search"
                            type="search"
                            defaultValue={filters.search}
                            className="w-64 max-w-full"
                        />
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

                {organizations.data.length === 0 ? (
                    filtered ? (
                        <EmptyState
                            title="No clients match"
                            description="Try another name, or clear the filters."
                            action={
                                <Button variant="secondary" asChild>
                                    <Link href={index()}>Clear filters</Link>
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            title="Add your first client"
                            description="Clients hold their projects and the contacts who log in to Launchpad."
                            action={
                                <Button asChild>
                                    <Link
                                        href={OrganizationController.create()}
                                    >
                                        Add client
                                    </Link>
                                </Button>
                            }
                        />
                    )
                ) : (
                    <div className="overflow-x-auto rounded-lg border bg-card">
                        <table className="w-full text-left text-sm">
                            <caption className="sr-only">
                                {`Clients, page ${organizations.current_page} of ${organizations.last_page}`}
                            </caption>
                            <thead className="border-b text-muted-foreground">
                                <tr>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Name
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Projects
                                    </th>
                                    <th
                                        scope="col"
                                        className="px-4 py-3 font-medium"
                                    >
                                        Contacts
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {organizations.data.map((organization) => (
                                    <tr
                                        key={organization.id}
                                        className="border-b last:border-0"
                                    >
                                        <th
                                            scope="row"
                                            className="px-4 py-3 font-semibold"
                                        >
                                            <Link
                                                href={OrganizationController.show(
                                                    organization.id,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {organization.name}
                                            </Link>
                                            {organization.archived && (
                                                <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                    (archived)
                                                </span>
                                            )}
                                        </th>
                                        <td className="px-4 py-3">
                                            {organization.projectsCount}
                                        </td>
                                        <td className="px-4 py-3">
                                            {organization.contactsCount}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination page={organizations} />
            </div>
        </>
    );
}

OrganizationsIndex.layout = {
    breadcrumbs: [{ title: 'Clients', href: index() }],
};
