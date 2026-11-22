import type { ReactNode } from 'react';
import type { Art } from '@/lib/mission-art';

export function PageHeader({
    title,
    description,
    actions,
    emblem,
    landmark,
}: {
    title: string;
    description?: ReactNode;
    actions?: ReactNode;
    /** A station badge beside the title (story 48); decorative. */
    emblem?: string;
    /** A station landmark at the right, from 768 px; decorative. */
    landmark?: Art;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="flex min-w-0 items-center gap-3 sm:gap-4">
                {emblem && (
                    <img
                        src={emblem}
                        width={256}
                        height={256}
                        alt=""
                        decoding="async"
                        className="size-12 shrink-0 sm:size-16"
                    />
                )}
                <div className="min-w-0">
                    <h1 className="text-2xl font-bold break-words">{title}</h1>
                    {description && (
                        <div className="mt-1 text-sm text-muted-foreground">
                            {description}
                        </div>
                    )}
                </div>
            </div>
            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
            {landmark && (
                <img
                    src={landmark.src}
                    width={landmark.width}
                    height={landmark.height}
                    alt=""
                    decoding="async"
                    className="-my-4 hidden h-auto w-48 shrink-0 md:block lg:w-56"
                />
            )}
        </div>
    );
}
