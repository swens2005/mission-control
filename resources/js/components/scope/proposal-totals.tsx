import type { Proposal } from '@/types';

/**
 * The proposal's sums as a cockpit readout. Amounts come formatted from the
 * server (ADR 0010); the browser never does money maths.
 */
export function ProposalTotals({
    proposal,
    announce = false,
}: {
    proposal: Pick<Proposal, 'totals' | 'discount'>;
    /** Read the new total out after edits (the studio's builder). */
    announce?: boolean;
}) {
    const { totals, discount } = proposal;

    return (
        <section
            aria-labelledby="totals-heading"
            className="lc-console"
            data-tour="proposal-totals"
        >
            <div className="lc-screen font-mono tabular-nums">
                <h3 id="totals-heading" className="lc-label text-screen-label">
                    Totals · excl. and incl. VAT
                </h3>
                <dl className="mt-3 grid grid-cols-[1fr_auto] gap-x-6 gap-y-1.5 text-sm">
                    <dt>Subtotal</dt>
                    <dd className="text-right">{totals.subtotal}</dd>
                    {totals.hasDiscount && (
                        <>
                            <dt>
                                Discount
                                {discount.description &&
                                discount.type === 'percentage'
                                    ? ` ${discount.description}`
                                    : ''}
                                {discount.label ? ` · ${discount.label}` : ''}
                            </dt>
                            <dd className="text-right">{totals.discount}</dd>
                            <dt>Net</dt>
                            <dd className="text-right">{totals.net}</dd>
                        </>
                    )}
                    <dt>VAT 21%</dt>
                    <dd className="text-right">{totals.vat}</dd>
                    <dt className="mt-2 border-t border-current/30 pt-2 text-base font-bold text-screen-ink">
                        Total incl. VAT
                    </dt>
                    <dd className="mt-2 border-t border-current/30 pt-2 text-right text-base font-bold text-screen-ink">
                        {totals.total}
                    </dd>
                </dl>
                {announce && (
                    <p role="status" className="sr-only">
                        Total incl. VAT: {totals.total}
                    </p>
                )}
            </div>
        </section>
    );
}
