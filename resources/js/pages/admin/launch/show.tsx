import { Form, Head, Link } from '@inertiajs/react';
import ChecklistItemController from '@/actions/App/Http/Controllers/Admin/ChecklistItemController';
import LaunchController from '@/actions/App/Http/Controllers/Admin/LaunchController';
import {
    FormField,
    focusFirstError,
    selectClassName,
} from '@/components/form-field';
import { Checklist } from '@/components/launch/checklist';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { show as showLaunch } from '@/routes/admin/launch';
import { index, show } from '@/routes/admin/projects';
import type { Launch, Project } from '@/types';

type Props = {
    project: Project;
    launch: Launch | null;
    isSandbox: boolean;
};

export default function ShowLaunch({ project, launch, isSandbox }: Props) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Launch Control', href: showLaunch(project.id) },
    ]);

    const urlHint = isSandbox
        ? 'In the demo, Launch Control checks codelaunch.nl.'
        : 'The public address the automated checks will visit.';

    return (
        <>
            <Head title={`Launch Control: ${project.name}`} />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title="Launch Control"
                    description={
                        <>
                            Pre-flight for{' '}
                            <Link
                                href={show(project.id)}
                                className="text-primary underline"
                            >
                                {project.name}
                            </Link>
                        </>
                    }
                />

                {launch === null ? (
                    <section
                        aria-labelledby="prepare-heading"
                        className="max-w-xl space-y-4 rounded-lg border bg-card p-4"
                    >
                        <h2 id="prepare-heading" className="text-lg font-bold">
                            Prepare the launch
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Adds the pre-flight checklist. The client sees it in
                            Launchpad and ticks their own items.
                        </p>
                        <Form
                            {...LaunchController.store.form(project.id)}
                            onError={focusFirstError}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <FormField
                                        name="url"
                                        label="Site URL"
                                        hint={urlHint}
                                        error={errors.url}
                                    >
                                        {(props) => (
                                            <Input
                                                {...props}
                                                type="url"
                                                required
                                                placeholder="https://"
                                                defaultValue={
                                                    isSandbox
                                                        ? 'https://codelaunch.nl'
                                                        : ''
                                                }
                                                autoComplete="url"
                                                data-tour="launch-url"
                                            />
                                        )}
                                    </FormField>
                                    <Button disabled={processing}>
                                        Prepare launch
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                ) : (
                    <>
                        <section
                            aria-labelledby="site-heading"
                            className="max-w-xl space-y-3"
                        >
                            <h2 id="site-heading" className="text-lg font-bold">
                                Site
                            </h2>
                            <Form
                                {...LaunchController.update.form(project.id)}
                                onError={focusFirstError}
                                options={{ preserveScroll: true }}
                                className="flex flex-wrap items-end gap-3"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="min-w-0 flex-1 basis-64">
                                            <FormField
                                                name="url"
                                                label="Site URL"
                                                hint={urlHint}
                                                error={errors.url}
                                            >
                                                {(props) => (
                                                    <Input
                                                        {...props}
                                                        type="url"
                                                        required
                                                        defaultValue={
                                                            launch.url
                                                        }
                                                        autoComplete="url"
                                                        data-tour="launch-url"
                                                    />
                                                )}
                                            </FormField>
                                        </div>
                                        <Button
                                            variant="secondary"
                                            disabled={processing}
                                        >
                                            Save URL
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>

                        <section
                            aria-labelledby="checklist-heading"
                            className="max-w-2xl space-y-3"
                        >
                            <h2
                                id="checklist-heading"
                                className="text-lg font-bold"
                            >
                                Manual checklist
                            </h2>
                            <Checklist
                                items={launch.checklist}
                                checkRoute={ChecklistItemController.check}
                                uncheckRoute={ChecklistItemController.uncheck}
                                renderActions={(item) => (
                                    <Form
                                        {...ChecklistItemController.destroy.form(
                                            item.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label={`Remove ${item.label}`}
                                        >
                                            Remove
                                        </Button>
                                    </Form>
                                )}
                            />

                            <Form
                                {...ChecklistItemController.store.form(
                                    project.id,
                                )}
                                onError={focusFirstError}
                                options={{ preserveScroll: true }}
                                resetOnSuccess
                                className="flex flex-wrap items-end gap-3 rounded-lg border bg-card p-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="min-w-0 flex-1 basis-56">
                                            <FormField
                                                name="label"
                                                label="New item"
                                                error={errors.label}
                                            >
                                                {(props) => (
                                                    <Input
                                                        {...props}
                                                        required
                                                        maxLength={120}
                                                        autoComplete="off"
                                                    />
                                                )}
                                            </FormField>
                                        </div>
                                        <div className="basis-36">
                                            <FormField
                                                name="owner"
                                                label="Owner"
                                                error={errors.owner}
                                            >
                                                {(props) => (
                                                    <select
                                                        {...props}
                                                        className={
                                                            selectClassName
                                                        }
                                                        defaultValue="studio"
                                                    >
                                                        <option value="studio">
                                                            Studio
                                                        </option>
                                                        <option value="client">
                                                            Client
                                                        </option>
                                                    </select>
                                                )}
                                            </FormField>
                                        </div>
                                        <Button
                                            variant="secondary"
                                            disabled={processing}
                                        >
                                            Add item
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </section>
                    </>
                )}
            </div>
        </>
    );
}
