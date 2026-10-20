# 28. Scope: send a proposal, versions, and printing

As the studio, I want a sent proposal to stay exactly as the client saw it,
and changes to become a new version; as the client, I want to read it in
Launchpad and print it.

## Acceptance criteria

- [x] **Send to the client** (with confirmation) freezes the draft: its
      lines, discount and totals can no longer change. The stored totals
      are written once at sending (cents), so a later change to the math
      can't change a sent proposal.
- [x] **Make changes** on a sent proposal creates the next version
      (**v2**) as a draft copy. The earlier version is kept, marked
      "Superseded by v2" once v2 is sent. Only one draft at a time.
- [x] The client sees the latest **sent** version at
      `/client/projects/{project}/scope`, never a draft, with a short list
      of earlier versions (read-only, each with its own URL).
- [x] "Review the proposal" appears in **Waiting on you** while the
      latest sent version is open (`WaitingOnClient`); accepting comes in
      story 29.
- [x] **Print:** a print stylesheet (`@media print`) for both portals'
      proposal pages: no navigation or buttons, black on white, the
      studio and client names, version and date, lines and totals as a
      table, no broken rows across pages. A **Print** button calls
      `window.print()`. No PDF library.
- [x] Sending and new versions are recorded in the activity log; sending is
      visible to the client.
- [x] `data-tour`: `send-proposal`, `proposal-version`, `make-changes`,
      `client-proposal`, `print-proposal`.

## Tests

- [x] Sending freezes: editing lines or discount of a sent version is
      refused (403 or 422), also by a crafted request.
- [x] "Make changes" copies lines and discount into v2; v1 is untouched.
- [x] The client gets the sent version, 404 for drafts and for other
      organizations.
- [x] The waiting item follows the sent version's status.

## Done notes

- Sending stores the totals on the proposal and each line
  (`Proposal::freeze()`); `Proposal::totals()` returns the stored ones for
  a sent version (`ProposalTotals::frozen()`).
- Printing: `resources/css/print.css` hides anything marked
  `data-print="hide"` (layout chrome, buttons, version lists) and the
  sidebar; print colors are tokens in `themes.css` under `@media print`.
  The document is a real `<table>` with the totals in `<tfoot>`, two
  columns so it fits 320 px too.
- The client page puts the proposal first; once a proposal exists, sent
  intake answers fold into a "Your intake answers" disclosure.
- The waiting item says "Review proposal v1"; story 29 makes it "Accept".
