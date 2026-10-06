import { Form } from '@inertiajs/react';
import { Copy, Link2, Send, Unlock } from 'lucide-react';
import { useState } from 'react';
import KitSharingController from '@/actions/App/Http/Controllers/Admin/KitSharingController';
import { focusById } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { KitSharing } from '@/types';

/**
 * Where the kit stands with the client, and what the studio can do next
 * (story 24): share it, turn the public link on or off, or start a
 * revision after approval.
 */
export function SharingCard({
    kitId,
    sharing,
}: {
    kitId: number;
    sharing: KitSharing;
}) {
    const [copied, setCopied] = useState('');
    const approved = sharing.approvedAt !== null;
    const shared = sharing.sharedAt !== null;

    const status = approved
        ? `Approved by ${sharing.approvedByName ?? 'the client'} on ${formatDateTime(sharing.approvedAt)}. The kit is locked.`
        : shared
          ? `Shared with the client on ${formatDateTime(sharing.sharedAt)}, waiting for approval.`
          : 'Not shared yet. Only the studio can see this kit.';

    return (
        <section
            aria-labelledby="sharing-heading"
            className={cn(
                'lc-card space-y-4 p-5',
                approved
                    ? 'lc-edge-pass'
                    : shared
                      ? 'lc-edge-warn'
                      : 'lc-edge-ink',
            )}
        >
            <div>
                <p className="lc-label text-muted-foreground">
                    Brand kit · with the client
                </p>
                <h2
                    id="sharing-heading"
                    tabIndex={-1}
                    className="text-xl font-bold"
                >
                    {approved ? 'Approved' : shared ? 'Shared' : 'Not shared'}
                </h2>
                <p className="text-sm text-muted-foreground">{status}</p>
            </div>

            <div className="flex flex-wrap items-start gap-3">
                {!shared && (
                    <Form
                        {...KitSharingController.share.form(kitId)}
                        options={{ preserveScroll: true }}
                        onSuccess={() => focusById('sharing-heading')}
                    >
                        {({ processing, errors }) => (
                            <>
                                <button
                                    className="lc-pill"
                                    disabled={processing}
                                    data-tour="share-kit"
                                >
                                    <Send
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                    Share with the client
                                </button>
                                {errors.share && (
                                    <p
                                        role="alert"
                                        className="mt-2 text-sm font-medium text-destructive"
                                    >
                                        {errors.share}
                                    </p>
                                )}
                            </>
                        )}
                    </Form>
                )}

                {approved && (
                    <Form
                        {...KitSharingController.revise.form(kitId)}
                        options={{ preserveScroll: true }}
                        onBefore={() =>
                            window.confirm(
                                'Start a revision? The approval is cleared and the client is asked to approve again.',
                            )
                        }
                        onSuccess={() => focusById('sharing-heading')}
                    >
                        <Button variant="secondary">
                            <Unlock aria-hidden="true" className="size-4" />
                            Start a revision
                        </Button>
                    </Form>
                )}
            </div>

            {shared && (
                <div
                    className="space-y-2 border-t pt-4"
                    data-tour="public-link"
                >
                    <h3 className="font-semibold">Public link</h3>
                    <p className="text-sm text-muted-foreground">
                        A read-only style guide anyone with the link can open,
                        without logging in. Turning it off (or on again) retires
                        the old link.
                    </p>
                    {sharing.publicUrl ? (
                        <>
                            <p className="font-mono text-sm break-all">
                                {sharing.publicUrl}
                            </p>
                            <div className="flex flex-wrap items-center gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={async () => {
                                        try {
                                            await navigator.clipboard.writeText(
                                                sharing.publicUrl ?? '',
                                            );
                                            setCopied('Link copied.');
                                        } catch {
                                            setCopied(
                                                'Copying was blocked; select the link instead.',
                                            );
                                        }
                                    }}
                                >
                                    <Copy
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    Copy link
                                </Button>
                                <Form
                                    {...KitSharingController.disablePublicLink.form(
                                        kitId,
                                    )}
                                    options={{ preserveScroll: true }}
                                    onSuccess={() =>
                                        focusById('public-link-toggle')
                                    }
                                >
                                    <Button
                                        id="public-link-toggle"
                                        size="sm"
                                        variant="secondary"
                                    >
                                        Turn off
                                    </Button>
                                </Form>
                                <p
                                    role="status"
                                    className="text-sm font-semibold"
                                >
                                    {copied}
                                </p>
                            </div>
                        </>
                    ) : (
                        <Form
                            {...KitSharingController.enablePublicLink.form(
                                kitId,
                            )}
                            options={{ preserveScroll: true }}
                            onSuccess={() => {
                                setCopied('');
                                focusById('public-link-toggle');
                            }}
                        >
                            <Button
                                id="public-link-toggle"
                                size="sm"
                                variant="secondary"
                            >
                                <Link2 aria-hidden="true" className="size-4" />
                                Turn on the public link
                            </Button>
                        </Form>
                    )}
                </div>
            )}
        </section>
    );
}
