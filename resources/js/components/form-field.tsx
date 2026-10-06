import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';

export type FieldControlProps = {
    id: string;
    name: string;
    'aria-invalid': boolean | undefined;
    'aria-describedby': string | undefined;
};

/**
 * A labelled form control whose hint and error are linked to it with
 * aria-describedby, so screen readers read them with the field.
 */
export function FormField({
    name,
    id: idOverride,
    label,
    error,
    hint,
    children,
}: {
    name: string;
    /** Needed when the same field name appears more than once on a page. */
    id?: string;
    label: string;
    error?: string;
    hint?: string;
    children: (props: FieldControlProps) => ReactNode;
}) {
    const id = idOverride ?? `field-${name}`;
    const describedBy =
        [hint && `${id}-hint`, error && `${id}-error`]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {hint && (
                <p id={`${id}-hint`} className="text-sm text-muted-foreground">
                    {hint}
                </p>
            )}
            {children({
                id,
                name,
                'aria-invalid': error ? true : undefined,
                'aria-describedby': describedBy,
            })}
            {error && (
                <p
                    id={`${id}-error`}
                    className="text-sm font-medium text-destructive"
                >
                    {error}
                </p>
            )}
        </div>
    );
}

/**
 * After a failed submit, move focus to the first invalid field so keyboard
 * and screen reader users land on the problem.
 */
export function focusFirstError(): void {
    afterRender(() =>
        document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus(),
    );
}

/**
 * Moves focus to an element by id once Inertia's visit has re-rendered the
 * page. Use after an action removes or moves the control that had focus.
 * The fallback is used when the element is gone or disabled.
 */
export function focusById(id: string, fallbackId?: string): void {
    afterRender(() => {
        const element = document.getElementById(id);
        const usable =
            element !== null &&
            !(element instanceof HTMLButtonElement && element.disabled);

        (usable || !fallbackId
            ? element
            : document.getElementById(fallbackId)
        )?.focus();
    });
}

/**
 * Two frames: React may commit Inertia's new page props after the first
 * one (seen with uploads and renames), and focus set earlier is lost.
 */
function afterRender(callback: () => void): void {
    requestAnimationFrame(() => requestAnimationFrame(callback));
}

/** Native select styled like the other inputs. */
export const selectClassName =
    'h-9 w-full rounded-md border border-input bg-card px-3 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:border-destructive';
