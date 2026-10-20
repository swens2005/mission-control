import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import LineItemController from '@/actions/App/Http/Controllers/Admin/LineItemController';
import { focusById, focusFirstError } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { LineItemFields } from '@/components/scope/line-item-fields';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import type { LibraryItem, Option } from '@/types';

type Props = {
    items: LibraryItem[];
    archived: LibraryItem[];
    kinds: Option[];
};

export default function Library({ items, archived, kinds }: Props) {
    useBreadcrumbs([
        { title: 'Scope library', href: LineItemController.index() },
    ]);

    // The fields keep their own state; a new key clears them after adding.
    const [formKey, setFormKey] = useState(0);

    return (
        <>
            <Head title="Scope library" />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title="Scope library"
                    description="Your price list for proposals. Prices exclude VAT. A proposal keeps its own copy of each item, so changes here never change a proposal."
                />

                <section aria-labelledby="items-heading" className="space-y-5">
                    <div>
                        <p className="lc-label text-muted-foreground">
                            Scope · line items
                        </p>
                        <h2
                            id="items-heading"
                            tabIndex={-1}
                            className="text-2xl font-extrabold"
                        >
                            Line items
                        </h2>
                    </div>

                    {items.length === 0 ? (
                        <p className="max-w-3xl rounded-2xl border border-dashed border-input bg-card px-6 py-10 text-center text-muted-foreground">
                            No line items yet. Add the things you usually sell
                            below.
                        </p>
                    ) : (
                        <ol
                            aria-labelledby="items-heading"
                            className="max-w-4xl space-y-3"
                        >
                            {items.map((item, i) => (
                                <ItemRow
                                    key={item.id}
                                    item={item}
                                    index={i}
                                    kinds={kinds}
                                    isFirst={i === 0}
                                    isLast={i === items.length - 1}
                                />
                            ))}
                        </ol>
                    )}

                    <Form
                        {...LineItemController.store.form()}
                        onError={focusFirstError}
                        options={{ preserveScroll: true }}
                        onSuccess={() => {
                            setFormKey((key) => key + 1);
                            focusById('new-item-name');
                        }}
                        className="lc-card lc-edge-pass grid max-w-4xl gap-4 p-5 md:grid-cols-2 md:items-start"
                        data-tour="add-line-item"
                    >
                        {({ processing, errors }) => (
                            <>
                                <h3 className="lc-label text-muted-foreground md:col-span-2">
                                    Add a line item
                                </h3>
                                <LineItemFields
                                    key={formKey}
                                    idPrefix="new-item"
                                    kinds={kinds}
                                    errors={errors}
                                />
                                <div className="md:col-span-2">
                                    <Button disabled={processing}>
                                        Add line item
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                {archived.length > 0 && (
                    <section
                        aria-labelledby="archived-heading"
                        className="max-w-4xl space-y-3"
                    >
                        <h2
                            id="archived-heading"
                            tabIndex={-1}
                            className="text-xl font-bold"
                        >
                            Archived
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Not offered in new proposals. Proposals that already
                            use them are unchanged.
                        </p>
                        <ul className="divide-y rounded-2xl border bg-card">
                            {archived.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                                >
                                    <span>
                                        <span className="font-semibold">
                                            {item.name}
                                        </span>{' '}
                                        <span className="text-muted-foreground tabular-nums">
                                            · {item.unitPrice}
                                        </span>
                                    </span>
                                    <Form
                                        {...LineItemController.restore.form(
                                            item.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                        onSuccess={() =>
                                            focusById(
                                                `edit-item-${item.id}`,
                                                'items-heading',
                                            )
                                        }
                                    >
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            aria-label={`Restore ${item.name}`}
                                        >
                                            Restore
                                        </Button>
                                    </Form>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

function ItemRow({
    item,
    index,
    kinds,
    isFirst,
    isLast,
}: {
    item: LibraryItem;
    index: number;
    kinds: Option[];
    isFirst: boolean;
    isLast: boolean;
}) {
    const [editing, setEditing] = useState(false);
    const number = String(index + 1).padStart(2, '0');

    return (
        <li className="lc-card lc-edge-ink p-4">
            <div className="flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
                <div className="min-w-0 flex-1">
                    <p className="lc-label text-muted-foreground">
                        <span aria-hidden="true" className="text-status-pass">
                            ●
                        </span>{' '}
                        {number} · {item.kindLabel}
                    </p>
                    <h3 className="mt-1 font-bold break-words">{item.name}</h3>
                    {item.description && (
                        <p className="text-sm text-muted-foreground">
                            {item.description}
                        </p>
                    )}
                </div>
                <p className="text-right tabular-nums">
                    <span className="block text-lg font-bold">
                        {item.unitPrice}
                    </span>
                    <span className="block text-sm text-muted-foreground">
                        {item.kind === 'hourly'
                            ? `per hour · usually ${item.defaultQuantity} h`
                            : `per piece · usually ${item.defaultQuantity}`}
                    </span>
                </p>
            </div>

            {editing ? (
                <Form
                    {...LineItemController.update.form(item.id)}
                    errorBag={`item-${item.id}`}
                    options={{ preserveScroll: true, preserveState: true }}
                    onSuccess={() => {
                        setEditing(false);
                        focusById(`edit-item-${item.id}`);
                    }}
                    onError={focusFirstError}
                    className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-2 md:items-start"
                >
                    {({ processing, errors }) => (
                        <>
                            <LineItemFields
                                idPrefix={`item-${item.id}`}
                                kinds={kinds}
                                errors={errors}
                                item={item}
                            />
                            <div className="flex gap-2 md:col-span-2">
                                <Button size="sm" disabled={processing}>
                                    Save
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => {
                                        setEditing(false);
                                        focusById(`edit-item-${item.id}`);
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            ) : (
                <div className="mt-3 flex flex-wrap gap-1">
                    <Button
                        id={`edit-item-${item.id}`}
                        size="sm"
                        variant="ghost"
                        aria-label={`Edit ${item.name}`}
                        onClick={() => {
                            setEditing(true);
                            focusById(`item-${item.id}-name`);
                        }}
                    >
                        Edit
                    </Button>
                    {(['up', 'down'] as const).map((direction) => {
                        const id = `move-${direction}-item-${item.id}`;

                        return (
                            <Form
                                key={direction}
                                {...LineItemController.move.form(item.id)}
                                transform={(data) => ({ ...data, direction })}
                                options={{
                                    preserveScroll: true,
                                    preserveState: true,
                                }}
                                onSuccess={() =>
                                    focusById(
                                        id,
                                        `move-${direction === 'up' ? 'down' : 'up'}-item-${item.id}`,
                                    )
                                }
                            >
                                <Button
                                    id={id}
                                    size="sm"
                                    variant="ghost"
                                    disabled={
                                        direction === 'up' ? isFirst : isLast
                                    }
                                    aria-label={`Move ${item.name} ${direction === 'up' ? 'earlier' : 'later'}`}
                                >
                                    {direction === 'up' ? 'Earlier' : 'Later'}
                                </Button>
                            </Form>
                        );
                    })}
                    <Form
                        {...LineItemController.archive.form(item.id)}
                        options={{ preserveScroll: true, preserveState: true }}
                        onSuccess={() => focusById('items-heading')}
                    >
                        <Button
                            size="sm"
                            variant="ghost"
                            aria-label={`Archive ${item.name}`}
                        >
                            Archive
                        </Button>
                    </Form>
                </div>
            )}
        </li>
    );
}
