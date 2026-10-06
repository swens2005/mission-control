import { Form } from '@inertiajs/react';
import { Wand2 } from 'lucide-react';
import { useState } from 'react';
import ColorController from '@/actions/App/Http/Controllers/Admin/ColorController';
import { focusById } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { ContrastCell, ContrastMatrix as Matrix } from '@/types';

const toneClass = {
    pass: 'text-status-pass border-status-pass',
    warn: 'text-status-warn border-status-warn',
    fail: 'text-status-fail border-status-fail',
};

type Selected = {
    row: Matrix['rows'][number];
    column: Matrix['columns'][number];
    cell: ContrastCell;
};

/**
 * Every foreground on every surface (story 21). Grades are text; the "Aa"
 * sample in each cell shows the pair itself (decoration, aria-hidden).
 */
export function ContrastMatrix({
    matrix,
    canFix,
}: {
    matrix: Matrix;
    canFix: boolean;
}) {
    const [selected, setSelected] = useState<Selected | null>(null);
    const fixButtonId = (s: Selected) => `fix-${s.row.id}-${s.column.id}`;

    return (
        <section
            aria-labelledby="matrix-heading"
            className="space-y-4"
            data-tour="contrast-matrix"
        >
            <div>
                <p className="lc-label text-muted-foreground">
                    Brand kit · contrast
                </p>
                <h2
                    id="matrix-heading"
                    tabIndex={-1}
                    className="text-2xl font-extrabold"
                >
                    Contrast matrix
                </h2>
                <p className="text-sm text-muted-foreground">
                    {matrix.columns.length === 0
                        ? 'Add a surface color to check contrast.'
                        : matrix.failing === 0
                          ? 'Every text and accent color passes AA on every surface.'
                          : `${matrix.failing} text ${matrix.failing === 1 ? 'pair needs' : 'pairs need'} a fix for AA. Shapes-only colors are judged as graphics (3:1).`}
                </p>
            </div>

            {matrix.columns.length > 0 && matrix.rows.length > 0 && (
                <div
                    role="region"
                    aria-labelledby="matrix-heading"
                    tabIndex={0}
                    className="lc-card overflow-x-auto focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    <table className="w-full min-w-max border-collapse text-sm">
                        <caption className="sr-only">
                            Contrast ratio and WCAG grade of each color on each
                            surface
                        </caption>
                        <thead>
                            <tr>
                                <td className="p-3" />
                                {matrix.columns.map((column) => (
                                    <th
                                        key={column.id}
                                        scope="col"
                                        className="p-3 text-left align-bottom"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="mb-2 block h-3 w-16 rounded-full border"
                                            style={{
                                                backgroundColor: column.hex,
                                            }}
                                        />
                                        <span className="lc-label">
                                            On {column.name}
                                        </span>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {matrix.rows.map((row) => (
                                <tr key={row.id} className="border-t">
                                    <th
                                        scope="row"
                                        className="p-3 text-left align-top font-normal"
                                    >
                                        <span className="flex items-center gap-2">
                                            <span
                                                aria-hidden="true"
                                                className="size-4 shrink-0 rounded-full border"
                                                style={{
                                                    backgroundColor: row.hex,
                                                }}
                                            />
                                            <span className="font-semibold">
                                                {row.name}
                                            </span>
                                        </span>
                                        <span className="mt-1 block text-xs text-muted-foreground">
                                            {row.roleLabel}
                                        </span>
                                    </th>
                                    {row.cells.map((cell, i) => {
                                        const column = matrix.columns[i];
                                        const here = { row, column, cell };

                                        return (
                                            <td
                                                key={cell.surfaceId}
                                                className="p-3 align-top"
                                                data-tour="contrast-cell"
                                            >
                                                <div
                                                    aria-hidden="true"
                                                    className="mb-2 grid h-12 w-28 place-items-center rounded-lg border font-display text-xl font-bold"
                                                    style={{
                                                        backgroundColor:
                                                            column.hex,
                                                        color: row.hex,
                                                    }}
                                                >
                                                    {row.role === 'shape' ? (
                                                        <span
                                                            className="size-5 rounded-full"
                                                            style={{
                                                                backgroundColor:
                                                                    row.hex,
                                                            }}
                                                        />
                                                    ) : (
                                                        'Aa'
                                                    )}
                                                </div>
                                                <p className="font-mono">
                                                    {cell.ratio}
                                                </p>
                                                <p
                                                    className={cn(
                                                        'mt-1 inline-block rounded-md border px-1.5 py-0.5 text-xs font-bold',
                                                        toneClass[cell.tone],
                                                    )}
                                                >
                                                    {cell.gradeLabel}
                                                </p>
                                                {canFix && cell.fix && (
                                                    <div className="mt-2">
                                                        <Button
                                                            id={fixButtonId(
                                                                here,
                                                            )}
                                                            size="sm"
                                                            variant="secondary"
                                                            aria-label={`Fix ${row.name} on ${column.name}`}
                                                            aria-expanded={
                                                                selected !==
                                                                    null &&
                                                                fixButtonId(
                                                                    selected,
                                                                ) ===
                                                                    fixButtonId(
                                                                        here,
                                                                    )
                                                            }
                                                            aria-controls="fix-panel"
                                                            onClick={() => {
                                                                setSelected(
                                                                    here,
                                                                );
                                                                focusById(
                                                                    'fix-heading',
                                                                );
                                                            }}
                                                            data-tour="fix-it"
                                                        >
                                                            <Wand2
                                                                aria-hidden="true"
                                                                className="size-4"
                                                            />
                                                            Fix it
                                                        </Button>
                                                    </div>
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {selected?.cell.fix && (
                <FixPanel
                    selected={selected}
                    onClose={(applied) => {
                        const back = fixButtonId(selected);
                        setSelected(null);
                        focusById(applied ? 'matrix-heading' : back);
                    }}
                />
            )}
        </section>
    );
}

function FixPanel({
    selected,
    onClose,
}: {
    selected: Selected;
    onClose: (applied: boolean) => void;
}) {
    const { row, column, cell } = selected;
    const fix = cell.fix;

    if (!fix) {
        return null;
    }

    return (
        <div
            id="fix-panel"
            className="lc-card lc-edge-warn max-w-2xl space-y-4 p-5"
        >
            <h3 id="fix-heading" tabIndex={-1} className="text-lg font-bold">
                Fix {row.name} on {column.name}
            </h3>
            <p className="text-sm text-muted-foreground">
                The nearest lightness that reaches AA (4.5:1), with the same
                hue. This changes {row.name} everywhere in the kit, so check its
                other pairs afterwards.
            </p>
            <div className="grid gap-4 sm:grid-cols-2">
                {[
                    ['Now', row.hex, cell.ratio, cell.gradeLabel],
                    ['After', fix.hex, fix.ratio, 'AA'],
                ].map(([label, hex, ratio, grade]) => (
                    <div key={label} className="space-y-2">
                        <p className="lc-label text-muted-foreground">
                            {label}
                        </p>
                        <div
                            aria-hidden="true"
                            className="grid h-16 place-items-center rounded-lg border font-display text-2xl font-bold"
                            style={{ backgroundColor: column.hex, color: hex }}
                        >
                            Aa
                        </div>
                        <p className="font-mono text-sm">
                            {hex} · {ratio} · {grade}
                        </p>
                    </div>
                ))}
            </div>
            <p className="font-mono text-xs text-muted-foreground">
                {fix.oklch}
            </p>
            <Form
                {...ColorController.fix.form(row.id)}
                transform={(data) => ({ ...data, surface: column.id })}
                options={{ preserveScroll: true }}
                onSuccess={() => onClose(true)}
                className="flex flex-wrap gap-2"
            >
                {({ processing, errors }) => (
                    <>
                        <Button disabled={processing}>Use {fix.hex}</Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => onClose(false)}
                        >
                            Cancel
                        </Button>
                        {errors.fix && (
                            <p
                                role="alert"
                                className="w-full text-sm font-medium text-destructive"
                            >
                                {errors.fix}
                            </p>
                        )}
                    </>
                )}
            </Form>
        </div>
    );
}
