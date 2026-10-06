import { Form } from '@inertiajs/react';
import { useState } from 'react';
import TypeController from '@/actions/App/Http/Controllers/Admin/TypeController';
import {
    FormField,
    focusFirstError,
    selectClassName,
} from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { KitType, TypeOptions } from '@/types';

/**
 * Fonts and the modular scale (story 22): the form for the studio, and a
 * specimen that both portals can show.
 */
export function TypeSection({
    kitId,
    type,
    options,
    editable,
}: {
    kitId: number;
    type: KitType;
    options: TypeOptions;
    editable: boolean;
}) {
    return (
        <section aria-labelledby="type-heading" className="space-y-5">
            <div>
                <p className="lc-label text-muted-foreground">
                    Brand kit · type
                </p>
                <h2 id="type-heading" className="text-2xl font-extrabold">
                    Type
                </h2>
                <p className="text-sm text-muted-foreground">
                    {type.headingLabel} for headings, {type.bodyLabel} for text.
                    Each size is {type.ratio} times the one below, starting at{' '}
                    {type.baseSizePx} px.
                </p>
            </div>

            <div className="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                {editable && (
                    <TypeForm kitId={kitId} type={type} options={options} />
                )}
                <TypeSpecimen type={type} />
            </div>
        </section>
    );
}

function TypeForm({
    kitId,
    type,
    options,
}: {
    kitId: number;
    type: KitType;
    options: TypeOptions;
}) {
    const [preset, setPreset] = useState(type.ratioPreset);

    return (
        <Form
            {...TypeController.update.form(kitId)}
            errorBag="type"
            onError={focusFirstError}
            options={{ preserveScroll: true, preserveState: true }}
            className="lc-card lc-edge-ink h-fit space-y-4 p-5"
            data-tour="type-scale"
        >
            {({ processing, errors }) => (
                <>
                    {(
                        [
                            ['heading_font', 'Heading font', type.headingFont],
                            ['body_font', 'Body font', type.bodyFont],
                        ] as const
                    ).map(([name, label, value]) => (
                        <FormField
                            key={name}
                            name={name}
                            label={label}
                            error={errors[name]}
                        >
                            {(props) => (
                                <select
                                    {...props}
                                    className={selectClassName}
                                    defaultValue={value}
                                    autoComplete="off"
                                >
                                    {options.fonts.map((font) => (
                                        <option
                                            key={font.value}
                                            value={font.value}
                                        >
                                            {font.label}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </FormField>
                    ))}
                    <FormField
                        name="base_size_px"
                        label="Base size (px)"
                        error={errors.base_size_px}
                    >
                        {(props) => (
                            <Input
                                {...props}
                                type="number"
                                min={12}
                                max={24}
                                required
                                defaultValue={type.baseSizePx}
                                autoComplete="off"
                            />
                        )}
                    </FormField>
                    <FormField
                        name="ratio_preset"
                        label="Ratio"
                        error={errors.ratio_preset}
                    >
                        {(props) => (
                            <select
                                {...props}
                                className={selectClassName}
                                value={preset}
                                onChange={(event) =>
                                    setPreset(event.target.value)
                                }
                                autoComplete="off"
                            >
                                {options.ratios.map((ratio) => (
                                    <option
                                        key={ratio.value}
                                        value={ratio.value}
                                    >
                                        {ratio.label}
                                    </option>
                                ))}
                            </select>
                        )}
                    </FormField>
                    {preset === 'custom' && (
                        <FormField
                            name="ratio"
                            label="Custom ratio"
                            hint="Between 1.05 and 2, for example 1.15."
                            error={errors.ratio}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    inputMode="decimal"
                                    required
                                    defaultValue={type.ratio}
                                    autoComplete="off"
                                />
                            )}
                        </FormField>
                    )}
                    <div className="grid grid-cols-2 gap-3">
                        <FormField
                            name="steps_up"
                            label="Steps up"
                            error={errors.steps_up}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    type="number"
                                    min={1}
                                    max={8}
                                    required
                                    defaultValue={type.stepsUp}
                                    autoComplete="off"
                                />
                            )}
                        </FormField>
                        <FormField
                            name="steps_down"
                            label="Steps down"
                            error={errors.steps_down}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    type="number"
                                    min={0}
                                    max={3}
                                    required
                                    defaultValue={type.stepsDown}
                                    autoComplete="off"
                                />
                            )}
                        </FormField>
                    </div>
                    <Button disabled={processing}>Save type</Button>
                </>
            )}
        </Form>
    );
}

/**
 * Largest first. Fonts and sizes go through the style prop (CSP-safe).
 */
export function TypeSpecimen({ type }: { type: KitType }) {
    const steps = [...type.scale].reverse();
    const baseIndex = type.scale.findIndex((step) => step.name === 'base');

    return (
        <ol
            className="lc-card min-w-0 divide-y overflow-hidden"
            aria-label="Type specimen"
            data-tour="type-specimen"
        >
            {steps.map((step) => {
                const heading =
                    type.scale.indexOf(step) > baseIndex && step.name !== 'lg';

                return (
                    <li
                        key={step.name}
                        className="grid gap-1 p-4 sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-baseline"
                    >
                        <p className="lc-label text-muted-foreground">
                            {step.name} · {step.px}px · {step.rem}
                        </p>
                        <p
                            className="break-words"
                            style={{
                                fontFamily: heading
                                    ? type.headingStack
                                    : type.bodyStack,
                                fontSize: step.px,
                                lineHeight: 1.2,
                                fontWeight: heading ? 700 : 400,
                            }}
                        >
                            {heading
                                ? 'Fresh bread before 8'
                                : 'Order tonight, pick up warm tomorrow morning.'}
                        </p>
                    </li>
                );
            })}
        </ol>
    );
}
