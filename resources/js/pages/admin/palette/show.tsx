import { Form, Head, Link } from '@inertiajs/react';
import { Palette } from 'lucide-react';
import { useState } from 'react';
import ColorController from '@/actions/App/Http/Controllers/Admin/ColorController';
import PaletteController from '@/actions/App/Http/Controllers/Admin/PaletteController';
import { focusById, focusFirstError } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { ColorFields } from '@/components/palette/color-fields';
import { ContrastMatrix } from '@/components/palette/contrast-matrix';
import { ExportSection } from '@/components/palette/export-section';
import { SharingCard } from '@/components/palette/sharing-card';
import { TypeSection } from '@/components/palette/type-section';
import { Button } from '@/components/ui/button';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { index, show } from '@/routes/admin/projects';
import type {
    BrandColor,
    BrandKit,
    Project,
    RoleOption,
    TokenExport,
    TypeOptions,
} from '@/types';

type Props = {
    project: Project;
    kit: BrandKit | null;
    roles: RoleOption[];
    typeOptions: TypeOptions;
    exports: TokenExport[];
};

export default function ShowPalette({
    project,
    kit,
    roles,
    typeOptions,
    exports,
}: Props) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Palette Lab', href: PaletteController.show(project.id) },
    ]);

    return (
        <>
            <Head title={`Palette Lab: ${project.name}`} />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title="Palette Lab"
                    description={
                        <>
                            Brand kit for{' '}
                            <Link
                                href={show(project.id)}
                                className="text-primary underline"
                            >
                                {project.name}
                            </Link>
                        </>
                    }
                />

                {kit === null ? (
                    <section className="lc-card lc-edge-ink max-w-xl space-y-4 p-6">
                        <p className="lc-label text-muted-foreground">
                            Brand kit · empty
                        </p>
                        <h2 className="text-xl font-bold">No brand kit yet</h2>
                        <p className="text-sm text-muted-foreground">
                            Define the colors and type, check every pair for
                            contrast, then share the style guide with the
                            client.
                        </p>
                        <Form
                            {...PaletteController.store.form(project.id)}
                            onSuccess={() => focusById('colors-heading')}
                        >
                            {({ processing }) => (
                                <button
                                    className="lc-pill"
                                    disabled={processing}
                                >
                                    <Palette
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                    Start the brand kit
                                </button>
                            )}
                        </Form>
                    </section>
                ) : (
                    <div className="space-y-12">
                        <SharingCard kitId={kit.id} sharing={kit.sharing} />
                        <Colors kit={kit} roles={roles} />
                        <ContrastMatrix
                            matrix={kit.matrix}
                            canFix={!kit.locked}
                        />
                        <TypeSection
                            kitId={kit.id}
                            type={kit.type}
                            options={typeOptions}
                            editable={!kit.locked}
                        />
                        <ExportSection kitId={kit.id} exports={exports} />
                    </div>
                )}
            </div>
        </>
    );
}

function Colors({ kit, roles }: { kit: BrandKit; roles: RoleOption[] }) {
    // The fields keep their own state; a new key clears them after adding.
    const [formKey, setFormKey] = useState(0);

    return (
        <section aria-labelledby="colors-heading" className="space-y-5">
            <div>
                <p className="lc-label text-muted-foreground">
                    Brand kit · colors
                </p>
                <h2
                    id="colors-heading"
                    tabIndex={-1}
                    className="text-2xl font-extrabold"
                >
                    Colors
                </h2>
                {kit.locked && (
                    <p className="text-sm text-muted-foreground">
                        Approved by the client, so the colors are locked.
                    </p>
                )}
            </div>

            {kit.colors.length === 0 ? (
                <p className="rounded-2xl border border-dashed border-input bg-card px-6 py-10 text-center text-muted-foreground">
                    No colors yet. Add the first one below.
                </p>
            ) : (
                <ol
                    aria-labelledby="colors-heading"
                    className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4"
                    data-tour="color-list"
                >
                    {kit.colors.map((color, i) => (
                        <ColorCard
                            key={color.id}
                            color={color}
                            index={i}
                            roles={roles}
                            editable={!kit.locked}
                            isFirst={i === 0}
                            isLast={i === kit.colors.length - 1}
                        />
                    ))}
                </ol>
            )}

            {!kit.locked && (
                <Form
                    {...ColorController.store.form(kit.id)}
                    onError={focusFirstError}
                    options={{ preserveScroll: true }}
                    onSuccess={() => {
                        setFormKey((key) => key + 1);
                        focusById('new-color-name');
                    }}
                    className="lc-card lc-edge-pass grid gap-4 p-5 md:grid-cols-3 md:items-start"
                    data-tour="add-color"
                >
                    {({ processing, errors }) => (
                        <>
                            <h3 className="lc-label text-muted-foreground md:col-span-3">
                                Add a color
                            </h3>
                            <ColorFields
                                key={formKey}
                                idPrefix="new-color"
                                roles={roles}
                                errors={errors}
                            />
                            <div className="md:col-span-3">
                                <Button disabled={processing}>Add color</Button>
                            </div>
                        </>
                    )}
                </Form>
            )}
        </section>
    );
}

