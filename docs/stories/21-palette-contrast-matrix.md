# 21. Palette Lab: the contrast matrix and "Fix it"

As a studio admin, I want to see at a glance which color pairs pass WCAG,
and fix a failing one without changing the color's character.

## Acceptance criteria

- [ ] A **contrast matrix**: every text and accent color (rows) on every
      surface color (columns), with the ratio ("4.82:1") and badges:
      **AAA** (7:1), **AA** (4.5:1), **AA large** (3:1) or **Fails**.
      Ratios come from the existing `App\Support\Color\Contrast`.
- [ ] **Shape-only** colors (like lime) are checked against 3:1 as
      non-text graphics (WCAG 1.4.11), and their text cells say
      "Shapes only" instead of a failing grade. This is the
      lime-for-shapes / green-for-text rule.
- [ ] **Fix it** on a failing cell: finds the nearest lightness that passes
      AA, keeping chroma and hue (`App\Support\Color\ContrastFixer`, a pure
      function). It shows before and after, and applying it changes that
      color (with an activity entry).
- [ ] The matrix is a real `<table>` with row and column headers; badges
      are text, colors are a second signal. At 320 px it scrolls inside
      its own focusable region.
- [ ] `data-tour`: `contrast-matrix`, `contrast-cell`, `fix-it`.

## Tests

- [ ] Unit: the fixer darkens on light surfaces and lightens on dark ones,
      returns the closest passing lightness, keeps chroma and hue, and
      reports when nothing passes (lime on cream as text).
- [ ] Unit: the matrix grades, including shape-only colors.
- [ ] Applying a fix updates the color and records it.

## Notes

- Demo moment: the codelaunch.nl kit starts with the CV site's original
  screen label blue, which is 3.6:1 on the lower screen gradient. "Fix it"
  proposes the darker blue the app actually uses (ADR 0008).
