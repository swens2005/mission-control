import { Printer } from 'lucide-react';
import { formatDate, formatDateTime } from '@/lib/format';
import type { Proposal } from '@/types';

/**
 * A sent proposal as the client reads and prints it (story 28). Lines and
 * totals are a real table, so they print as one and read well in a screen
 * reader; amounts come formatted from the server.
 */
export function ProposalDocument({ proposal }: { proposal: Proposal }) {
    const { totals, discount } = proposal;

    return (
        <article
            aria-labelledby="proposal-document-title"
            className="lc-card lc-edge-ink proposal-document space-y-6 p-5 md:p-8"
            data-tour="client-proposal"
        >
            <header className="space-y-2">
                <p className="lc-label text-muted-foreground">
                    {proposal.studioName} · for {proposal.organizationName}
                </p>
                <h2
                    id="proposal-document-title"
                    className="text-2xl font-extrabold break-words md:text-3xl"
                >
                    {proposal.title}
                </h2>
                <p className="text-sm text-muted-foreground">
                    Version {proposal.number}
                    {proposal.sentAt &&
                        ` · Sent ${formatDate(proposal.sentAt.slice(0, 10))}`}
                    {proposal.sentByName && ` by ${proposal.sentByName}`} ·{' '}
                    {proposal.projectName}
                </p>
            </header>

            {proposal.introduction && (
                <p className="max-w-prose whitespace-pre-line">
                    {proposal.introduction}
                </p>
            )}

            <table className="w-full border-collapse text-sm">
                <caption className="sr-only">
                    Lines and totals, prices in euros
                </caption>
                <thead>
                    <tr className="border-b-2 border-foreground text-left">
                        <th scope="col" className="py-2 pr-4 font-semibold">
                            Item
                        </th>
                        <th
                            scope="col"
                            className="py-2 text-right font-semibold"
                        >
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {proposal.lines.map((line) => (
                        <tr key={line.id} className="border-b align-top">
                            <th
                                scope="row"
                                className="py-3 pr-4 text-left font-normal"
                            >
                                <span className="block font-semibold">
                                    {line.name}
                                </span>
                                {line.description && (
                                    <span className="block text-muted-foreground">
                                        {line.description}
                                    </span>
                                )}
                                <span className="block text-muted-foreground tabular-nums">
                                    {line.kind === 'hourly'
                                        ? `${line.quantity} h × ${line.unitPrice} per hour`
                                        : `${line.quantity} × ${line.unitPrice}`}
                                </span>
                            </th>
                            <td className="py-3 text-right font-semibold tabular-nums">
                                {line.total}
                            </td>
                        </tr>
                    ))}
                </tbody>
                <tfoot className="tabular-nums">
                    <tr>
                        <th
                            scope="row"
                            className="pt-3 pr-4 text-left font-normal"
                        >
                            Subtotal
                        </th>
                        <td className="pt-3 text-right">{totals.subtotal}</td>
                    </tr>
                    {totals.hasDiscount && (
                        <tr>
                            <th
                                scope="row"
                                className="pr-4 text-left font-normal"
                            >
                                Discount
                                {discount.type === 'percentage' &&
                                    ` ${discount.description}`}
                                {discount.label && ` · ${discount.label}`}
                            </th>
                            <td className="text-right">{totals.discount}</td>
                        </tr>
                    )}
                    <tr>
                        <th scope="row" className="pr-4 text-left font-normal">
                            VAT 21%
                            {totals.hasDiscount && ` on ${totals.net}`}
                        </th>
                        <td className="text-right">{totals.vat}</td>
                    </tr>
                    <tr className="border-t-2 border-foreground text-base font-bold">
                        <th scope="row" className="pt-2 pr-4 text-left">
                            Total incl. VAT
                        </th>
                        <td className="pt-2 text-right">{totals.total}</td>
                    </tr>
                </tfoot>
            </table>

            {proposal.acceptance && (
                <p className="border-t pt-4 text-sm">
                    <span className="font-semibold">Accepted</span> by{' '}
                    {proposal.acceptance.byName}, signed as “
                    {proposal.acceptance.signature}”, on{' '}
                    {formatDateTime(proposal.acceptance.at)}.
                </p>
            )}

            <p className="text-xs text-muted-foreground">
                Prices in euros. Amounts excl. VAT unless marked otherwise.
                {proposal.sentAt &&
                    ` Version ${proposal.number}, sent ${formatDateTime(proposal.sentAt)}.`}
            </p>
        </article>
    );
}

/** Opens the browser's print dialog; the print stylesheet does the rest. */
export function PrintButton() {
    return (
        <button
            type="button"
            className="lc-pill"
            onClick={() => window.print()}
            data-tour="print-proposal"
            data-print="hide"
        >
            <Printer aria-hidden="true" className="size-5" />
            Print or save as PDF
        </button>
    );
}
