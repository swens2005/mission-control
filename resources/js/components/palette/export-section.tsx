import { Copy, Download } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { useRef, useState } from 'react';
import TokenExportController from '@/actions/App/Http/Controllers/Admin/TokenExportController';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { TokenExport } from '@/types';

/**
 * The kit as code (story 23). Tabs follow the WAI-ARIA tabs pattern:
 * arrow keys move between tabs, Tab moves into the panel.
 */
export function ExportSection({
    kitId,
    exports,
}: {
    kitId: number;
    exports: TokenExport[];
}) {
    const [current, setCurrent] = useState(0);
    const [status, setStatus] = useState('');
    const tabs = useRef<(HTMLButtonElement | null)[]>([]);
    const active = exports[current];

    if (!active) {
        return null;
    }

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        const moves: Record<string, number> = {
            ArrowRight: 1,
            ArrowLeft: -1,
            Home: -current,
            End: exports.length - 1 - current,
        };

        if (!(event.key in moves)) {
            return;
        }

        event.preventDefault();
        const next =
            (current + moves[event.key] + exports.length) % exports.length;
        setCurrent(next);
        setStatus('');
        tabs.current[next]?.focus();
    };

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(active.content);
            setStatus(`Copied the ${active.label} to the clipboard.`);
        } catch {
            setStatus(
                'Copying was blocked by the browser. Select the code and copy it instead.',
            );
        }
    };

    return (
        <section aria-labelledby="export-heading" className="space-y-4">
            <div>
                <p className="lc-label text-muted-foreground">
                    Brand kit · export
                </p>
                <h2 id="export-heading" className="text-2xl font-extrabold">
                    Export tokens
                </h2>
                <p className="text-sm text-muted-foreground">
                    The colors and type as code, for the build.
                </p>
            </div>

            <div
                role="tablist"
                aria-labelledby="export-heading"
                className="inline-flex flex-wrap rounded-xl border border-border bg-card p-1"
                onKeyDown={onKeyDown}
                data-tour="export-tabs"
            >
                {exports.map((item, i) => (
                    <button
                        key={item.format}
                        ref={(el) => {
                            tabs.current[i] = el;
                        }}
                        type="button"
                        role="tab"
                        id={`export-tab-${item.format}`}
                        aria-selected={i === current}
                        aria-controls="export-panel"
                        tabIndex={i === current ? 0 : -1}
                        onClick={() => {
                            setCurrent(i);
                            setStatus('');
                        }}
                        className={cn(
                            'rounded-lg px-3 py-1.5 text-sm font-semibold focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring',
                            i === current
                                ? 'bg-sidebar text-sidebar-foreground'
                                : 'hover:bg-muted',
                        )}
                    >
                        {item.label}
                    </button>
                ))}
            </div>

            <div
                role="tabpanel"
                id="export-panel"
                aria-labelledby={`export-tab-${active.format}`}
                className="lc-console space-y-3"
            >
                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        size="sm"
                        onClick={copy}
                        data-tour="copy-export"
                    >
                        <Copy aria-hidden="true" className="size-4" />
                        Copy
                    </Button>
                    <Button size="sm" variant="secondary" asChild>
                        <a
                            href={TokenExportController.url({
                                kit: kitId,
                                format: active.format,
                            })}
                            download={active.filename}
                        >
                            <Download aria-hidden="true" className="size-4" />
                            Download {active.filename}
                        </a>
                    </Button>
                    <p role="status" className="text-sm font-semibold">
                        {status}
                    </p>
                </div>
                <pre
                    tabIndex={0}
                    aria-label={`${active.label} code`}
                    className="lc-screen max-h-96 overflow-auto font-mono text-xs leading-relaxed focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring"
                >
                    <code>{active.content}</code>
                </pre>
            </div>
        </section>
    );
}
