# 16. Proofmark: send a round, and the client's view

As a studio admin, I want to send a finished round to the client, and as a
client, I want to see exactly the designs that are waiting for my review.

## Acceptance criteria

- [ ] An admin **sends** a draft round to the client (only if it has at
      least one design). A sent round is frozen: designs can't be added,
      removed or replaced. Changes mean a new round.
- [ ] Only one round per project can be **in review** at a time. Sending
      v2 marks v1 as **superseded** (kept, read-only, with its comments).
- [ ] Clients see sent rounds in Launchpad at
      `/client/projects/{project}/proofmark`, newest first; drafts are
      invisible to them (404 on the round and its images).
- [ ] A **design viewer** in both portals: one design at a time with "fit
      to width" and "actual size", a design switcher (native buttons, the
      current one marked with `aria-current`), and the round's status
      written out ("v2 · In review", "v1 · Superseded").
- [ ] "Review design round v2" appears in the client's **Waiting on you**
      (registered with `WaitingOnClient`) while a round is in review and
      not yet approved, due on the project's target date. Archived and
      launched projects are left out.
- [ ] Sending is recorded in the activity log and visible to the client
      ("sent design round v2 of Wedding cakes").
- [ ] The client's project page links to Proofmark when the project has a
      sent round.
- [ ] Same cockpit look in Launchpad (navy bar, sky fade, `lc-card`s).
- [ ] `data-tour`: `send-round`, `design-viewer`, `design-switcher`,
      `round-status`.

## Tests

- [ ] Sending freezes the round; uploading to or deleting from a sent round
      is refused.
- [ ] Sending v2 supersedes v1.
- [ ] A client sees sent rounds only; another organization's client gets
      404.
- [ ] The waiting item appears after sending and disappears after approval
      (story 18), archiving or launch.
