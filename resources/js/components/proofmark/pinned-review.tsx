import { Form } from '@inertiajs/react';
import { MessageSquarePlus } from 'lucide-react';
import type { KeyboardEvent, MouseEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import CommentResolutionController from '@/actions/App/Http/Controllers/Admin/CommentResolutionController';
import CommentController from '@/actions/App/Http/Controllers/CommentController';
import { FormField, focusById, focusFirstError } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import type { PinPoint } from '@/lib/pin-math';
import {
    describePin,
    moveCrosshair,
    pinFromPixels,
    pinToCss,
} from '@/lib/pin-math';
import { cn } from '@/lib/utils';
import type { Design, DesignComment, ReviewRound } from '@/types';
import { DesignViewer } from './design-viewer';

const textareaClassName =
    'w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:border-destructive';

/**
 * The design viewer with pinned comments (story 17).
 *
 * "Add comment" mode shows a crosshair over the design: click a spot, or
 * move it with the arrow keys (Shift for bigger steps) and press Enter.
 * Every pin is also in the numbered list next to the design, the
 * accessible equivalent; selecting either one highlights the other.
 */
type Filter = 'all' | 'open' | 'resolved';

export function PinnedReview({
    round,
    studio = false,
}: {
    round: ReviewRound;
    /** The studio can resolve and reopen comments (story 18). */
    studio?: boolean;
}) {
    const [adding, setAdding] = useState(false);
    const [filter, setFilter] = useState<Filter>('all');
    const [crosshair, setCrosshair] = useState<PinPoint>({ x: 5000, y: 5000 });
    const [draft, setDraft] = useState<PinPoint | null>(null);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const layerRef = useRef<HTMLDivElement>(null);
    const ringRef = useRef<HTMLDivElement>(null);

    // Entering the mode: start in the middle of the part of the design
    // that's on screen, so the crosshair is visible on a long page.
    useEffect(() => {
        const layer = layerRef.current;

        if (!adding || draft || !layer) {
            return;
        }

        const box = layer.getBoundingClientRect();
        const view = layer.closest('[role="region"]')?.getBoundingClientRect();
        const top = Math.max(box.top, view?.top ?? 0, 0);
        const bottom = Math.min(
            box.bottom,
            view?.bottom ?? window.innerHeight,
            window.innerHeight,
        );

        setCrosshair({
            x: 5000,
            y: pinFromPixels(0, (top + bottom) / 2 - box.top, 1, box.height).y,
        });
        layer.focus({ preventScroll: true });
        // Only when the mode starts, not on every move.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [adding]);

    // Keep the crosshair in view while it moves.
    useEffect(() => {
        ringRef.current?.scrollIntoView({
            block: 'nearest',
            inline: 'nearest',
        });
    }, [crosshair]);

    const stopAdding = (focusButton = true) => {
        setAdding(false);
        setDraft(null);

        if (focusButton) {
            focusById('add-comment');
        }
    };

    const place = (point: PinPoint) => {
        setCrosshair(point);
        setDraft(point);
        focusById('comment-body');
    };

    const onLayerKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            stopAdding();

            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            place(crosshair);

            return;
        }

        const moved = moveCrosshair(crosshair, event.key, event.shiftKey);

        if (moved) {
            event.preventDefault();
            setCrosshair(moved);
            setDraft(null);
        }
    };

    const onLayerClick = (event: MouseEvent<HTMLDivElement>) => {
        const box = event.currentTarget.getBoundingClientRect();

        place(
            pinFromPixels(
                event.clientX - box.left,
                event.clientY - box.top,
                box.width,
                box.height,
            ),
        );
    };

    const select = (comment: DesignComment, from: 'pin' | 'list') => {
        setSelectedId(comment.id);
        const target = document.getElementById(
            from === 'pin' ? `comment-${comment.id}` : `pin-${comment.id}`,
        );
        target?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };

    const position = draft ?? crosshair;
    const status = !adding
        ? ''
        : draft
          ? `Pin placed at ${describePin(draft)}. Write your comment.`
          : `Crosshair at ${describePin(crosshair)}.`;

    const toolbar = round.canComment && (
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
            <button
                type="button"
                id="add-comment"
                className="lc-pill"
                aria-pressed={adding}
                onClick={() => (adding ? stopAdding(false) : setAdding(true))}
                data-tour="add-comment"
            >
                <MessageSquarePlus aria-hidden="true" className="size-5" />
                Add comment
            </button>
            <p className="text-sm text-muted-foreground">
                {adding
                    ? 'Click the spot, or use the arrow keys (Shift moves further) and press Enter. Escape cancels.'
                    : 'Pin a comment to the exact spot that should change.'}
            </p>
            <p
                id="crosshair-position"
                role="status"
                className="lc-label w-full text-foreground"
            >
                {status}
            </p>
        </div>
    );

    return (
        <DesignViewer
            designs={round.designs}
            roundLabel={round.label}
            toolbar={toolbar}
            onDesignChange={() => {
                setAdding(false);
                setDraft(null);
                setSelectedId(null);
            }}
            overlay={(design) => (
                <>
                    {shown(design.comments, filter).map((comment) => (
                        <button
                            key={comment.id}
                            id={`pin-${comment.id}`}
                            type="button"
                            className={cn(
                                'pm-pin',
                                comment.resolved && 'pm-pin-resolved',
                            )}
                            style={{
                                left: pinToCss(comment.x),
                                top: pinToCss(comment.y),
                            }}
                            aria-current={
                                comment.id === selectedId ? 'true' : undefined
                            }
                            aria-label={`Comment ${comment.number} by ${comment.authorName}${comment.resolved ? ', resolved' : ''}`}
                            onClick={() => select(comment, 'pin')}
                            data-tour="pin"
                        >
                            {comment.number}
                        </button>
                    ))}
                    {adding && (
                        <div
                            ref={layerRef}
                            tabIndex={0}
                            role="application"
                            aria-roledescription="crosshair"
                            aria-label="Place a pin. Arrow keys move the crosshair, Shift moves further, Enter drops the pin, Escape cancels."
                            aria-describedby="crosshair-position"
                            className="pm-layer pm-layer-adding"
                            onClick={onLayerClick}
                            onKeyDown={onLayerKeyDown}
                            data-tour="crosshair"
                        >
                            <div
                                className="pm-cross-x"
                                style={{ top: pinToCss(position.y) }}
                            />
                            <div
                                className="pm-cross-y"
                                style={{ left: pinToCss(position.x) }}
                            />
                            <div
                                ref={ringRef}
                                className="pm-cross-ring"
                                style={{
                                    left: pinToCss(position.x),
                                    top: pinToCss(position.y),
                                }}
                            />
                        </div>
                    )}
                </>
            )}
            aside={(design) => (
                <div className="min-w-0 space-y-4">
                    {draft && (
                        <CommentForm
                            design={design}
                            draft={draft}
                            onCancel={() => stopAdding()}
                            onSaved={(id) => {
                                stopAdding(false);
                                setSelectedId(id);
                                focusById(`comment-${id}`);
                            }}
                        />
                    )}
                    <CommentList
                        design={design}
                        filter={filter}
                        onFilterChange={setFilter}
                        selectedId={selectedId}
                        canResolve={studio && round.canComment}
                        onSelect={(comment) => select(comment, 'list')}
                    />
                </div>
            )}
        />
    );
}

function CommentForm({
    design,
    draft,
    onCancel,
    onSaved,
}: {
    design: Design;
    draft: PinPoint;
    onCancel: () => void;
    onSaved: (commentId: number) => void;
}) {
    return (
        <Form
            {...CommentController.store.form(design.id)}
            transform={(data) => ({ ...data, x: draft.x, y: draft.y })}
            errorBag="comment"
            options={{ preserveScroll: true, preserveState: true }}
            onError={focusFirstError}
            onSuccess={(page) => {
                const round = (page.props as { round?: ReviewRound }).round;
                const saved = round?.designs.find((d) => d.id === design.id);
                const newest = saved?.comments.at(-1);

                if (newest) {
                    onSaved(newest.id);
                }
            }}
            onKeyDown={(event) => {
                if (event.key === 'Escape') {
                    onCancel();
                }
            }}
            className="lc-card lc-edge-pass space-y-3 p-4"
            data-tour="comment-form"
        >
            {({ processing, errors }) => (
                <>
                    <FormField
                        name="body"
                        id="comment-body"
                        label={`New comment at ${describePin(draft)}`}
                        hint="What should change here?"
                        error={errors.body}
                    >
                        {(props) => (
                            <textarea
                                {...props}
                                required
                                rows={4}
                                maxLength={2000}
                                autoComplete="off"
                                className={textareaClassName}
                            />
                        )}
                    </FormField>
                    {(errors.x || errors.y) && (
                        <p
                            role="alert"
                            className="text-sm font-medium text-destructive"
                        >
                            {errors.x ?? errors.y}
                        </p>
                    )}
                    <div className="flex gap-2">
                        <Button disabled={processing}>Pin comment</Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={onCancel}
                        >
                            Cancel
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function shown(comments: DesignComment[], filter: Filter): DesignComment[] {
    return filter === 'all'
        ? comments
        : comments.filter((comment) =>
              filter === 'resolved' ? comment.resolved : !comment.resolved,
          );
}

function CommentList({
    design,
    filter,
    onFilterChange,
    selectedId,
    canResolve,
    onSelect,
}: {
    design: Design;
    filter: Filter;
    onFilterChange: (filter: Filter) => void;
    selectedId: number | null;
    canResolve: boolean;
    onSelect: (comment: DesignComment) => void;
}) {
    const count = design.comments.length;
    const open = design.comments.filter((comment) => !comment.resolved).length;
    const visible = shown(design.comments, filter);
    const filters: [Filter, string][] = [
        ['all', `All (${count})`],
        ['open', `Open (${open})`],
        ['resolved', `Resolved (${count - open})`],
    ];

    return (
        <section aria-labelledby="pins-heading" data-tour="pin-list">
            <h4
                id="pins-heading"
                className="lc-label mb-3 text-muted-foreground"
            >
                Comments on this design · {count}
            </h4>
            {count > 0 && (
                <fieldset className="mb-3" data-tour="pin-filter">
                    <legend className="sr-only">Show comments</legend>
                    <div className="inline-flex flex-wrap rounded-xl border border-border bg-card p-1">
                        {filters.map(([value, label]) => (
                            <label
                                key={value}
                                className="cursor-pointer rounded-lg px-2.5 py-1 text-sm font-semibold has-checked:bg-sidebar has-checked:text-sidebar-foreground has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring"
                            >
                                <input
                                    type="radio"
                                    name={`pin-filter-${design.id}`}
                                    value={value}
                                    checked={filter === value}
                                    onChange={() => onFilterChange(value)}
                                    autoComplete="off"
                                    className="sr-only"
                                />
                                {label}
                            </label>
                        ))}
                    </div>
                </fieldset>
            )}
            {count === 0 ? (
                <p className="rounded-2xl border border-dashed border-input bg-card px-4 py-6 text-sm text-muted-foreground">
                    No comments on this design yet.
                </p>
            ) : visible.length === 0 ? (
                <p className="rounded-2xl border border-dashed border-input bg-card px-4 py-6 text-sm text-muted-foreground">
                    {filter === 'open'
                        ? 'No open comments. Everything is resolved.'
                        : 'No resolved comments yet.'}
                </p>
            ) : (
                <ol className="space-y-2">
                    {visible.map((comment) => {
                        const selected = comment.id === selectedId;

                        return (
                            <li
                                key={comment.id}
                                className={cn(
                                    'lc-card',
                                    comment.resolved
                                        ? 'lc-edge-pass'
                                        : 'lc-edge-warn',
                                    selected &&
                                        'ring-2 ring-foreground ring-offset-2 ring-offset-background',
                                )}
                            >
                                <button
                                    type="button"
                                    id={`comment-${comment.id}`}
                                    aria-current={selected ? 'true' : undefined}
                                    onClick={() => onSelect(comment)}
                                    className="flex w-full gap-3 rounded-[14px] p-3 text-left focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                >
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'grid size-7 shrink-0 place-items-center rounded-full font-mono text-xs font-bold',
                                            comment.resolved
                                                ? 'border-2 border-sidebar bg-card text-sidebar'
                                                : 'bg-sidebar text-sidebar-foreground',
                                        )}
                                    >
                                        {comment.number}
                                    </span>
                                    <span className="min-w-0 flex-1 space-y-1">
                                        <span className="sr-only">
                                            Comment {comment.number}:{' '}
                                        </span>
                                        <span className="block text-sm break-words whitespace-pre-line">
                                            {comment.body}
                                        </span>
                                        <span className="block text-xs text-muted-foreground">
                                            {comment.authorName} ·{' '}
                                            {comment.authorRole === 'studio'
                                                ? 'Studio'
                                                : 'Client'}{' '}
                                            ·{' '}
                                            {formatDateTime(comment.createdAt)}
                                        </span>
                                        <span className="lc-label block text-foreground">
                                            {comment.resolved
                                                ? `Resolved${comment.resolvedByName ? ` by ${comment.resolvedByName}` : ''}`
                                                : 'Open'}
                                            {selected && ' · Selected'}
                                        </span>
                                    </span>
                                </button>
                                {canResolve && (
                                    <ResolveForm comment={comment} />
                                )}
                            </li>
                        );
                    })}
                </ol>
            )}
        </section>
    );
}

/**
 * Resolve or reopen. Keeps focus on the button, whose label flips.
 */
function ResolveForm({ comment }: { comment: DesignComment }) {
    const id = `resolve-${comment.id}`;
    const action = comment.resolved
        ? CommentResolutionController.destroy.form(comment.id)
        : CommentResolutionController.store.form(comment.id);

    return (
        <Form
            {...action}
            options={{ preserveScroll: true, preserveState: true }}
            onSuccess={() => focusById(id)}
            className="px-3 pb-3"
        >
            {({ processing }) => (
                <Button
                    id={id}
                    size="sm"
                    variant="secondary"
                    disabled={processing}
                    aria-label={`${comment.resolved ? 'Reopen' : 'Resolve'} comment ${comment.number}`}
                    data-tour="resolve-comment"
                >
                    {comment.resolved ? 'Reopen' : 'Resolve'}
                </Button>
            )}
        </Form>
    );
}
