import type { ReactNode } from 'react';
import type { Art } from '@/lib/mission-art';
import { cn } from '@/lib/utils';

export function EmptyState({
    title,
    description,
    action,
    art,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
    /** Story 48's picture for this empty screen; decorative. */
    art?: Art;
}) {
    return (
        <div className="rounded-lg border border-dashed border-input bg-card px-6 py-10 text-center">
            {art && <EmptyArt art={art} />}
            <p className="font-display text-lg font-bold">{title}</p>
            {description && (
                <p className="mt-1 text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}

export function EmptyArt({ art, className }: { art: Art; className?: string }) {
    return (
        <img
            src={art.src}
            width={art.width}
            height={art.height}
            alt=""
            loading="lazy"
            decoding="async"
            className={cn(
                'mx-auto mb-4 h-auto max-w-full',
                art.width === art.height ? 'w-32' : 'w-48',
                className,
            )}
        />
    );
}
