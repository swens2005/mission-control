# 32. Autopilot: the client tour and the full story

As a visitor, I want to see both sides of the app: what the client sees,
and how the studio and the client work together on one project.

## Acceptance criteria

- [x] **The client tour** (about 2 minutes): logs in, switches to Anna
      with "View as client", and shows Launchpad:
    - "Waiting on you" across the modules;
    - approving the Café corner brand kit (the style guide);
    - pinning a comment on the Wedding cakes round by keyboard crosshair;
    - ticking a launch checklist item and signing the go/no-go board.
- [x] **The full story** (about 4 minutes), on "Seasonal menu pages":
    - the studio reads Anna's change request and the intake, looks at
      v2's Easter lines and totals, and **sends v2**;
    - "View as client": Anna sees "Accept proposal v2" in Waiting on you,
      opens it, types her name, ticks the box and **accepts**;
    - back as the studio: **Move to Palette Lab**, then a quick look at
      what's next in Palette Lab, Proofmark and Launch Control on the
      other projects.
- [x] Captions tell the story in plain words ("Anna asked for Easter
      pages, so the studio made v2…"), not UI instructions.
- [x] Switching portals uses the real sandbox switch (`switch-portal`), so
      the tour proves both portals and the policies between them.

## Tests

- [ ] Both tours run end to end in the browser test (story 33), and
      afterwards the database shows v2 accepted by Anna and the project in
      Palette Lab.

## Done notes

- The client tour doesn't sign the go/no-go board: in the demo the board
  isn't clear yet (checks and studio items open), and signing only opens
  then. It ticks Anna's "Analytics consent" item and shows the board
  instead; the caption says when she signs.
- The tour answers its own "Are you sure?" dialogs (sending, approving):
  `window.confirm` is replaced only for the duration of the autopilot's
  own click, so the visitor's clicks still ask.
- New engine step `press(target, key, times)` for the keyboard crosshair.
- New hooks: `waiting-item`, `client-project`, `comment-body`,
  `comment-submit`, `checklist-label`, `accept-submit`, `start-kit`,
  `demo-ready`.
- Found while watching: when a visitor has started five demos in an hour,
  "Take the controls" showed a bare 429 page. The limiter now sends them
  back to the login page with a plain message, and tours wait for
  `demo-ready`, so they stop at the right step.
- Checked in Chrome: the full story (v2 sent, View as client, accepted
  with a typed name, Move to Palette Lab) and the client tour (kit
  approved, a pin placed by keyboard and saved, checklist item ticked),
  no console errors. The database check after each tour is part of the
  browser tests (story 33).
