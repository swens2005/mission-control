import { useState } from 'react';
import { FormField, selectClassName } from '@/components/form-field';
import { Input } from '@/components/ui/input';
import type { BrandColor, RoleOption } from '@/types';

/**
 * Name, role and value for a color, shared by "Add color" and "Edit".
 * The small preview uses the typed value as a CSS color through the style
 * prop (CSP-safe); an invalid value just shows no color.
 */
export function ColorFields({
    idPrefix,
    roles,
    errors,
    color,
}: {
    idPrefix: string;
    roles: RoleOption[];
    errors: Partial<Record<'name' | 'role' | 'value', string>>;
    color?: BrandColor;
}) {
    const [value, setValue] = useState(color?.hex ?? '');
    const [role, setRole] = useState(color?.role ?? 'text');
    const roleHint = roles.find((option) => option.value === role)?.hint;

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
                        maxLength={60}
                        defaultValue={color?.name}
                        autoComplete="off"
                    />
                )}
            </FormField>
            <FormField
                name="role"
                id={`${idPrefix}-role`}
                label="Role"
                hint={roleHint}
                error={errors.role}
            >
                {(props) => (
                    <select
                        {...props}
                        className={selectClassName}
                        value={role}
                        onChange={(event) =>
                            setRole(event.target.value as typeof role)
                        }
                        autoComplete="off"
                    >
                        {roles.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                )}
            </FormField>
            <FormField
                name="value"
                id={`${idPrefix}-value`}
                label="Value"
                hint="Hex like #10233a, or oklch(0.27 0.05 255)."
                error={errors.value}
            >
                {(props) => (
                    <div className="flex items-center gap-2">
                        <span
                            aria-hidden="true"
                            className="size-9 shrink-0 rounded-md border border-input"
                            style={{
                                // The CSSOM ignores invalid colors and would
                                // keep the previous one, so check first.
                                background: CSS.supports('color', value)
                                    ? value
                                    : 'transparent',
                            }}
                        />
                        <Input
                            {...props}
                            required
                            maxLength={60}
                            value={value}
                            onChange={(event) => setValue(event.target.value)}
                            className="font-mono"
                            autoComplete="off"
                            spellCheck={false}
                            data-tour="color-value"
                        />
                    </div>
                )}
            </FormField>
        </>
    );
}
