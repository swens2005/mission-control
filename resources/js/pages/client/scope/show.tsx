import { Head, Link } from '@inertiajs/react';
import { IntakeAnswers } from '@/components/scope/intake-answers';
import { IntakeForm } from '@/components/scope/intake-form';
import { formatDate, formatDateTime } from '@/lib/format';
import { show } from '@/routes/client/projects';
import type { Intake, IntakeOptions } from '@/types';

type Props = {
    project: { id: number; name: string; targetLaunchOn: string | null };
    intake: Intake;
    options: IntakeOptions;
};

export default function ShowScope({ project, intake, options }: Props) {
    return (
        <>
            <Head title={`Scope: ${project.name}`} />
            <div className="space-y-10">
                <div>
                    <Link
                        href={show(project.id)}
                        className="text-sm font-semibold text-primary underline"
                    >
                        {project.name}
                    </Link>
                    <h1 className="mt-3 text-4xl font-extrabold tracking-tight">
                        Scope
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        What you need from the new website · Target launch:{' '}
                        {formatDate(project.targetLaunchOn)}
                    </p>
                </div>

                <section
                    aria-labelledby="intake-status"
                    className={`lc-card max-w-3xl space-y-2 p-5 ${intake.submitted ? 'lc-edge-pass' : 'lc-edge-warn'}`}
                >
                    <p className="lc-label text-muted-foreground">
                        Intake · {intake.submitted ? 'sent' : 'to do'}
                    </p>
                    <h2
                        id="intake-status"
                        tabIndex={-1}
                        className="text-xl font-bold"
                    >
                        {intake.submitted
                            ? 'Sent to the studio'
                            : 'Tell us what you need'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {intake.submitted
                            ? `Sent by ${intake.submittedByName ?? 'you'} on ${formatDateTime(intake.submittedAt)}. The studio uses your answers to write a proposal. Need to change something? Ask the studio to reopen it.`
                            : 'A few questions about your goals, pages, features, budget and deadline. Save a draft any time; send it when you’re done.'}
                    </p>
                </section>

                {intake.submitted ? (
                    <div className="max-w-3xl">
                        <IntakeAnswers intake={intake} />
                    </div>
                ) : (
                    <IntakeForm intake={intake} options={options} />
                )}
            </div>
        </>
    );
}
