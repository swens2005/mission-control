import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ChecklistItem } from '@/types';
import { usePortal } from '@/hooks/use-portal';
import { cn } from '@/lib/utils';

type Route = { url: string; method: 'put' | 'delete' };

/**
 * The manual launch checklist, shared by both portals. Each item is a
 * native checkbox that saves as soon as it changes.
 */
export function Checklist({
    items,
    checkRoute,
    uncheckRoute,
    renderActions,
}: {
    items: ChecklistItem[];
    checkRoute: (id: number) => Route;
    uncheckRoute: (id: number) => Route;
    renderActions?: (item: ChecklistItem) => React.ReactNode;
}) {
    const { portal } = usePortal();
    const [savingId, setSavingId] = useState<number | null>(null);

    // Not disabled while saving: a disabled checkbox drops keyboard focus.
    // Further changes are ignored until the save finishes instead.
    const toggle = (item: ChecklistItem, checked: boolean) => {
        if (savingId !== null) {
            return;
        }

        const route = checked ? checkRoute(item.id) : uncheckRoute(item.id);
        setSavingId(item.id);
        router.visit(route.url, {
            method: route.method,
            preserveScroll: true,
            // Keep the page mounted, so the focused checkbox keeps focus.
            preserveState: true,
            onFinish: () => setSavingId(null),
        });
    };

    const done = items.filter((item) => item.checked).length;

    return (
        <div className="space-y-3">
            <p className="text-sm text-muted-foreground" aria-live="polite">
                {done} of {items.length} done
            </p>
            <ul
                className={cn(
                    'divide-y bg-card',
                    portal === 'client'
                        ? 'divide-foreground rounded-sm border-2 border-foreground'
                        : 'rounded-lg border',
                )}
                data-tour="launch-checklist"
            >
                {items.map((item) => {
                    const id = `checklist-item-${item.id}`;

                    return (
                        <li
                            key={item.id}
                            className="flex flex-wrap items-start gap-3 p-4"
                            data-tour="checklist-item"
                        >
                            <input
                                id={id}
                                type="checkbox"
                                className="mt-0.5 size-5 shrink-0 accent-primary"
                                checked={item.checked}
                                disabled={!item.canToggle}
                                aria-busy={savingId === item.id}
                                aria-describedby={`${id}-details`}
                                onChange={(event) =>
                                    toggle(item, event.target.checked)
                                }
                            />
                            <div className="min-w-0 flex-1">
                                <label htmlFor={id} className="font-semibold">
                                    {item.label}
                                </label>
                                <div
                                    id={`${id}-details`}
                                    className="text-sm text-muted-foreground"
                                >
                                    {item.hint && <p>{item.hint}</p>}
                                    <p>
                                        Owner: {item.ownerLabel}
                                        {item.checked && item.checkedBy && (
                                            <>
                                                {' · '}Ticked by{' '}
                                                {item.checkedBy}
                                                {item.checkedAt &&
                                                    `, ${formatDay(item.checkedAt)}`}
                                            </>
                                        )}
                                        {!item.canToggle &&
                                            ` · Only the ${item.ownerLabel.toLowerCase()} can tick this`}
                                    </p>
                                </div>
                            </div>
                            {renderActions?.(item)}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

function formatDay(iso: string): string {
    return new Date(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}
