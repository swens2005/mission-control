import { Form, Head, Link } from '@inertiajs/react';
import { ImagePlus, Send } from 'lucide-react';
import { useState } from 'react';
import DesignController from '@/actions/App/Http/Controllers/Admin/DesignController';
import ProofmarkController from '@/actions/App/Http/Controllers/Admin/ProofmarkController';
import { FormField, focusById, focusFirstError } from '@/components/form-field';
import { PageHeader } from '@/components/page-header';
import { DesignCard } from '@/components/proofmark/design-card';
import { RoundList } from '@/components/proofmark/round-list';
import { DesignViewer } from '@/components/proofmark/design-viewer';
import { RoundHeader } from '@/components/proofmark/round-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useBreadcrumbs } from '@/hooks/use-breadcrumbs';
import { formatDateTime } from '@/lib/format';
import { index, show } from '@/routes/admin/projects';
import type { Design, Project, ReviewRound, ReviewRoundSummary } from '@/types';

type Props = {
    project: Project;
    rounds: ReviewRoundSummary[];
    round: ReviewRound | null;
    canStartRound: boolean;
    quota: { used: string; limit: string } | null;
};

export default function ShowProofmark({
    project,
    rounds,
    round,
    canStartRound,
    quota,
}: Props) {
    useBreadcrumbs([
        { title: 'Projects', href: index() },
        { title: project.name, href: show(project.id) },
        { title: 'Proofmark', href: ProofmarkController.show(project.id) },
    ]);

    const nextNumber = (rounds[0]?.number ?? 0) + 1;

    const startRound = canStartRound && (
        <Form
            {...ProofmarkController.storeRound.form(project.id)}
            options={{ preserveScroll: true }}
            // The button is gone now; land on the new round.
            onSuccess={() => focusById('round-heading')}
        >
            {({ processing }) => (
                <button
                    className="lc-pill"
                    disabled={processing}
                    data-tour="new-round"
                >
                    <ImagePlus aria-hidden="true" className="size-5" />
                    Start round v{nextNumber}
                </button>
            )}
        </Form>
    );

    return (
        <>
            <Head title={`Proofmark: ${project.name}`} />
            <div className="space-y-8 p-4 md:p-6">
                <PageHeader
                    title="Proofmark"
                    description={
                        <>
                            Design review for{' '}
                            <Link
                                href={show(project.id)}
                                className="text-primary underline"
                            >
                                {project.name}
                            </Link>
                        </>
                    }
                    actions={rounds.length > 0 ? startRound : undefined}
                />

                {round === null ? (
                    <section className="lc-card lc-edge-ink max-w-xl space-y-4 p-6">
                        <p className="lc-label text-muted-foreground">
                            Light table · empty
                        </p>
                        <h2 className="text-xl font-bold">
                            No design rounds yet
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Start round v1, upload the screens, then send them
                            to the client to pin their comments.
                        </p>
                        {startRound}
                    </section>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
                        <RoundList
                            rounds={rounds}
                            currentId={round.id}
                            href={(r) =>
                                ProofmarkController.show(project.id, {
                                    query: { round: r.number },
                                })
                            }
                        />
                        <RoundDetail round={round} quota={quota} />
                    </div>
                )}
            </div>
        </>
    );
}

function RoundDetail({
    round,
    quota,
}: {
    round: ReviewRound;
    quota: Props['quota'];
}) {
    const draft = round.status === 'draft';

    return (
        <section aria-labelledby="round-heading" className="min-w-0 space-y-6">
            <RoundHeader round={round} description={roundDescription(round)}>
                {draft && round.designs.length > 0 && (
                    <SendForm round={round} />
                )}
            </RoundHeader>

            {draft ? (
                <>
                    <UploadForm round={round} quota={quota} />
                    <h3
                        id="designs-heading"
                        tabIndex={-1}
                        className="sr-only focus:not-sr-only"
                    >
                        Designs in {round.label}
                    </h3>
                    {round.designs.length === 0 ? (
                        <p className="rounded-2xl border border-dashed border-input bg-card px-6 py-10 text-center text-muted-foreground">
                            No designs in this round yet.
                        </p>
                    ) : (
                        <ol
                            aria-labelledby="designs-heading"
                            className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            {round.designs.map((design, i) => (
                                <DesignCard
                                    key={design.id}
                                    design={design}
                                    index={i}
                                >
                                    <DraftActions
                                        design={design}
                                        isFirst={i === 0}
                                        isLast={i === round.designs.length - 1}
                                    />
                                </DesignCard>
                            ))}
                        </ol>
                    )}
                </>
            ) : (
                <DesignViewer
                    key={round.id}
                    designs={round.designs}
                    roundLabel={round.label}
                />
            )}
        </section>
    );
}

function roundDescription(round: ReviewRound): string {
    switch (round.status) {
        case 'draft':
            return 'A draft: only the studio can see it.';
        case 'in_review':
            return `With the client since ${formatDateTime(round.sentAt)}. Its designs can no longer change.`;
        case 'approved':
            return `Approved by ${round.approvedByName ?? 'the client'} on ${formatDateTime(round.approvedAt)}.`;
        default:
            return 'Replaced by a newer round. Kept for the record.';
    }
}

/**
 * Sends the draft to the client, after a confirmation, because the
 * designs are frozen from then on.
 */
