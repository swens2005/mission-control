import type { StyleGuide as Guide } from '@/types';
import { TypeSpecimen } from './type-section';

/**
 * The brand kit as a read-only style guide (story 24), for the client in
 * Launchpad and for the public link. Every color is also written out.
 */
export function StyleGuide({ guide }: { guide: Guide }) {
    return (
        <div className="space-y-12" data-tour="style-guide">
            <section aria-labelledby="guide-colors" className="space-y-4">
                <div>
                    <p className="lc-label text-muted-foreground">
                        Style guide · colors
                    </p>
                    <h2 id="guide-colors" className="text-2xl font-extrabold">
                        Colors
                    </h2>
                </div>
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {guide.colors.map((color, i) => (
                        <li
                            key={color.id}
                            className="lc-card flex overflow-hidden"
                        >
                            <span
                                aria-hidden="true"
                                className="w-20 shrink-0 border-r"
                                style={{ backgroundColor: color.hex }}
                            />
                            <span className="min-w-0 space-y-1 p-4">
                                <span className="lc-label block text-foreground">
                                    {String(i + 1).padStart(2, '0')} ·{' '}
                                    {color.name}
                                </span>
                                <span className="block text-sm text-muted-foreground">
                                    {color.roleLabel}
                                </span>
                                <span className="block font-mono text-sm">
                                    {color.hex}
                                </span>
                                <span className="block font-mono text-xs break-all text-muted-foreground">
                                    {color.oklch}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            </section>

            <section aria-labelledby="guide-pairs" className="space-y-4">
                <div>
                    <p className="lc-label text-muted-foreground">
                        Style guide · readable pairs
                    </p>
                    <h2 id="guide-pairs" className="text-2xl font-extrabold">
                        Text that's easy to read
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Every combination here meets WCAG AA for normal text
                        (4.5:1 or more). Colors marked "Shapes only" are for
                        icons and decoration, never for text.
                    </p>
                </div>
                {guide.pairs.length === 0 ? (
                    <p className="text-muted-foreground">No text pairs yet.</p>
                ) : (
                    <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {guide.pairs.map((pair) => (
                            <li
                                key={`${pair.text}-${pair.surface}`}
                                className="lc-card overflow-hidden"
                            >
                                <p
                                    className="px-4 py-5 font-display text-xl font-bold"
                                    style={{
                                        backgroundColor: pair.surfaceHex,
                                        color: pair.textHex,
                                    }}
                                >
                                    {pair.text} on {pair.surface}
                                </p>
                                <p className="border-t px-4 py-2 text-sm">
                                    <span className="font-mono">
                                        {pair.ratio}
                                    </span>{' '}
                                    ·{' '}
                                    <span className="font-semibold text-status-pass">
                                        {pair.gradeLabel}
                                    </span>
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section aria-labelledby="guide-type" className="space-y-4">
                <div>
                    <p className="lc-label text-muted-foreground">
                        Style guide · type
                    </p>
                    <h2 id="guide-type" className="text-2xl font-extrabold">
                        Type
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {guide.type.headingLabel} for headings,{' '}
                        {guide.type.bodyLabel} for text.
                    </p>
                </div>
                <TypeSpecimen type={guide.type} />
            </section>
        </div>
    );
}
