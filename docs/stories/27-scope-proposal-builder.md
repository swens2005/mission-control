# 27. Scope: building a proposal

As the studio, I want to turn the intake into a priced proposal from my
library, and see the totals as I go.

## Acceptance criteria

- [x] On the project's Scope page, **Start a proposal** creates a draft
      **v1** with a title and an introduction (plain text, paragraphs kept).
      The intake answers stay visible next to it, so the studio can work
      from them.
- [x] **Add from library** (a native `<select>` grouped by kind, plus a
      quantity) copies the item into the proposal. The proposal keeps its
      own copy of name, description, kind and price: editing or archiving
      the library never changes a proposal.
- [x] A **custom line** can be added without the library.
- [x] Lines can be edited (name, description, quantity, unit price),
      reordered (move up and down buttons, like colors) and removed.
- [x] A proposal-level **discount** (none, percentage or fixed amount) with
      an optional label ("Returning client").
- [x] The **totals** (subtotal, discount, VAT 21%, total) come from
      `ProposalTotals` on the server after every save; there is no second
      copy of the money math in TypeScript.
- [x] Every write is recorded with `Activity::record()` (studio-only; the
      client hears nothing until it's sent).
- [x] Repeated line forms have unique ids and their own `errorBag`; focus
      stays on the edited line after saving and moves to the next line (or
      "Add from library") after removing one.
- [x] Cockpit look: lines in an `lc-card` with the totals in an
      `lc-console` readout ("SUBTOTAL … VAT 21% … TOTAL").
- [x] `data-tour`: `start-proposal`, `add-from-library`, `proposal-lines`,
      `proposal-discount`, `proposal-totals`.

## Tests

- [x] Draft CRUD for lines and discount; totals in the response match
      `ProposalTotals`.
- [x] Library edits after adding don't change the proposal line.
- [x] Validation: quantity in quarter steps and > 0, price ≥ 0 and below a
      sane cap, discount within range.
- [x] Another workspace gets 404; a client gets 403 on the studio page and
      can't see a draft at all.

## Done notes

- The Scope page has two columns on wide screens: the intake (narrow) and
  the proposal. On smaller screens the intake comes first.
- A fixed discount that becomes larger than the subtotal after removing
  lines is capped at the subtotal and the builder warns about it (ADR 0010
  updated); entering one is still refused.
- The totals console has a polite live region, so the new total is read
  out after each change.
- Each form has its own error bag (`proposal-details`, `library`,
  `custom-line`, `discount`, `line-{id}`).
