# 0010. Proposal money: integer cents, VAT once on the total

- Status: accepted
- Date: 2026-10-19

## Context

Scope proposals add up lines (fixed price and hourly), an optional discount
and Dutch VAT (21%). Sums of money must be exact and must not change after a
proposal has been sent or accepted.

Floats can't represent most cent amounts (0.1 + 0.2 ≠ 0.3), so money in
this app is always integer cents (CLAUDE.md). Rounding still happens in
three places: hourly lines with quarter hours, percentage discounts, and
VAT. The Dutch tax office allows VAT to be rounded per line or on the
total, as long as it's done consistently. The two can differ by a cent or
more on the same proposal.

## Decision

- **Storage:** amounts in integer cents, quantities in integer hundredths
  (1.25 hours = 125), discount percentages in basis points (10% = 1000).
- **Rounding:** half up (away from zero; all amounts are positive), done
  with integer arithmetic only: `intdiv(a * b + half, divisor)`.
- **Order:** line total → subtotal → discount → VAT → total.
  - Each line total is rounded to the cent.
  - The discount is rounded once.
  - **VAT is calculated once, on the discounted subtotal**, not per line.
- **Prices are entered excluding VAT** (business clients), and proposals
  show subtotal, discount, VAT and total.
- **Frozen totals:** sending a version stores its computed totals. Pages
  show the stored totals for sent versions, so a later fix to the math
  can't change what a client accepted.
- All of it lives in one pure class, `App\Support\Money\ProposalTotals`,
  with unit tests on every rounding case. There's no TypeScript copy.

## Consequences

- VAT matches what a calculator gives for "subtotal × 21%", which is what
  a client checks first. Per-line VAT could disagree with that by a cent.
- One VAT rate per proposal. Mixed rates (9% and 21%) would need VAT per
  rate group; nothing a web studio sells uses 9%, so that's in the backlog
  rather than the build.
- `PHP_INT_MAX` cents is about €92 trillion, so overflow isn't a concern.
