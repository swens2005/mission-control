import { Form } from '@inertiajs/react';
import { useState } from 'react';
import ProposalController from '@/actions/App/Http/Controllers/Admin/ProposalController';
import ProposalLineController from '@/actions/App/Http/Controllers/Admin/ProposalLineController';
import {
    FormField,
    focusById,
    focusFirstError,
    selectClassName,
} from '@/components/form-field';
import { ProposalTotals } from '@/components/scope/proposal-totals';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { LibraryGroup, Option, Proposal, ProposalLine } from '@/types';

const textareaClassName =
    'w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs aria-invalid:border-destructive';

const keep = { preserveScroll: true, preserveState: true } as const;

/**
 * The studio's proposal builder for a draft (story 27).
 */
export function ProposalBuilder({
    proposal,
    library,
    kinds,
    discountTypes,
}: {
    proposal: Proposal;
    library: LibraryGroup[];
    kinds: Option[];
    discountTypes: Option[];
}) {
    return (
        <div className="space-y-8">
            <Details proposal={proposal} />
            <Lines proposal={proposal} />
            <div className="grid gap-5 lg:grid-cols-2 lg:items-start">
                <AddFromLibrary proposalId={proposal.id} library={library} />
                <AddCustomLine proposalId={proposal.id} kinds={kinds} />
            </div>
            <div className="grid gap-5 lg:grid-cols-2 lg:items-start">
                <DiscountForm proposal={proposal} types={discountTypes} />
                <ProposalTotals proposal={proposal} announce />
            </div>
        </div>
    );
}

