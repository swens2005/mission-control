import { Form, Head, Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import ScopeController from '@/actions/App/Http/Controllers/Admin/ScopeController';
import { focusById } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { IntakeAnswers } from '@/components/scope/intake-answers';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { formatDateTime } from '@/lib/format';
import { index, show } from '@/routes/admin/projects';
import type { Intake, Project } from '@/types';

type Props = {
    project: Project;
    intake: Intake | null;
};

export default function ShowScope({ project, intake }: Props) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Scope', href: ScopeController.show(project.id) },
    ]);

    return (
        <>
            <Head title={`Scope: ${project.name}`} />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title="Scope"
                    description={
                        <>
                            Intake and proposal for{' '}
                            <Link
                                href={show(project.id)}
                                className="text-primary underline"
                            >
                                {project.name}
                            </Link>
                        </>
                    }
                />

                {intake === null ? (
                    <section className="lc-card lc-edge-ink max-w-xl space-y-4 p-6">
                        <p className="lc-label text-muted-foreground">
                            Intake · not asked
                        </p>
                        <h2 className="text-xl font-bold">No intake yet</h2>
                        <p className="text-sm text-muted-foreground">
                            Ask {project.organization.name} about their goals,
                            pages, features, budget and deadline. They fill it
                            in from Launchpad.
                        </p>
                        <Form
                            {...ScopeController.askIntake.form(project.id)}
                            onSuccess={() => focusById('intake-status')}
                        >
                            {({ processing }) => (
                                <button
                                    className="lc-pill"
                                    disabled={processing}
                                    data-tour="ask-intake"
                                >
                                    <ClipboardList
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                    Ask the client
                                </button>
                            )}
                        </Form>
                    </section>
                ) : (
                    <section
                        aria-labelledby="intake-status"
                        className="space-y-5"
                    >
                        <div
                            className={`lc-card max-w-3xl space-y-3 p-5 ${intake.submitted ? 'lc-edge-pass' : 'lc-edge-warn'}`}
                        >
                            <p className="lc-label text-muted-foreground">
                                Intake ·{' '}
                                {intake.submitted ? 'received' : 'waiting'}
                            </p>
                            <h2
                                id="intake-status"
                                tabIndex={-1}
                                className="text-xl font-bold"
                            >
                                {intake.submitted
                                    ? 'Intake received'
                                    : 'Waiting for the client'}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {intake.submitted
                                    ? `Sent by ${intake.submittedByName ?? 'the client'} on ${formatDateTime(intake.submittedAt)}.`
                                    : `Asked on ${formatDateTime(intake.requestedAt)}. The answers appear here once the client sends them.`}
                            </p>
                            {intake.submitted && (
                                <Form
                                    {...ScopeController.reopenIntake.form(
                                        intake.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                    onBefore={() =>
                                        window.confirm(
                                            'Reopen the intake? The client can change the answers and has to send them again.',
                                        )
                                    }
                                    onSuccess={() => focusById('intake-status')}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="secondary"
                                            disabled={processing}
                                        >
                                            Reopen for changes
                                        </Button>
                                    )}
                                </Form>
                            )}
                        </div>

                        {intake.submitted && <IntakeAnswers intake={intake} />}
                    </section>
                )}
            </div>
        </>
    );
}
