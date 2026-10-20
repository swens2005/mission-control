import { FormField, selectClassName } from '@/components/form-field';
import { Input } from '@/components/ui/input';
import type { LibraryItem, Option } from '@/types';

/**
 * The fields of a library item (story 26), for adding and editing. Ids are
 * prefixed so several forms can be on the page at once.
 */
export function LineItemFields({
    idPrefix,
    kinds,
    errors,
    item,
}: {
    idPrefix: string;
    kinds: Option[];
    errors: Record<string, string | undefined>;
    item?: LibraryItem;
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
                        defaultValue={item?.name ?? ''}
                    />
                )}
            </FormField>
            <FormField
                name="kind"
                id={`${idPrefix}-kind`}
                label="Priced"
                error={errors.kind}
            >
                {(props) => (
                    <select
                        {...props}
                        className={selectClassName}
                        autoComplete="off"
                        defaultValue={item?.kind ?? 'fixed'}
                    >
                        {kinds.map((kind) => (
                            <option key={kind.value} value={kind.value}>
                                {kind.label}
                            </option>
                        ))}
                    </select>
                )}
            </FormField>
            <div className="md:col-span-2">
                <FormField
                    name="description"
                    id={`${idPrefix}-description`}
                    label="Description"
                    hint="Optional. What the client gets, in a sentence."
                    error={errors.description}
                >
                    {(props) => (
                        <textarea
                            {...props}
                            rows={2}
                            maxLength={500}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs aria-invalid:border-destructive"
                            autoComplete="off"
                            defaultValue={item?.description ?? ''}
                        />
                    )}
                </FormField>
            </div>
            <FormField
                name="unit_price"
                id={`${idPrefix}-price`}
                label="Price in euros, excl. VAT"
                hint="Per piece, or per hour. For example 1250 or 1.250,50."
                error={errors.unit_price}
            >
                {(props) => (
                    <Input
                        {...props}
                        required
                        inputMode="decimal"
                        autoComplete="off"
                        className="tabular-nums"
                        defaultValue={item?.unitPriceInput ?? ''}
                        data-tour={item ? undefined : 'line-item-price'}
                    />
                )}
            </FormField>
            <FormField
                name="default_quantity"
                id={`${idPrefix}-quantity`}
                label="Usual quantity"
                hint="Pieces for a fixed price; hours (in quarters, like 1.5) for hourly."
                error={errors.default_quantity}
            >
                {(props) => (
                    <Input
                        {...props}
                        required
                        inputMode="decimal"
                        autoComplete="off"
                        className="tabular-nums"
                        defaultValue={item?.defaultQuantity ?? '1'}
                    />
                )}
            </FormField>
        </>
    );
}
