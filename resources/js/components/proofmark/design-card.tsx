import type { ReactNode } from 'react';
import type { Design } from '@/types';

/**
 * One design on the light table: a "● 01 · HOME, DESKTOP" label, the top
 * of the image, its size, and optional actions underneath.
 */
export function DesignCard({
    design,
    index,
    children,
}: {
    design: Design;
    index: number;
    children?: ReactNode;
}) {
    const number = String(index + 1).padStart(2, '0');

    return (
        <li className="lc-card lc-edge-ink flex flex-col overflow-hidden">
            <div className="aspect-[4/3] overflow-hidden border-b bg-muted">
                <img
                    src={design.imageUrl}
                    alt={`Preview of ${design.title}`}
                    width={design.width}
                    height={design.height}
                    loading="lazy"
                    decoding="async"
                    className="h-full w-full object-cover object-top"
                />
            </div>
            <div className="flex flex-1 flex-col gap-1 p-4">
                <h3 className="lc-label text-foreground">
                    <span aria-hidden="true" className="text-status-pass">
                        ●
                    </span>{' '}
                    {number} · {design.title}
                </h3>
                <p className="text-sm text-muted-foreground">
                    {design.width} × {design.height} px · {design.size}
                </p>
                {children && <div className="mt-auto pt-3">{children}</div>}
            </div>
        </li>
    );
}
