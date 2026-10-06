import { Form, Head, Link } from '@inertiajs/react';
import { Radar } from 'lucide-react';
import ChecklistItemController from '@/actions/App/Http/Controllers/Admin/ChecklistItemController';
import CheckRunController from '@/actions/App/Http/Controllers/Admin/CheckRunController';
import LaunchController from '@/actions/App/Http/Controllers/Admin/LaunchController';
import SignoffController from '@/actions/App/Http/Controllers/Admin/SignoffController';
import {
    FormField,
    focusFirstError,
    selectClassName,
} from '@/components/form-field';
import { CheckResults, RunSummary } from '@/components/launch/check-results';
import { Checklist } from '@/components/launch/checklist';
import { LaunchReadouts } from '@/components/launch/launch-readouts';
import { MissionConsole } from '@/components/launch/mission-console';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { cn } from '@/lib/utils';
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
                        className="lc-card max-w-xl space-y-4 p-5"
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
                    <div className="max-w-5xl space-y-10">
                        <MissionConsole
                            projectName={project.name}
                            url={launch.url}
                            targetLaunchOn={project.targetLaunchOn}
                            board={launch.board}
                            signForm={SignoffController.store.form(project.id)}
                            launchedForm={LaunchController.launched.form(
                                project.id,
                            )}
                        />

                        <LaunchReadouts launch={launch} />

                        <section
                            aria-labelledby="checks-heading"
                            className="space-y-4"
                        >
                            <div className="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <p className="lc-label text-muted-foreground">
                                        Pre-flight · automated
                                    </p>
                                    <h2
                                        id="checks-heading"
                                        className="text-2xl font-extrabold"
                                    >
                                        Automated checks
                                    </h2>
                                    <RunSummary checks={launch.checks} />
                                </div>
                                <Form
                                    {...CheckRunController.store.form(
                                        project.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                    className="flex flex-col items-start gap-2 sm:items-end"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <button
                                                className="lc-pill"
                                                disabled={processing}
                                                data-tour="run-checks"
                                            >
                                                <Radar
                                                    aria-hidden="true"
                                                    className={cn(
                                                        'size-5',
                                                        processing &&
                                                            'animate-spin',
                                                    )}
                                                />
                                                {processing
                                                    ? 'Running checks…'
                                                    : 'Run checks'}
                                            </button>
                                            <p
                                                className="text-sm text-muted-foreground"
                                                role="status"
                                            >
                                                {processing
                                                    ? 'This can take up to 25 seconds.'
                                                    : ''}
                                            </p>
                                            {errors.checks && (
                                                <p
                                                    role="alert"
                                                    className="max-w-sm text-sm font-medium text-destructive"
                                                >
                                                    {errors.checks}
                                                </p>
                                            )}
                                        </>
                                    )}
                                </Form>
                            </div>
                            <CheckResults
                                checks={launch.checks}
                                projectId={project.id}
                            />
                        </section>

                        <section
                            aria-labelledby="checklist-heading"
                            className="space-y-4"
                        >
                            <div>
                                <p className="lc-label text-muted-foreground">
                                    Pre-flight · by hand
                                </p>
                                <h2
                                    id="checklist-heading"
                                    className="text-2xl font-extrabold"
                                >
                                    Manual checklist
                                </h2>
                            </div>
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
                                className="lc-card flex flex-wrap items-end gap-3 p-5"
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
                                                        autoComplete="off"
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

                        <section
                            aria-labelledby="site-heading"
                            className="lc-card space-y-3 p-5"
                        >
                            <h2
                                id="site-heading"
                                className="lc-label text-muted-foreground"
                            >
                                Site settings
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
                    </div>
                )}
            </div>
        </>
    );
}
