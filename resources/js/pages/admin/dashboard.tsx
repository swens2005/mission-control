import { Head, Link } from '@inertiajs/react';
import { PhaseRail } from '@/components/phase-rail';
import { formatDate, formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import type { Option } from '@/types';
import { index as activityIndex } from '@/routes/admin/activity';
import { create as createProject } from '@/routes/admin/projects';
import launchBadge from '../../../images/mission-control/badges/launch.webp';
import paletteBadge from '../../../images/mission-control/badges/palette.webp';
import proofmarkBadge from '../../../images/mission-control/badges/proofmark.webp';
import scopeBadge from '../../../images/mission-control/badges/scope.webp';
import launchSite from '../../../images/mission-control/login-hero.webp';
import navigatorEmblem from '../../../images/navigator/emblem.webp';

type Mission = {
    id: number;
    name: string;
    client: string;
    targetLaunchOn: string | null;
    phase: string;
    phaseLabel: string;
    step: number;
    waiting: string[];
    navigator: boolean;
    urls: {
        show: string;
        scope: string;
        palette: string;
        proofmark: string;
        launch: string;
    };
};

type WaitingEntry = {
    title: string;
    module: string;
    client: string | null;
    projectName: string;
    dueOn: string | null;
    url: string;
};

type LogEntry = {
    id: number;
    actor: string;
    description: string;
    createdAt: string;
    project: { id: number; name: string } | null;
};

type Props = {
    firstName: string;
    steps: Option[];
    readouts: {
        inFlight: number;
        waiting: number;
        nextLaunch: { on: string | null; project: string } | null;
        acceptedThisMonth: { amount: string; count: number };
    };
    missions: Mission[];
    waiting: WaitingEntry[];
    log: LogEntry[];
};

const STATIONS = [
    { key: 'scope', label: 'Scope', badge: scopeBadge },
    { key: 'palette', label: 'Palette Lab', badge: paletteBadge },
    { key: 'proofmark', label: 'Proofmark', badge: proofmarkBadge },
    { key: 'launch', label: 'Launch Control', badge: launchBadge },
] as const;

function greeting(now: Date): string {
    const hour = now.getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
}

function plural(count: number, one: string, many: string): string {
    return `${count} ${count === 1 ? one : many}`;
}

/** "In flight · 4 missions. Next launch: Repair booking, 28 Nov 2026." */
function summary(readouts: Props['readouts']): string {
    if (readouts.inFlight === 0) {
        return 'No missions in flight. Start one with a new project.';
    }

    const flying = `${plural(readouts.inFlight, 'mission', 'missions')} in flight.`;
    const next = readouts.nextLaunch
        ? ` Next launch: ${readouts.nextLaunch.project}, ${formatDate(readouts.nextLaunch.on)}.`
        : '';

    return flying + next;
}

/** Today's entries show the time; older ones the date. */
function logTime(createdAt: string): string {
    const date = new Date(createdAt);

    if (date.toDateString() === new Date().toDateString()) {
        return date.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    return formatDateTime(createdAt).split(',')[0];
}

export default function Dashboard({
    firstName,
    steps,
    readouts,
    missions,
    waiting,
    log,
}: Props) {
    const now = new Date();

    return (
        <>
            <Head title="Flight deck" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <section className="fd-hero" aria-labelledby="deck-heading">
                    <div className="fd-hero-copy">
                        <p className="hud-label text-[var(--nav-brass)]">
                            Flight deck ·{' '}
                            {now.toLocaleDateString('en-GB', {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}
                        </p>
                        <h1
                            id="deck-heading"
                            className="mt-1 font-display text-3xl font-extrabold md:text-4xl"
                        >
                            {greeting(now)}, {firstName}
                        </h1>
                        <p className="mt-2 text-foreground">
                            {summary(readouts)}
                        </p>
                    </div>
                    <img
                        src={launchSite}
                        alt=""
                        width={1600}
                        height={900}
                        className="fd-hero-art"
                    />
                </section>

                <section aria-label="Studio readouts">
                    <dl className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <Readout
                            label="In flight"
                            value={String(readouts.inFlight)}
                            note={
                                readouts.inFlight === 1 ? 'project' : 'projects'
                            }
                        />
                        <Readout
                            label="Waiting on clients"
                            value={String(readouts.waiting)}
                            note={
                                readouts.waiting === 1
                                    ? 'approval or sign-off'
                                    : 'approvals and sign-offs'
                            }
                        />
                        <Readout
                            label="Next launch"
                            value={
                                readouts.nextLaunch
                                    ? formatDate(readouts.nextLaunch.on)
                                    : 'None set'
                            }
                            note={
                                readouts.nextLaunch?.project ??
                                'No target dates ahead'
                            }
                        />
                        <Readout
                            label="Accepted this month"
                            value={readouts.acceptedThisMonth.amount}
                            note={plural(
                                readouts.acceptedThisMonth.count,
                                'proposal',
                                'proposals',
                            )}
                        />
                    </dl>
                </section>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">
                    <section aria-labelledby="missions-heading">
                        <div className="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                            <h2
                                id="missions-heading"
                                className="font-display text-xl font-extrabold"
                            >
                                Missions
                            </h2>
                            <span className="hud-label text-muted-foreground">
                                Soonest launch first
                            </span>
                        </div>

                        {missions.length === 0 ? (
                            <div className="lc-card p-6">
                                <p>No missions in flight right now.</p>
                                <Link
                                    href={createProject()}
                                    className="mt-3 inline-block font-semibold text-primary underline"
                                >
                                    New project
                                </Link>
                            </div>
                        ) : (
                            <ul className="flex flex-col gap-3">
                                {missions.map((mission) => (
                                    <li key={mission.id}>
                                        <MissionCard
                                            mission={mission}
                                            steps={steps}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <div className="flex flex-col gap-6">
                        <section
                            className="lc-card lc-edge-warn p-5"
                            aria-labelledby="waiting-heading"
                        >
                            <div className="mb-3 flex items-baseline justify-between gap-2">
                                <h2
                                    id="waiting-heading"
                                    className="font-display text-lg font-extrabold"
                                >
                                    Waiting on clients
                                </h2>
                                <span className="hud-label text-muted-foreground">
                                    {waiting.length}
                                </span>
                            </div>
                            {waiting.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nothing. Every client is up to date.
                                </p>
                            ) : (
                                <ul className="flex flex-col gap-2">
                                    {waiting.map((item) => (
                                        <li
                                            key={`${item.url}-${item.title}`}
                                            className="fd-waiting"
                                        >
                                            <span className="hud-label flex items-center gap-2 text-status-warn">
                                                <span
                                                    aria-hidden="true"
                                                    className="size-2 rounded-full bg-[var(--edge-warn)]"
                                                />
                                                {item.module}
                                                {item.client
                                                    ? ` · ${item.client}`
                                                    : ''}
                                            </span>
                                            <Link
                                                href={item.url}
                                                className="font-semibold hover:underline"
                                            >
                                                {item.title}
                                                <span className="sr-only">
                                                    {' '}
                                                    ({item.projectName})
                                                </span>
                                            </Link>
                                            <span className="text-sm text-muted-foreground">
                                                {item.projectName}
                                                {item.dueOn
                                                    ? ` · launch ${formatDate(item.dueOn)}`
                                                    : ''}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section
                            className="lc-card lc-edge-pass p-5"
                            aria-labelledby="log-heading"
                        >
                            <div className="mb-3 flex items-baseline justify-between gap-2">
                                <h2
                                    id="log-heading"
                                    className="font-display text-lg font-extrabold"
                                >
                                    Mission log
                                </h2>
                                <Link
                                    href={activityIndex()}
                                    className="hud-label text-muted-foreground hover:text-foreground hover:underline"
                                >
                                    All activity
                                </Link>
                            </div>
                            {log.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nothing logged yet.
                                </p>
                            ) : (
                                <ol className="flex flex-col gap-2 font-mono text-[13px] leading-snug">
                                    {log.map((entry) => (
                                        <li
                                            key={entry.id}
                                            className="grid grid-cols-[4.5rem_1fr] gap-3"
                                        >
                                            <time
                                                dateTime={entry.createdAt}
                                                className="text-muted-foreground"
                                            >
                                                {logTime(entry.createdAt)}
                                            </time>
                                            <span>
                                                <span className="text-primary">
                                                    {entry.actor}
                                                </span>{' '}
                                                {entry.description}
                                            </span>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}

function Readout({
    label,
    value,
    note,
}: {
    label: string;
    value: string;
    note: string;
}) {
    return (
        <div className="fd-readout">
            <dt className="hud-label text-muted-foreground">{label}</dt>
            <dd className="font-mono text-2xl font-medium tabular-nums md:text-3xl">
                {value}
            </dd>
            <dd className="text-sm text-muted-foreground">{note}</dd>
        </div>
    );
}

function MissionCard({
    mission,
    steps,
}: {
    mission: Mission;
    steps: Option[];
}) {
    const waiting = mission.waiting.length > 0;

    return (
        <article
            className={cn(
                'lc-card p-5',
                mission.navigator
                    ? 'fd-edge-violet'
                    : waiting
                      ? 'lc-edge-warn'
                      : 'lc-edge-pass',
            )}
            aria-labelledby={`mission-${mission.id}`}
        >
            <div className="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                <div className="min-w-0">
                    <p className="hud-label text-muted-foreground">
                        {mission.client}
                    </p>
                    <h3
                        id={`mission-${mission.id}`}
                        className="font-display text-lg font-extrabold"
                    >
                        <Link
                            href={mission.urls.show}
                            className="hover:underline"
                        >
                            {mission.name}
                        </Link>
                    </h3>
                </div>
                <p className="text-right">
                    <span className="block font-mono tabular-nums">
                        {formatDate(mission.targetLaunchOn)}
                    </span>
                    <span className="hud-label text-muted-foreground">
                        Target launch
                    </span>
                </p>
            </div>

            <PhaseRail
                className="mt-4"
                steps={steps}
                step={mission.step}
                label={`${mission.name} progress`}
            />

            {mission.navigator && (
                <div className="mt-4 flex items-center gap-4">
                    <img
                        src={navigatorEmblem}
                        alt=""
                        width={64}
                        height={64}
                        className="size-16 shrink-0"
                    />
                    <div>
                        <p className="font-display font-extrabold">
                            Navigator can draft this proposal
                        </p>
                        <p className="text-sm text-muted-foreground">
                            The intake is in. Navigator picks lines from your
                            price list and sets quantities; you approve every
                            line.
                        </p>
                        <Link
                            href={mission.urls.scope}
                            className="fd-violet mt-2"
                        >
                            Open Scope to draft
                        </Link>
                    </div>
                </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2">
                {waiting ? (
                    mission.waiting.map((title) => (
                        <span key={title} className="fd-pill fd-pill-wait">
                            Waiting: {title}
                        </span>
                    ))
                ) : (
                    <span className="fd-pill fd-pill-go">
                        Nothing waiting on the client
                    </span>
                )}
                <nav
                    aria-label={`Stations for ${mission.name}`}
                    className="ml-auto flex gap-2"
                >
                    {STATIONS.map((station) => (
                        <Link
                            key={station.key}
                            href={mission.urls[station.key]}
                            className="fd-station"
                            title={station.label}
                        >
                            <img
                                src={station.badge}
                                alt={station.label}
                                width={30}
                                height={30}
                            />
                        </Link>
                    ))}
                </nav>
            </div>
        </article>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Flight deck', href: dashboard() }],
};
