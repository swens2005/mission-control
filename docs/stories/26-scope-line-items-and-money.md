# 26. Scope: the line-item library and the money math

As the studio, I want a library of the things I usually sell, with prices,
so a proposal is a few clicks instead of a spreadsheet; and the sums must be
right to the cent.

## Acceptance criteria

- [ ] A workspace has a **line-item library** at `/admin/scope/library`
      (linked from the admin navigation as "Scope library").
- [ ] An item has a name, an optional description, a **kind** (fixed price
      or hourly), a **unit price** (entered as euros, "1250" or
      "1.250,50" or "1,250.50"; stored as integer cents) and a default
      quantity (1 for fixed; hours for hourly, in steps of 0.25).
      Items can be added, edited, reordered and archived (archived items
      stay on old proposals).
- [ ] All money is **integer cents**; quantities are stored as integer
      hundredths (1.5 hours = 150). No floats anywhere, also not while
      parsing input.
- [ ] A pure class, `App\Support\Money\ProposalTotals`, computes a
      proposal from its lines and its discount:
    - line total = unit price × quantity, rounded half up to the cent
      (only hourly lines with quarter hours can round);
    - subtotal = sum of the line totals;
    - an optional **discount**, either a percentage (stored in basis
      points, 10% = 1000) or a fixed amount in cents, never more than the
      subtotal;
    - **VAT at 21%** on the discounted subtotal, rounded half up **once,
      on the total** (ADR 0010);
    - total = discounted subtotal + VAT.
- [ ] `App\Support\Money\Money` formats cents for display as "€1,234.56"
      (one place, reused by every page) and parses the input formats above.
- [ ] Library changes are recorded with `Activity::record()` (studio-only).
- [ ] Cockpit look: the library as an `lc-card` table, kind as an
      `lc-pill`, prices right-aligned in tabular figures.
- [ ] `data-tour`: `scope-library`, `add-line-item`, `line-item-price`.

## Tests

- [ ] Unit (`ProposalTotals`): an empty proposal; fixed lines only; hourly
      lines with quarter hours that round; a percentage discount that
      rounds; a fixed discount; a discount larger than the subtotal is
      refused; VAT rounding cases at exactly half a cent (both directions);
      per-line VAT would differ from VAT on the total (the case ADR 0010
      is about); large amounts (€1,000,000+) stay exact.
- [ ] Unit (`Money`): parsing "1250", "1.250,50", "1,250.50", "€ 99",
      refusing "12.345.6", negative and empty input; formatting.
- [ ] Library CRUD; another workspace gets 404; a client gets 403.