function SendForm({ round }: { round: ReviewRound }) {
    return (
        <Form
            {...ProofmarkController.send.form(round.id)}
            options={{ preserveScroll: true }}
            onBefore={() =>
                window.confirm(
                    `Send ${round.label} to the client? Its designs can't change after this.`,
                )
            }
            onSuccess={() => focusById('round-heading')}
            className="space-y-2"
        >
            {({ processing, errors }) => (
                <>
                    <button
                        className="lc-pill"
                        disabled={processing}
                        data-tour="send-round"
                    >
                        <Send aria-hidden="true" className="size-5" />
                        Send {round.label} to the client
                    </button>
                    {errors.send && (
                        <p
                            role="alert"
                            className="text-sm font-medium text-destructive"
                        >
                            {errors.send}
                        </p>
                    )}
                </>
            )}
        </Form>
    );
}

function UploadForm({
    round,
    quota,
}: {
    round: ReviewRound;
    quota: Props['quota'];
}) {
    return (
        <Form
            {...DesignController.store.form(round.id)}
            onError={focusFirstError}
            options={{ preserveScroll: true }}
            resetOnSuccess
            className="lc-card lc-edge-pass grid gap-4 p-5 md:grid-cols-2 md:items-start"
            data-tour="upload-design"
        >
            {({ processing, errors }) => (
                <>
                    <FormField
                        name="title"
                        label="Design title"
                        hint="For example: Home, desktop"
                        error={errors.title}
                    >
                        {(props) => (
                            <Input
                                {...props}
                                required
                                maxLength={120}
                                autoComplete="off"
                            />
                        )}
                    </FormField>
                    <FormField
                        name="image"
                        label="Image"
                        hint={`PNG, JPG or WebP, up to 8 MB. Hidden data such as GPS location is removed.${quota ? ` Demo space used: ${quota.used} of ${quota.limit}.` : ''}`}
                        error={errors.image}
                    >
                        {(props) => (
                            <Input
                                {...props}
                                type="file"
                                required
                                accept="image/png,image/jpeg,image/webp"
                                autoComplete="off"
                                className="h-auto py-1.5"
                            />
                        )}
                    </FormField>
                    <div className="md:col-span-2">
                        <Button disabled={processing}>
                            {processing ? 'Uploading…' : 'Add design'}
                        </Button>
                        <p role="status" className="sr-only">
                            {processing ? 'Uploading the design.' : ''}
                        </p>
                    </div>
                </>
            )}
        </Form>
    );
}

/**
 * Rename, reorder and remove. Keeps keyboard focus on the control that was
 * used, because reordering moves the card and Inertia re-renders the list.
 */
function DraftActions({
    design,
    isFirst,
    isLast,
}: {
    design: Design;
    isFirst: boolean;
    isLast: boolean;
}) {
    const [renaming, setRenaming] = useState(false);

    if (renaming) {
        return (
            <Form
                {...DesignController.update.form(design.id)}
                errorBag={`design-${design.id}`}
                options={{ preserveScroll: true, preserveState: true }}
                onSuccess={() => {
                    setRenaming(false);
                    focusById(`rename-${design.id}`);
                }}
                onError={focusFirstError}
                className="space-y-2"
            >
                {({ processing, errors }) => (
                    <>
                        <FormField
                            name="title"
                            id={`design-title-${design.id}`}
                            label={`New title for ${design.title}`}
                            error={errors.title}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    required
                                    maxLength={120}
                                    defaultValue={design.title}
                                    autoComplete="off"
                                    autoFocus
                                />
                            )}
                        </FormField>
                        <div className="flex gap-2">
                            <Button size="sm" disabled={processing}>
                                Save
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => {
                                    setRenaming(false);
                                    focusById(`rename-${design.id}`);
                                }}
                            >
                                Cancel
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        );
    }

    return (
        <div className="flex flex-wrap gap-1">
            <Button
                id={`rename-${design.id}`}
                size="sm"
                variant="ghost"
                aria-label={`Rename ${design.title}`}
                onClick={() => setRenaming(true)}
            >
                Rename
            </Button>
            {(['up', 'down'] as const).map((direction) => {
                const id = `move-${direction}-${design.id}`;
                const disabled = direction === 'up' ? isFirst : isLast;

                return (
                    <Form
                        key={direction}
                        {...DesignController.move.form(design.id)}
                        transform={(data) => ({ ...data, direction })}
                        options={{ preserveScroll: true, preserveState: true }}
                        // At the end of the list this button is now disabled;
                        // then focus the other direction instead.
                        onSuccess={() =>
                            focusById(
                                id,
                                `move-${direction === 'up' ? 'down' : 'up'}-${design.id}`,
                            )
                        }
                    >
                        <Button
                            id={id}
                            size="sm"
                            variant="ghost"
                            disabled={disabled}
                            aria-label={`Move ${design.title} ${direction}`}
                        >
                            {direction === 'up' ? 'Move up' : 'Move down'}
                        </Button>
                    </Form>
                );
            })}
            <Form
                {...DesignController.destroy.form(design.id)}
                options={{ preserveScroll: true, preserveState: true }}
                onBefore={() =>
                    window.confirm(`Remove ${design.title} from this round?`)
                }
                onSuccess={() => focusById('designs-heading')}
            >
                <Button
                    size="sm"
                    variant="ghost"
                    aria-label={`Remove ${design.title}`}
                >
                    Remove
                </Button>
            </Form>
        </div>
    );
}
