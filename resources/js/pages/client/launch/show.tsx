import { Head, Link } from '@inertiajs/react';
import ClientLaunchController from '@/actions/App/Http/Controllers/Client/LaunchController';
import { CheckResults, RunSummary } from '@/components/launch/check-results';
import { Checklist } from '@/components/launch/checklist';
import { GoBoard } from '@/components/launch/go-board';
import { formatDate } from '@/lib/format';
import { show } from '@/routes/client/projects';
import type { Launch } from '@/types';

type Props = {
    project: { id: number; name: string; targetLaunchOn: string | null };
    launch: Launch;
};

export default function ShowLaunch({ project, launch }: Props) {
    return (
        <>
            <Head title={`Launch: ${project.name}`} />
            <div className="space-y-8">
                <div>
                    <Link
                        href={show(project.id)}
                        className="text-sm font-semibold text-primary underline"
                    >
                        {project.name}
                    </Link>
                    <h1 className="mt-3 text-3xl font-extrabold">
                        Launch Control
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        Target launch: {formatDate(project.targetLaunchOn)} ·{' '}
                        <span className="break-all">{launch.url}</span>
                    </p>
                </div>

                <div className="max-w-2xl">
                    <GoBoard
                        board={launch.board}
                        signForm={ClientLaunchController.signoff.form(
                            project.id,
                        )}
                    />
                </div>

                <section
                    aria-labelledby="checks-heading"
                    className="max-w-2xl space-y-3"
                >
                    <h2 id="checks-heading" className="text-xl font-extrabold">
                        Automated checks
                    </h2>
                    <p>
                        The studio runs these against your site before launch:
                        security, search and sharing basics, and speed.
                    </p>
                    <RunSummary checks={launch.checks} />
                    <CheckResults checks={launch.checks} />
                </section>

                <section
                    aria-labelledby="checklist-heading"
                    className="max-w-2xl space-y-3"
                >
                    <h2
                        id="checklist-heading"
                        className="text-xl font-extrabold"
                    >
                        Pre-flight checklist
                    </h2>
                    <p>
                        Tick your items once they're done. The studio takes care
                        of the rest.
                    </p>
                    <Checklist
                        items={launch.checklist}
                        checkRoute={ClientLaunchController.check}
                        uncheckRoute={ClientLaunchController.uncheck}
                    />
                </section>
            </div>
        </>
    );
}
