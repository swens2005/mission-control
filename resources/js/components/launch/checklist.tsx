import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ChecklistItem } from '@/types';
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

    const groups = (['studio', 'client'] as const)
        .map((owner) => ({
            owner,
            items: items.filter((item) => item.owner === owner),
        }))
        .filter((group) => group.items.length > 0);

    return (
        <div className="space-y-3">
            <p className="text-sm text-muted-foreground" aria-live="polite">
                {done} of {items.length} done
            </p>
            <div
                className="grid gap-4 lg:grid-cols-2"
                data-tour="launch-checklist"
            >
                {groups.map((group) => {
                    const groupDone = group.items.filter(
                        (item) => item.checked,
                    ).length;
                    const complete = groupDone === group.items.length;

                    return (
                        <section
                            key={group.owner}
                            aria-labelledby={`checklist-${group.owner}`}
                            className={cn(
                                'lc-card p-5',
                                complete ? 'lc-edge-pass' : 'lc-edge-ink',
                            )}
                        >
                            <h3
                                id={`checklist-${group.owner}`}
                                className="lc-label flex items-center gap-2 text-muted-foreground"
                            >
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'lc-lamp size-2.5',
                                        complete && 'lc-lamp-go',
                                    )}
                                />
                                {group.owner === 'studio' ? 'Studio' : 'Client'}{' '}
                                · {groupDone} of {group.items.length}
                            </h3>
                            <ul className="mt-2 divide-y">
                                {group.items.map((item) => (
                                    <ChecklistRow
                                        key={item.id}
                                        item={item}
                                        saving={savingId === item.id}
                                        onToggle={toggle}
                                        actions={renderActions?.(item)}
                                    />
                                ))}
                            </ul>
                        </section>
                    );
                })}
            </div>
        </div>
    );
}

function ChecklistRow({
    item,
    saving,
    onToggle,
    actions,
}: {
    item: ChecklistItem;
    saving: boolean;
    onToggle: (item: ChecklistItem, checked: boolean) => void;
    actions?: React.ReactNode;
}) {
    const id = `checklist-item-${item.id}`;

    return (
        <li
            className="flex flex-wrap items-start gap-3 py-3"
            data-tour="checklist-item"
        >
            <input
                id={id}
                type="checkbox"
                className="mt-0.5 size-5 shrink-0 accent-primary"
                checked={item.checked}
                disabled={!item.canToggle}
                aria-busy={saving}
                aria-describedby={`${id}-details`}
                onChange={(event) => onToggle(item, event.target.checked)}
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
                                {' · '}Ticked by {item.checkedBy}
                                {item.checkedAt &&
                                    `, ${formatDay(item.checkedAt)}`}
                            </>
                        )}
                        {!item.canToggle &&
                            ` · Only the ${item.ownerLabel.toLowerCase()} can tick this`}
                    </p>
                </div>
            </div>
            {actions}
        </li>
    );
}

function formatDay(iso: string): string {
    return new Date(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}
