# 32. Autopilot: the client tour and the full story

As a visitor, I want to see both sides of the app: what the client sees,
and how the studio and the client work together on one project.

## Acceptance criteria

- [ ] **The client tour** (about 2 minutes): logs in, switches to Anna
      with "View as client", and shows Launchpad:
    - "Waiting on you" across the modules;
    - approving the Café corner brand kit (the style guide);
    - pinning a comment on the Wedding cakes round by keyboard crosshair;
    - ticking a launch checklist item and signing the go/no-go board.
- [ ] **The full story** (about 4 minutes), on "Seasonal menu pages":
    - the studio reads Anna's change request and the intake, looks at
      v2's Easter lines and totals, and **sends v2**;
    - "View as client": Anna sees "Accept proposal v2" in Waiting on you,
      opens it, types her name, ticks the box and **accepts**;
    - back as the studio: **Move to Palette Lab**, then a quick look at
      what's next in Palette Lab, Proofmark and Launch Control on the
      other projects.
- [ ] Captions tell the story in plain words ("Anna asked for Easter
      pages, so the studio made v2…"), not UI instructions.
- [ ] Switching portals uses the real sandbox switch (`switch-portal`), so
      the tour proves both portals and the policies between them.

## Tests

- [ ] Both tours run end to end in the browser test (story 33), and
      afterwards the database shows v2 accepted by Anna and the project in
      Palette Lab.
