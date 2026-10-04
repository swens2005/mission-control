import { Form, usePage } from '@inertiajs/react';
import DemoController from '@/actions/App/Http/Controllers/DemoController';
import { timeAgo } from '@/components/activity-list';

/**
 * Shown to demo visitors in both portals: what this is, when it ends, and a
 * one-click switch between the studio and client side of the same sandbox.
 */
export function SandboxBanner() {
    const { workspace, portal } = usePage().props;

    if (!workspace?.isSandbox) {
        return null;
    }

    return (
        <aside
            aria-label="Demo"
            className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-foreground px-4 py-2 text-center text-sm text-background"
        >
            <p>
                <span className="font-semibold">Demo studio.</span> Your own
                copy
                {workspace.expiresAt &&
                    `, deleted ${timeAgo(workspace.expiresAt)}`}
                .
            </p>
            <Form {...DemoController.switch.form()}>
                {({ processing }) => (
                    <button
                        type="submit"
                        disabled={processing}
                        data-tour="switch-portal"
                        className="rounded-sm px-2 py-0.5 font-semibold underline underline-offset-4 hover:no-underline focus-visible:outline-background"
                    >
                        {portal === 'client'
                            ? 'View as studio'
                            : 'View as client'}
                    </button>
                )}
            </Form>
        </aside>
    );
}
