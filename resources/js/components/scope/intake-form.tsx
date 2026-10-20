import { Form } from '@inertiajs/react';
import { Plus, Send, X } from 'lucide-react';
import { useRef, useState } from 'react';
import ClientScopeController from '@/actions/App/Http/Controllers/Client/ScopeController';
import {
    FormField,
    focusById,
    focusFirstError,
    selectClassName,
} from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Intake, IntakeOptions } from '@/types';

const textareaClassName =
    'w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs aria-invalid:border-destructive';

type PageRow = { key: number; value: string };

/**
 * The client's intake questionnaire (story 25): save a draft or send it.
 */
export function IntakeForm({
    intake,
    options,
}: {
    intake: Intake;
    options: IntakeOptions;
}) {
    // Which button sent the form; Inertia's <Form> doesn't pass the submitter.
    const submitting = useRef(false);
    const nextKey = useRef(intake.pages.length + 1);
    const [pages, setPages] = useState<PageRow[]>(() =>
        (intake.pages.length > 0 ? intake.pages : ['']).map((value, i) => ({
            key: i,
            value,
        })),
    );
    const today = localToday();

    function addPage() {
        const key = nextKey.current++;
        setPages((rows) => [...rows, { key, value: '' }]);
        focusById(`intake-page-${key}`);
    }

    function removePage(index: number) {
        const neighbour = pages[index - 1] ?? pages[index + 1];
        setPages((rows) => rows.filter((_, i) => i !== index));
        focusById(
            neighbour ? `intake-page-${neighbour.key}` : 'intake-add-page',
        );
    }

    return (
        <Form
            {...ClientScopeController.updateIntake.form(intake.id)}
            transform={(data) => ({ ...data, submit: submitting.current })}
            options={{ preserveScroll: true, preserveState: true }}
            onBefore={() =>
                !submitting.current ||
                window.confirm(
                    'Send your answers to the studio? You can’t change them afterwards unless the studio reopens the intake.',
                )
            }
            onError={focusFirstError}
            onSuccess={() =>
                focusById(submitting.current ? 'intake-status' : 'intake-save')
            }
            className="max-w-3xl space-y-6"
            data-tour="intake-form"
        >
            {({ processing, errors }) => (
                <>
                    <section className="lc-card lc-edge-ink space-y-4 p-5">
                        <h2 className="lc-label text-foreground">
                            <span
                                aria-hidden="true"
                                className="text-status-pass"
                            >
                                ●
                            </span>{' '}
                            01 · Goals
                        </h2>
                        <FormField
                            name="goals"
                            label="What should the new website do for you?"
                            hint="For example: more bookings, fewer phone calls, look more professional."
                            error={errors.goals}
                        >
                            {(props) => (
                                <textarea
                                    {...props}
                                    rows={5}
                                    maxLength={5000}
                                    className={textareaClassName}
                                    defaultValue={intake.goals ?? ''}
                                />
                            )}
                        </FormField>
                    </section>

                    <fieldset
                        className="lc-card lc-edge-ink space-y-4 p-5"
                        data-tour="intake-pages"
                        aria-describedby={
                            errors.pages ? 'intake-pages-error' : undefined
                        }
                    >
                        <legend className="sr-only">Pages</legend>
                        <p
                            aria-hidden="true"
                            className="lc-label text-foreground"
                        >
                            <span className="text-status-pass">●</span> 02 ·
                            Pages
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Which pages do you need? One per row, for example
                            “Home”, “Menu”, “Contact”.
                        </p>
                        <ol className="space-y-3">
                            {pages.map((row, i) => (
                                <li
                                    key={row.key}
                                    className="flex items-end gap-2"
                                >
                                    <div className="min-w-0 flex-1">
                                        <FormField
                                            name="pages[]"
                                            id={`intake-page-${row.key}`}
                                            label={`Page ${i + 1}`}
                                            error={errors[`pages.${i}`]}
                                        >
                                            {(props) => (
                                                <Input
                                                    {...props}
                                                    maxLength={80}
                                                    value={row.value}
                                                    {...(i === 0 &&
                                                        errors.pages && {
                                                            'aria-invalid': true,
                                                            'aria-describedby':
                                                                'intake-pages-error',
                                                        })}
                                                    onChange={(event) => {
                                                        const value =
                                                            event.target.value;
                                                        setPages((rows) =>
                                                            rows.map((r) =>
                                                                r.key ===
                                                                row.key
                                                                    ? {
                                                                          ...r,
                                                                          value,
                                                                      }
                                                                    : r,
                                                            ),
                                                        );
                                                    }}
                                                />
                                            )}
                                        </FormField>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Remove page ${i + 1}${row.value ? `, ${row.value}` : ''}`}
                                        disabled={pages.length === 1}
                                        onClick={() => removePage(i)}
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </li>
                            ))}
                        </ol>
                        {errors.pages && (
                            <p
                                id="intake-pages-error"
                                className="text-sm font-medium text-destructive"
                            >
                                {errors.pages}
                            </p>
                        )}
                        <Button
                            id="intake-add-page"
                            type="button"
                            variant="secondary"
                            disabled={pages.length >= 30}
                            onClick={addPage}
                        >
                            <Plus aria-hidden="true" />
                            Add a page
                        </Button>
                    </fieldset>

                    <fieldset className="lc-card lc-edge-ink space-y-4 p-5">
                        <legend className="sr-only">Features</legend>
                        <p
                            aria-hidden="true"
                            className="lc-label text-foreground"
                        >
                            <span className="text-status-pass">●</span> 03 ·
                            Features
                        </p>
                        <p className="text-sm text-muted-foreground">
                            Tick everything the site should be able to do.
                        </p>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {options.features.map((feature) => {
                                const id = `intake-feature-${feature.value}`;

                                return (
                                    <div
                                        key={feature.value}
                                        className="flex items-center gap-3"
                                    >
                                        <input
                                            id={id}
                                            type="checkbox"
                                            name="features[]"
                                            value={feature.value}
                                            className="size-5 shrink-0 accent-primary"
                                            defaultChecked={intake.features.includes(
                                                feature.value,
                                            )}
                                        />
                                        <label htmlFor={id}>
                                            {feature.label}
                                        </label>
                                    </div>
                                );
                            })}
                        </div>
                        <FormField
                            name="features_other"
                            id="intake-features-other"
                            label="Anything else?"
                            hint="Optional."
                            error={errors.features_other ?? errors.features}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    maxLength={200}
                                    defaultValue={intake.featuresOther ?? ''}
                                />
                            )}
                        </FormField>
                    </fieldset>

                    <section className="lc-card lc-edge-ink grid gap-5 p-5 md:grid-cols-2">
                        <h2 className="lc-label text-foreground md:col-span-2">
                            <span
                                aria-hidden="true"
                                className="text-status-pass"
                            >
                                ●
                            </span>{' '}
                            04 · Budget and deadline
                        </h2>
                        <FormField
                            name="budget"
                            label="Budget"
                            hint="A rough range is enough."
                            error={errors.budget}
                        >
                            {(props) => (
                                <select
                                    {...props}
                                    className={selectClassName}
                                    defaultValue={intake.budget ?? ''}
                                >
                                    <option value="">Choose a range</option>
                                    {options.budgets.map((budget) => (
                                        <option
                                            key={budget.value}
                                            value={budget.value}
                                        >
                                            {budget.label}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </FormField>
                        <FormField
                            name="deadline_on"
                            label="Deadline"
                            hint="Optional."
                            error={errors.deadline_on}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    type="date"
                                    min={today}
                                    className="w-48 max-w-full"
                                    defaultValue={intake.deadlineOn ?? ''}
                                />
                            )}
                        </FormField>
                        <div className="md:col-span-2">
                            <FormField
                                name="deadline_reason"
                                label="Why that date?"
                                hint="Optional. For example: “Before Christmas”."
                                error={errors.deadline_reason}
                            >
                                {(props) => (
                                    <Input
                                        {...props}
                                        maxLength={120}
                                        defaultValue={
                                            intake.deadlineReason ?? ''
                                        }
                                    />
                                )}
                            </FormField>
                        </div>
                    </section>

                    {/* Save draft comes first: Enter in a field "clicks" it. */}
                    <div className="flex flex-wrap items-center gap-3">
                        <Button
                            id="intake-save"
                            variant="secondary"
                            disabled={processing}
                            onClick={() => (submitting.current = false)}
                        >
                            Save draft
                        </Button>
                        <button
                            className="lc-pill"
                            disabled={processing}
                            onClick={() => (submitting.current = true)}
                            data-tour="send-intake"
                        >
                            <Send aria-hidden="true" className="size-5" />
                            Send to the studio
                        </button>
                    </div>
                </>
            )}
        </Form>
    );
}

/** Today as YYYY-MM-DD in the visitor's own time zone. */
function localToday(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}
