import { router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import type { FlashToast } from '@/types';

type Toast = FlashToast & { id: number };

const DISMISS_AFTER_MS = 6000;

/**
 * Shows the server's flash toasts (Inertia::flash('toast', ...)).
 *
 * Our own small component instead of a toast library, because the usual
 * ones inject <style> tags at runtime, which the strict CSP blocks. The
 * region is always in the DOM so screen readers announce new messages.
 */
export function FlashToaster() {
    const [toasts, setToasts] = useState<Toast[]>([]);

    useEffect(() => {
        let nextId = 1;

        return router.on('flash', (event) => {
            const data = (event as CustomEvent).detail?.flash?.toast as
                | FlashToast
                | undefined;

            if (!data) {
                return;
            }

            const id = nextId++;
            setToasts((current) => [...current, { ...data, id }]);
            window.setTimeout(
                () =>
                    setToasts((current) => current.filter((t) => t.id !== id)),
                DISMISS_AFTER_MS,
            );
        });
    }, []);

    const dismiss = (id: number) =>
        setToasts((current) => current.filter((t) => t.id !== id));

    return (
        <div
            role="status"
            aria-live="polite"
            className="pointer-events-none fixed right-4 bottom-4 z-50 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2"
        >
            {toasts.map((toast) => (
                <div
                    key={toast.id}
                    className={cn(
                        'pointer-events-auto flex items-start gap-3 rounded-md border-2 bg-card px-4 py-3 text-sm text-card-foreground shadow-lg',
                        toast.type === 'error'
                            ? 'border-destructive'
                            : 'border-foreground',
                    )}
                >
                    <span
                        aria-hidden="true"
                        className={cn(
                            'mt-1.5 size-2 shrink-0 rounded-full',
                            toast.type === 'error'
                                ? 'bg-destructive'
                                : 'bg-brand-accent',
                        )}
                    />
                    <p className="flex-1">{toast.message}</p>
                    <button
                        type="button"
                        onClick={() => dismiss(toast.id)}
                        className="-m-1 rounded-sm p-1 text-muted-foreground hover:text-foreground"
                    >
                        <X aria-hidden="true" className="size-4" />
                        <span className="sr-only">Dismiss</span>
                    </button>
                </div>
            ))}
        </div>
    );
}
