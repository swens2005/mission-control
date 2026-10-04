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
    label,
    error,
    hint,
    children,
}: {
    name: string;
    label: string;
    error?: string;
    hint?: string;
    children: (props: FieldControlProps) => ReactNode;
}) {
    const id = `field-${name}`;
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
    requestAnimationFrame(() => {
        document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus();
    });
}

/** Native select styled like the other inputs. */
export const selectClassName =
    'h-9 w-full rounded-md border border-input bg-card px-3 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:border-destructive';
