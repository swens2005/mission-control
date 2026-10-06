# 21. Palette Lab: the contrast matrix and "Fix it"

As a studio admin, I want to see at a glance which color pairs pass WCAG,
and fix a failing one without changing the color's character.

## Acceptance criteria

- [x] A **contrast matrix**: every text and accent color (rows) on every
      surface color (columns), with the ratio ("4.82:1") and badges:
      **AAA** (7:1), **AA** (4.5:1), **AA large** (3:1) or **Fails**.
      Ratios come from the existing `App\Support\Color\Contrast`.
- [x] **Shape-only** colors (like lime) are checked against 3:1 as
      non-text graphics (WCAG 1.4.11), and their text cells say
      "Shapes only" instead of a failing grade. This is the
      lime-for-shapes / green-for-text rule.
- [x] **Fix it** on a failing cell: finds the nearest lightness that passes
      AA, keeping chroma and hue (`App\Support\Color\ContrastFixer`, a pure
      function). It shows before and after, and applying it changes that
      color (with an activity entry).
- [x] The matrix is a real `<table>` with row and column headers; badges
      are text, colors are a second signal. At 320 px it scrolls inside
      its own focusable region.
- [x] `data-tour`: `contrast-matrix`, `contrast-cell`, `fix-it`.

## Tests

- [x] Unit: the fixer darkens on light surfaces and lightens on dark ones,
      returns the closest passing lightness, keeps chroma and hue, and
      reports when nothing passes (lime on cream as text).
- [x] Unit: the matrix grades, including shape-only colors.
- [x] Applying a fix updates the color and records it.

## Notes

- Demo moment: the codelaunch.nl kit starts with the CV site's original
  screen label blue `#4a7fc4`, which is 3.23:1 on the lower screen
  gradient. "Fix it" proposes `#3367ab` (4.51:1); the app uses the darker
  `#33598a` (5.64:1) for extra margin (ADR 0008).
- "Fails" for text also covers "AA large only": both get "Fix it". Shape
  cells say "Icons OK" (3:1) or "Decoration only", never "Fails".