function ColorCard({
    color,
    index,
    roles,
    editable,
    isFirst,
    isLast,
}: {
    color: BrandColor;
    index: number;
    roles: RoleOption[];
    editable: boolean;
    isFirst: boolean;
    isLast: boolean;
}) {
    const [editing, setEditing] = useState(false);
    const number = String(index + 1).padStart(2, '0');

    return (
        <li className="lc-card lc-edge-ink flex flex-col overflow-hidden">
            <div
                aria-hidden="true"
                className="h-28 border-b"
                style={{ backgroundColor: color.hex }}
            />
            <div className="flex flex-1 flex-col gap-1 p-4">
                <h3 className="lc-label text-foreground">
                    <span aria-hidden="true" className="text-status-pass">
                        ●
                    </span>{' '}
                    {number} · {color.name} · {color.roleLabel}
                </h3>
                <p className="font-mono text-sm">{color.hex}</p>
                <p className="font-mono text-xs break-all text-muted-foreground">
                    {color.oklch}
                </p>
                {color.gamutAdjusted && (
                    <p className="text-xs text-muted-foreground">
                        Adjusted to fit the screen: the typed OKLCH was more
                        vivid than sRGB can show.
                    </p>
                )}

                {editable &&
                    (editing ? (
                        <Form
                            {...ColorController.update.form(color.id)}
                            errorBag={`color-${color.id}`}
                            options={{
                                preserveScroll: true,
                                preserveState: true,
                            }}
                            onSuccess={() => {
                                setEditing(false);
                                focusById(`edit-color-${color.id}`);
                            }}
                            onError={focusFirstError}
                            className="mt-3 space-y-3"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <ColorFields
                                        idPrefix={`color-${color.id}`}
                                        roles={roles}
                                        errors={errors}
                                        color={color}
                                    />
                                    <div className="flex gap-2">
                                        <Button size="sm" disabled={processing}>
                                            Save
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => {
                                                setEditing(false);
                                                focusById(
                                                    `edit-color-${color.id}`,
                                                );
                                            }}
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    ) : (
                        <div className="mt-auto flex flex-wrap gap-1 pt-3">
                            <Button
                                id={`edit-color-${color.id}`}
                                size="sm"
                                variant="ghost"
                                aria-label={`Edit ${color.name}`}
                                onClick={() => {
                                    setEditing(true);
                                    focusById(`color-${color.id}-name`);
                                }}
                            >
                                Edit
                            </Button>
                            {(['up', 'down'] as const).map((direction) => {
                                const id = `move-${direction}-color-${color.id}`;

                                return (
                                    <Form
                                        key={direction}
                                        {...ColorController.move.form(color.id)}
                                        transform={(data) => ({
                                            ...data,
                                            direction,
                                        })}
                                        options={{
                                            preserveScroll: true,
                                            preserveState: true,
                                        }}
                                        onSuccess={() =>
                                            focusById(
                                                id,
                                                `move-${direction === 'up' ? 'down' : 'up'}-color-${color.id}`,
                                            )
                                        }
                                    >
                                        <Button
                                            id={id}
                                            size="sm"
                                            variant="ghost"
                                            disabled={
                                                direction === 'up'
                                                    ? isFirst
                                                    : isLast
                                            }
                                            aria-label={`Move ${color.name} ${direction === 'up' ? 'earlier' : 'later'}`}
                                        >
                                            {direction === 'up'
                                                ? 'Earlier'
                                                : 'Later'}
                                        </Button>
                                    </Form>
                                );
                            })}
                            <Form
                                {...ColorController.destroy.form(color.id)}
                                options={{
                                    preserveScroll: true,
                                    preserveState: true,
                                }}
                                onBefore={() =>
                                    window.confirm(`Remove ${color.name}?`)
                                }
                                onSuccess={() => focusById('colors-heading')}
                            >
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    aria-label={`Remove ${color.name}`}
                                >
                                    Remove
                                </Button>
                            </Form>
                        </div>
                    ))}
            </div>
        </li>
    );
}
