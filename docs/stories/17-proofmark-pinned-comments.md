# 17. Proofmark: pinned comments, by mouse or keyboard

As a client (or the studio), I want to drop a numbered pin on the exact spot
of a design and say what should change there, using a mouse or only the
keyboard.

## Acceptance criteria

- [x] On a round that's in review, both portals can **add a comment**:
      "Add comment" mode, then click a spot on the design, type the
      comment, save.
- [x] **Keyboard pinning:** in "Add comment" mode a crosshair appears in
      the middle of the design. Arrow keys move it by 1%, Shift + arrow by
      10%; Enter opens the comment form at that spot; Esc leaves the mode
      and returns focus to the "Add comment" button. The crosshair's
      position is announced ("Crosshair at 40% across, 25% down").
- [x] Pins are stored as **percentages** of the image, as integers in
      hundredths of a percent (0 to 10 000), so they stay put at any
      screen size or zoom.
- [x] Each pin is a numbered **button** on the design. Every pin also
      appears in an **ordered list** next to the design (the accessible
      equivalent): number, comment, author, time, status.
- [x] **In sync:** selecting a pin highlights and scrolls to its list item;
      selecting a list item highlights its pin and scrolls the design to it.
      The selected one is marked in text and with `aria-current`, not by
      color alone.
- [x] Comments are plain text (max 2000 characters), shown escaped; links
      are not made clickable.
- [x] Superseded and approved rounds show their pins and list read-only.
- [x] After saving, focus moves to the new comment in the list; the page
      keeps its scroll position (`preserveState`).
- [x] Each comment is recorded in the activity log and visible to the
      client.
- [x] At 320 px the list sits below the design, and the design can be
      panned without the page scrolling sideways.
- [x] `data-tour`: `add-comment`, `crosshair`, `pin`, `pin-list`,
      `comment-form`.

## Tests

- [x] Comments save with their position; out-of-range positions and empty
      text are refused.
- [x] Commenting on a draft, superseded or approved round is refused.
- [x] A client of another organization gets 404.
- [x] Unit (Vitest, `resources/js/lib/pin-math.test.ts`): converting
      clicks to positions, clamping at the edges, arrow-key steps.

## Notes

- The crosshair and pins are positioned with React's `style` prop (set via
  the CSSOM), which the CSP allows without `unsafe-inline`.

## Done notes

- The pin maths lives in `resources/js/lib/pin-math.ts` and is unit tested
  with Vitest through Vite+ (`npm run test:js`, new CI step). The server
  validates the range (0 to 10 000, integers) and refuses anything else.
- One comment route for both portals (`comments.store`, behind `auth`,
  30 a minute per user); the policy decides who may comment where.
- Entering "Add comment" puts the crosshair in the middle of the part of
  the design that's on screen, so it's visible on long pages.
- Pins have a light halo and a dark core so they show on any design; the
  selected pin grows and turns lime.
