import { Form, Head, Link, usePage } from '@inertiajs/react';
import ContactController from '@/actions/App/Http/Controllers/Admin/ContactController';
import OrganizationController from '@/actions/App/Http/Controllers/Admin/OrganizationController';
import ProjectController from '@/actions/App/Http/Controllers/Admin/ProjectController';
import { EmptyState } from '@/components/empty-state';
import { FormField, focusFirstError } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { PhaseBadge } from '@/components/phase-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { index, show } from '@/routes/admin/organizations';
import type { Contact, Organization, Project } from '@/types';

type NewContact = { name: string; email: string; temporaryPassword: string };

type Props = {
    organization: Organization;
    projects: Project[];
    contacts: Contact[];
};

export default function ShowOrganization({
    organization,
    projects,
    contacts,
}: Props) {
    const newContact = usePage().flash.newContact as NewContact | undefined;

    useBreadcrumbs([
        { title: 'Clients', href: index() },
        { title: organization.name, href: show(organization.id) },
    ]);

    return (
        <>
            <Head title={organization.name} />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title={organization.name}
                    description={
                        <>
                            {organization.websiteUrl && (
                                <a
                                    href={organization.websiteUrl}
                                    className="text-primary underline"
                                    rel="noopener noreferrer"
                                    target="_blank"
                                >
                                    {organization.websiteUrl}
                                    <span className="sr-only">
                                        {' '}
                                        (opens in a new tab)
                                    </span>
                                </a>
                            )}
                            {organization.archived && (
                                <p className="mt-1 font-semibold">
                                    This client is archived.
                                </p>
                            )}
                        </>
                    }
                    actions={
                        <>
                            <Button variant="secondary" asChild>
                                <Link
                                    href={OrganizationController.edit(
                                        organization.id,
                                    )}
                                >
                                    Edit
                                </Link>
                            </Button>
                            {organization.archived ? (
                                <Form
                                    {...OrganizationController.unarchive.form(
                                        organization.id,
                                    )}
                                >
                                    <Button variant="secondary">Restore</Button>
                                </Form>
                            ) : (
                                <Form
                                    {...OrganizationController.archive.form(
                                        organization.id,
                                    )}
                                >
                                    <Button variant="secondary">Archive</Button>
                                </Form>
                            )}
                        </>
                    }
                />

                <section
                    aria-labelledby="projects-heading"
                    className="space-y-4"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="projects-heading" className="text-lg font-bold">
                            Projects
                        </h2>
                        {!organization.archived && (
                            <Button size="sm" asChild>
                                <Link
                                    href={ProjectController.create({
                                        query: {
                                            organization: organization.id,
                                        },
                                    })}
                                >
                                    New project
                                </Link>
                            </Button>
                        )}
                    </div>

                    {projects.length === 0 ? (
                        <EmptyState
                            title="No projects yet"
                            description="Start one when the first conversation turns into work."
                        />
                    ) : (
                        <ul className="divide-y rounded-lg border bg-card">
                            {projects.map((project) => (
                                <li
                                    key={project.id}
                                    className="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                                >
                                    <Link
                                        href={ProjectController.show(
                                            project.id,
                                        )}
                                        className="font-semibold underline-offset-4 hover:underline"
                                    >
                                        {project.name}
                                        {project.archived && (
                                            <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                (archived)
                                            </span>
                                        )}
                                    </Link>
                                    <PhaseBadge
                                        phase={project.phase}
                                        label={project.phaseLabel}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section
                    aria-labelledby="contacts-heading"
                    className="space-y-4"
                >
                    <h2 id="contacts-heading" className="text-lg font-bold">
                        Contacts
                    </h2>

                    {newContact && (
                        <div
                            role="status"
                            className="space-y-2 rounded-lg border-2 border-primary bg-card p-4"
                        >
                            <p className="font-semibold">
                                {newContact.name} can now log in to Launchpad.
                            </p>
                            <p className="text-sm">
                                Share these details with them. The password is
                                shown only once.
                            </p>
                            <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 font-mono text-sm">
                                <dt className="text-muted-foreground">Email</dt>
                                <dd className="break-all">
                                    {newContact.email}
                                </dd>
                                <dt className="text-muted-foreground">
                                    Password
                                </dt>
                                <dd className="break-all">
                                    {newContact.temporaryPassword}
                                </dd>
                            </dl>
                        </div>
                    )}

                    {contacts.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No contacts yet. Add one so the client can log in.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border bg-card">
                            {contacts.map((contact) => (
                                <li key={contact.id} className="px-4 py-3">
                                    <span className="font-semibold">
                                        {contact.name}
                                    </span>
                                    <span className="ml-2 text-sm break-all text-muted-foreground">
                                        {contact.email}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}

                    {!organization.archived && (
                        <Form
                            {...ContactController.store.form(organization.id)}
                            onError={focusFirstError}
                            resetOnSuccess
                            options={{ preserveScroll: true }}
                            className="grid max-w-xl gap-4 rounded-lg border bg-card p-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <h3 className="font-bold">Add a contact</h3>
                                    <FormField
                                        name="name"
                                        label="Name"
                                        error={errors.name}
                                    >
                                        {(props) => (
                                            <Input
                                                {...props}
                                                required
                                                autoComplete="off"
                                            />
                                        )}
                                    </FormField>
                                    <FormField
                                        name="email"
                                        label="Email"
                                        error={errors.email}
                                    >
                                        {(props) => (
                                            <Input
                                                {...props}
                                                type="email"
                                                required
                                                autoComplete="off"
                                            />
                                        )}
                                    </FormField>
                                    <div>
                                        <Button disabled={processing}>
                                            Add contact
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    )}
                </section>
            </div>
        </>
    );
}
