import type { ReactNode } from 'react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { Design } from '@/types';

type Zoom = 'fit' | 'actual';

/**
 * One design at a time, with a switcher and two zoom levels. The image sits
 * in a positioned box exactly its own size, so overlays (pins, story 17)
 * can be placed in percentages. Sizes are set through React's style prop,
 * which the CSP allows.
 */
export function DesignViewer({
    designs,
    roundLabel,
    overlay,
    aside,
    toolbar,
    onDesignChange,
}: {
    designs: Design[];
    roundLabel: string;
    /** Drawn over the image, in a box exactly the image's size. */
    overlay?: (design: Design) => ReactNode;
    /** Next to the design on wide screens, below it on narrow ones. */
    aside?: (design: Design) => ReactNode;
    toolbar?: ReactNode;
    onDesignChange?: () => void;
}) {
    const [currentId, setCurrentId] = useState(designs[0]?.id);
    const [zoom, setZoom] = useState<Zoom>('fit');
    const index = Math.max(
        0,
        designs.findIndex((design) => design.id === currentId),
    );
    const design = designs[index];

    if (!design) {
        return (
            <p className="rounded-2xl border border-dashed border-input bg-card px-6 py-10 text-center text-muted-foreground">
                No designs in this round.
            </p>
        );
    }

    const number = String(index + 1).padStart(2, '0');

    return (
        <section
            aria-labelledby="viewer-heading"
            className="space-y-4"
            data-tour="design-viewer"
        >
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div
                    role="group"
                    aria-label={`Designs in ${roundLabel}`}
                    className="flex flex-wrap gap-2"
                    data-tour="design-switcher"
                >
                    {designs.map((item, i) => {
                        const current = item.id === design.id;

                        return (
                            <button
                                key={item.id}
                                type="button"
                                aria-current={current ? 'true' : undefined}
                                onClick={() => {
                                    setCurrentId(item.id);
                                    onDesignChange?.();
                                }}
                                className={cn(
                                    'rounded-xl border px-3 py-2 text-left text-sm font-semibold focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                    current
                                        ? 'border-sidebar bg-sidebar text-sidebar-foreground'
                                        : 'border-border bg-card hover:bg-muted',
                                )}
                            >
                                <span className="font-mono">
                                    {String(i + 1).padStart(2, '0')}
                                </span>{' '}
                                {item.title}
                            </button>
                        );
                    })}
                </div>
                <div
                    role="group"
                    aria-label="Zoom"
                    className="inline-flex rounded-xl border border-border bg-card p-1"
                >
                    {(
                        [
                            ['fit', 'Fit to width'],
                            ['actual', 'Actual size'],
                        ] as const
                    ).map(([value, label]) => (
                        <button
                            key={value}
                            type="button"
                            aria-pressed={zoom === value}
                            onClick={() => setZoom(value)}
                            className={cn(
                                'rounded-lg px-3 py-1.5 text-sm font-semibold focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring',
                                zoom === value
                                    ? 'bg-sidebar text-sidebar-foreground'
                                    : 'hover:bg-muted',
                            )}
                        >
                            {label}
                        </button>
                    ))}
                </div>
            </div>

            {toolbar}

            <h3 id="viewer-heading" className="lc-label text-foreground">
                <span aria-hidden="true" className="text-status-pass">
                    ●
                </span>{' '}
                {number} · {design.title}{' '}
                <span className="text-muted-foreground">
                    · {design.width} × {design.height} px
                </span>
            </h3>

            <div
                className={cn(
                    'grid gap-6',
                    aside && 'xl:grid-cols-[minmax(0,1fr)_20rem]',
                )}
            >
                {/* Focusable, so keyboard users can scroll a long design. */}
                <div
                    role="region"
                    aria-label={`${design.title}, scrollable`}
                    tabIndex={0}
                    className="lc-card lc-edge-ink max-h-[80vh] overflow-auto bg-muted p-3 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    <div
                        className="relative mx-auto"
                        style={{
                            width: design.width,
                            maxWidth: zoom === 'fit' ? '100%' : 'none',
                        }}
                    >
                        <img
                            key={design.id}
                            src={design.imageUrl}
                            alt={`Design: ${design.title}`}
                            width={design.width}
                            height={design.height}
                            className="block h-auto w-full shadow-md"
                        />
                        {overlay?.(design)}
                    </div>
                </div>
                {aside?.(design)}
            </div>
        </section>
    );
}