function Details({ proposal }: { proposal: Proposal }) {
    return (
        <Form
            {...ProposalController.update.form(proposal.id)}
            errorBag="proposal-details"
            options={keep}
            onError={focusFirstError}
            onSuccess={() => focusById('proposal-details-save')}
            className="lc-card lc-edge-ink grid gap-4 p-5"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="lc-label text-muted-foreground">
                        Proposal v{proposal.number} · details
                    </h3>
                    <FormField
                        name="title"
                        id="proposal-title"
                        label="Title"
                        error={errors.title}
                    >
                        {(props) => (
                            <Input
                                {...props}
                                required
                                maxLength={120}
                                autoComplete="off"
                                defaultValue={proposal.title}
                            />
                        )}
                    </FormField>
                    <FormField
                        name="introduction"
                        id="proposal-introduction"
                        label="Introduction"
                        hint="Optional. A few lines to the client: what you understood and what you propose."
                        error={errors.introduction}
                    >
                        {(props) => (
                            <textarea
                                {...props}
                                rows={4}
                                maxLength={5000}
                                autoComplete="off"
                                className={textareaClassName}
                                defaultValue={proposal.introduction ?? ''}
                            />
                        )}
                    </FormField>
                    <div>
                        <Button
                            id="proposal-details-save"
                            variant="secondary"
                            disabled={processing}
                        >
                            Save details
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function Lines({ proposal }: { proposal: Proposal }) {
    return (
        <section aria-labelledby="lines-heading" className="space-y-3">
            <h3 id="lines-heading" tabIndex={-1} className="text-xl font-bold">
                Lines
            </h3>
            {proposal.lines.length === 0 ? (
                <p className="rounded-2xl border border-dashed border-input bg-card px-6 py-8 text-center text-muted-foreground">
                    No lines yet. Add them from the library or as a custom line
                    below.
                </p>
            ) : (
                <ol
                    aria-labelledby="lines-heading"
                    className="space-y-3"
                    data-tour="proposal-lines"
                >
                    {proposal.lines.map((line, i) => (
                        <LineRow
                            key={line.id}
                            line={line}
                            index={i}
                            next={
                                proposal.lines[i + 1] ?? proposal.lines[i - 1]
                            }
                            isFirst={i === 0}
                            isLast={i === proposal.lines.length - 1}
                        />
                    ))}
                </ol>
            )}
        </section>
    );
}

function LineRow({
    line,
    index,
    next,
    isFirst,
    isLast,
}: {
    line: ProposalLine;
    index: number;
    next: ProposalLine | undefined;
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
                        {number} · {line.kindLabel}
                    </p>
                    <h4 className="mt-1 font-bold break-words">{line.name}</h4>
                    {line.description && (
                        <p className="text-sm text-muted-foreground">
                            {line.description}
                        </p>
                    )}
                </div>
                <p className="text-right tabular-nums">
                    <span className="block text-lg font-bold">
                        {line.total}
                    </span>
                    <span className="block text-sm text-muted-foreground">
                        {line.kind === 'hourly'
                            ? `${line.quantity} h × ${line.unitPrice}`
                            : `${line.quantity} × ${line.unitPrice}`}
                    </span>
                </p>
            </div>

            {editing ? (
                <Form
                    {...ProposalLineController.update.form(line.id)}
                    errorBag={`line-${line.id}`}
                    options={keep}
                    onSuccess={() => {
                        setEditing(false);
                        focusById(`edit-line-${line.id}`);
                    }}
                    onError={focusFirstError}
                    className="mt-4 grid gap-4 border-t pt-4 md:grid-cols-2 md:items-start"
                >
                    {({ processing, errors }) => (
                        <>
                            <LineFields
                                idPrefix={`line-${line.id}`}
                                errors={errors}
                                line={line}
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
                                        focusById(`edit-line-${line.id}`);
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
                        id={`edit-line-${line.id}`}
                        size="sm"
                        variant="ghost"
                        aria-label={`Edit ${line.name}`}
                        onClick={() => {
                            setEditing(true);
                            focusById(`line-${line.id}-name`);
                        }}
                    >
                        Edit
                    </Button>
                    {(['up', 'down'] as const).map((direction) => {
                        const id = `move-${direction}-line-${line.id}`;

                        return (
                            <Form
                                key={direction}
                                {...ProposalLineController.move.form(line.id)}
                                transform={(data) => ({ ...data, direction })}
                                options={keep}
                                onSuccess={() =>
                                    focusById(
                                        id,
                                        `move-${direction === 'up' ? 'down' : 'up'}-line-${line.id}`,
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
                                    aria-label={`Move ${line.name} ${direction === 'up' ? 'earlier' : 'later'}`}
                                >
                                    {direction === 'up' ? 'Earlier' : 'Later'}
                                </Button>
                            </Form>
                        );
                    })}
                    <Form
                        {...ProposalLineController.destroy.form(line.id)}
                        options={keep}
                        onBefore={() =>
                            window.confirm(
                                `Remove ${line.name} from the proposal?`,
                            )
                        }
                        onSuccess={() =>
                            focusById(
                                next ? `edit-line-${next.id}` : 'library-item',
                                'lines-heading',
                            )
                        }
                    >
                        <Button
                            size="sm"
                            variant="ghost"
                            aria-label={`Remove ${line.name}`}
                        >
                            Remove
                        </Button>
                    </Form>
                </div>
            )}
        </li>
    );
}

/**
 * Name, description, price and quantity of a line; the kind is chosen
 * separately because an existing line keeps its kind.
 */
function LineFields({
    idPrefix,
    errors,
    line,
}: {
    idPrefix: string;
    errors: Record<string, string | undefined>;
    line?: ProposalLine;
}) {
    return (
        <>
            <FormField
                name="name"
                id={`${idPrefix}-name`}
                label="Name"
                error={errors.name}
            >
                {(props) => (
                    <Input
                        {...props}
                        required
                        maxLength={80}
                        autoComplete="off"
                        defaultValue={line?.name ?? ''}
                    />
                )}
            </FormField>
            <FormField
                name="description"
                id={`${idPrefix}-description`}
                label="Description"
                hint="Optional."
                error={errors.description}
            >
                {(props) => (
                    <Input
                        {...props}
                        maxLength={500}
                        autoComplete="off"
                        defaultValue={line?.description ?? ''}
                    />
                )}
            </FormField>
            <FormField
                name="unit_price"
                id={`${idPrefix}-price`}
                label="Price in euros, excl. VAT"
                hint="Per piece, or per hour."
                error={errors.unit_price}
            >
                {(props) => (
                    <Input
                        {...props}
                        required
                        inputMode="decimal"
                        autoComplete="off"
                        className="tabular-nums"
                        defaultValue={line?.unitPriceInput ?? ''}
                    />
                )}
            </FormField>
            <FormField
                name="quantity"
                id={`${idPrefix}-quantity`}
                label="Quantity"
                hint={
                    line?.kind === 'hourly'
                        ? 'Hours, in quarters (like 1.5).'
                        : line
                          ? 'Pieces.'
                          : 'Pieces, or hours in quarters (like 1.5).'
                }
                error={errors.quantity}
            >
                {(props) => (
                    <Input
                        {...props}
                        required
                        inputMode="decimal"
                        autoComplete="off"
                        className="tabular-nums"
                        defaultValue={line?.quantity ?? '1'}
                    />
                )}
            </FormField>
        </>
    );
}

function AddFromLibrary({
    proposalId,
    library,
}: {
    proposalId: number;
    library: LibraryGroup[];
}) {
    // A new key clears the fields after adding.
    const [formKey, setFormKey] = useState(0);

    return (
        <Form
            {...ProposalLineController.storeFromLibrary.form(proposalId)}
            errorBag="library"
            options={keep}
            onError={focusFirstError}
            onSuccess={() => {
                setFormKey((key) => key + 1);
                focusById('library-item');
            }}
            className="lc-card lc-edge-pass grid gap-4 p-5"
            data-tour="add-from-library"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="lc-label text-muted-foreground">
                        Add from library
                    </h3>
                    {library.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            The Scope library is empty. Add your usual items
                            there, or add a custom line.
                        </p>
                    ) : (
                        <div key={formKey} className="grid gap-4">
                            <FormField
                                name="line_item_id"
                                id="library-item"
                                label="Item"
                                error={errors.line_item_id}
                            >
                                {(props) => (
                                    <select
                                        {...props}
                                        className={selectClassName}
                                        autoComplete="off"
                                        defaultValue=""
                                    >
                                        <option value="">Choose an item</option>
                                        {library.map((group) => (
                                            <optgroup
                                                key={group.label}
                                                label={group.label}
                                            >
                                                {group.items.map((item) => (
                                                    <option
                                                        key={item.id}
                                                        value={item.id}
                                                    >
                                                        {item.name} ·{' '}
                                                        {item.price}
                                                    </option>
                                                ))}
                                            </optgroup>
                                        ))}
                                    </select>
                                )}
                            </FormField>
                            <FormField
                                name="quantity"
                                id="library-quantity"
                                label="Quantity"
                                hint="Optional. Leave empty for the item's usual quantity."
                                error={errors.quantity}
                            >
                                {(props) => (
                                    <Input
                                        {...props}
                                        inputMode="decimal"
                                        autoComplete="off"
                                        className="w-32 tabular-nums"
                                    />
                                )}
                            </FormField>
                            <div>
                                <Button disabled={processing}>
                                    Add to proposal
                                </Button>
                            </div>
                        </div>
                    )}
                </>
            )}
        </Form>
    );
}

function AddCustomLine({
    proposalId,
    kinds,
}: {
    proposalId: number;
    kinds: Option[];
}) {
    const [formKey, setFormKey] = useState(0);

    return (
        <Form
            {...ProposalLineController.store.form(proposalId)}
            errorBag="custom-line"
            options={keep}
            onError={focusFirstError}
            onSuccess={() => {
                setFormKey((key) => key + 1);
                focusById('custom-line-name');
            }}
            className="lc-card lc-edge-ink grid gap-4 p-5"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="lc-label text-muted-foreground">
                        Add a custom line
                    </h3>
                    <div key={formKey} className="grid gap-4">
                        <LineFields idPrefix="custom-line" errors={errors} />
                        <FormField
                            name="kind"
                            id="custom-line-kind"
                            label="Priced"
                            error={errors.kind}
                        >
                            {(props) => (
                                <select
                                    {...props}
                                    className={selectClassName}
                                    autoComplete="off"
                                    defaultValue="fixed"
                                >
                                    {kinds.map((kind) => (
                                        <option
                                            key={kind.value}
                                            value={kind.value}
                                        >
                                            {kind.label}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </FormField>
                    </div>
                    <div>
                        <Button variant="secondary" disabled={processing}>
                            Add custom line
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function DiscountForm({
    proposal,
    types,
}: {
    proposal: Proposal;
    types: Option[];
}) {
    const [type, setType] = useState(proposal.discount.type);

    return (
        <Form
            {...ProposalController.discount.form(proposal.id)}
            errorBag="discount"
            options={keep}
            onError={focusFirstError}
            onSuccess={() => focusById('discount-save')}
            className="lc-card lc-edge-ink grid gap-4 p-5"
            data-tour="proposal-discount"
        >
            {({ processing, errors }) => (
                <>
                    <h3 className="lc-label text-muted-foreground">Discount</h3>
                    <FormField
                        name="discount_type"
                        id="discount-type"
                        label="Discount"
                        error={errors.discount_type}
                    >
                        {(props) => (
                            <select
                                {...props}
                                className={selectClassName}
                                autoComplete="off"
                                value={type}
                                onChange={(event) =>
                                    setType(
                                        event.target
                                            .value as Proposal['discount']['type'],
                                    )
                                }
                            >
                                {types.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        )}
                    </FormField>
                    {type !== 'none' && (
                        <>
                            <FormField
                                name="discount_value"
                                id="discount-value"
                                label={
                                    type === 'percentage'
                                        ? 'Percentage'
                                        : 'Amount in euros, excl. VAT'
                                }
                                hint={
                                    type === 'percentage'
                                        ? 'For example 10 or 12.5.'
                                        : 'Taken off the subtotal, before VAT.'
                                }
                                error={errors.discount_value}
                            >
                                {(props) => (
                                    <Input
                                        {...props}
                                        required
                                        inputMode="decimal"
                                        autoComplete="off"
                                        className="w-40 tabular-nums"
                                        defaultValue={
                                            proposal.discount.type === type
                                                ? proposal.discount.valueInput
                                                : ''
                                        }
                                    />
                                )}
                            </FormField>
                            <FormField
                                name="discount_label"
                                id="discount-label"
                                label="Label"
                                hint="Optional. For example “Returning client”."
                                error={errors.discount_label}
                            >
                                {(props) => (
                                    <Input
                                        {...props}
                                        maxLength={60}
                                        autoComplete="off"
                                        defaultValue={
                                            proposal.discount.label ?? ''
                                        }
                                    />
                                )}
                            </FormField>
                        </>
                    )}
                    {proposal.discount.capped && (
                        <p className="text-sm font-medium text-status-warn">
                            Warning: the discount (
                            {proposal.discount.description}) is more than the
                            subtotal, so it only takes the subtotal. Lower it
                            before sending.
                        </p>
                    )}
                    <div>
                        <Button
                            id="discount-save"
                            variant="secondary"
                            disabled={processing}
                        >
                            Save discount
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
