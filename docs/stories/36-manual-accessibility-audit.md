# 36. Manual accessibility audit: keyboard, screen reader, zoom

As a visitor who doesn't use a mouse, uses a screen reader or zooms in, I
want the main flows to work for me, because axe only catches about a third
of real problems.

## Acceptance criteria

- [ ] Keyboard pass on both portals, the public pages and the autopilot
      bar: logical focus order, visible focus everywhere, no traps, Esc
      closes dialogs and exits the tour, focus lands somewhere sensible
      after every action.
- [ ] Screen reader spot check with NVDA (or Narrator) of: logging in,
      "Waiting on you", accepting a proposal, pinning a comment, and the
      autopilot captions being read out.
- [ ] Every page at 320 px wide and at 200% zoom (1280 px window): no
      horizontal page scroll, nothing cut off, nothing overlapping.
- [ ] Findings and fixes are written up in `docs/accessibility.md`
      (what was checked, how, what was found, what was fixed, what's left),
      linked from the README.
- [ ] Small fixes are made in this story; anything large goes to
      `docs/backlog.md`.

## Tests

- [ ] Each fix gets a test where one is possible (a feature test for
      markup, a browser test for focus).

## Notes

- I can drive the keyboard, focus, 320 px and zoom checks in headless
  Chrome. A screen reader needs a real desktop: Meagan runs NVDA from a
  short script of steps, and reports what she hears; I fix what comes out.
