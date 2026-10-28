import { Head } from '@inertiajs/react';
import { Plane, PlayCircle } from 'lucide-react';
import { useTour } from '@/components/tour/tour-provider';
import type { Tour } from '@/lib/tour/types';
import { tours } from '@/tours';

/**
 * "Watch it fly" (story 31): pick a tour or a chapter. Nothing plays until
 * the visitor chooses one.
 */
export default function Autopilot({
    demoEnabled,
    signedInAs,
}: {
    demoEnabled: boolean;
    signedInAs: string | null;
}) {
    const { start } = useTour();
    const whole = tours.filter((tour) => tour.group === 'tour');
    const chapters = tours.filter((tour) => tour.group === 'chapter');

    return (
        <>
            <Head title="Autopilot" />
            <div className="max-w-3xl space-y-10">
                <div className="space-y-3">
                    <p className="lc-label text-muted-foreground">
                        Mission 05 · Autopilot
                    </p>
                    <h1 className="text-4xl font-extrabold tracking-tight">
                        Watch it fly
                    </h1>
                    <p className="text-lg">
                        Mission Control runs a small web studio from the first
                        conversation to launch day. Pick a tour and the
                        autopilot drives the real app: it logs in, clicks and
                        types, with a caption for each step.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Pause, skip or leave whenever you like (Esc). Click or
                        type anywhere and the autopilot hands you the controls.
                    </p>
                    {signedInAs && (
                        <p className="text-sm text-muted-foreground">
                            You’re signed in as {signedInAs}. A tour starts a
                            new demo studio, so it always begins the same way.
                        </p>
                    )}
                </div>

                {demoEnabled ? (
                    <>
                        <TourList
                            id="tours-heading"
                            title="Tours"
                            tours={whole}
                            onStart={start}
                        />
                        <TourList
                            id="chapters-heading"
                            title="Chapters"
                            tours={chapters}
                            onStart={start}
                        />
                    </>
                ) : (
                    <p className="lc-card lc-edge-warn p-5">
                        The demo is switched off for a moment. Please try again
                        later.
                    </p>
                )}
            </div>
        </>
    );
}

function TourList({
    id,
    title,
    tours,
    onStart,
}: {
    id: string;
    title: string;
    tours: Tour[];
    onStart: (tour: Tour) => void;
}) {
    return (
        <section aria-labelledby={id} className="space-y-3">
            <h2 id={id} className="text-2xl font-extrabold">
                {title}
            </h2>
            <ul className="grid gap-4 sm:grid-cols-2">
                {tours.map((tour) => (
                    <li
                        key={tour.id}
                        className="lc-card lc-edge-pass flex flex-col gap-3 p-5"
                    >
                        <div className="space-y-1">
                            <p className="lc-label text-muted-foreground">
                                {tour.duration}
                            </p>
                            <h3 className="text-lg font-bold">{tour.title}</h3>
                            <p className="text-sm text-muted-foreground">
                                {tour.description}
                            </p>
                        </div>
                        <button
                            type="button"
                            className="lc-pill mt-auto w-fit"
                            onClick={() => onStart(tour)}
                            data-tour={`start-${tour.id}`}
                        >
                            {tour.group === 'tour' ? (
                                <Plane aria-hidden="true" className="size-5" />
                            ) : (
                                <PlayCircle
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            )}
                            <span>
                                Start
                                <span className="sr-only">: {tour.title}</span>
                            </span>
                        </button>
                    </li>
                ))}
            </ul>
        </section>
    );
}
