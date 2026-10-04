# 03. Portal layouts and themes

As a user of either portal, I want a layout that is clearly "studio" or
clearly "client", works by keyboard and on a phone, and meets WCAG 2.2 AA.

## Acceptance criteria

- [x] `<html data-portal="admin|client">` is set server-side. Each portal is
      one set of CSS custom properties, mapped into Tailwind's `@theme`.
- [x] **Mission Control:** codelaunch.nl tokens (ink, soft, card, line, lime
      for shapes only, dark green for text), sidebar navigation, and a small
      HUD-style status bar in JetBrains Mono.
- [x] **Launchpad:** riso mission-poster tokens from ADR 0005 (pink for shapes
      only, teal ink for text), a top bar, and halftone/overprint decoration in
      CSS only.
- [x] **Login page:** the sky gradient (day sky to space) from codelaunch.nl.
- [x] Fonts are self-hosted `.woff2` files (SIL OFL): Bricolage Grotesque,
      Figtree and JetBrains Mono from the portfolio, plus one display face for
      Launchpad, chosen in this story and recorded in ADR 0005. The starter
      kit's Instrument Sans is removed.
- [x] Light theme only. The starter kit's appearance toggle is removed (dark
      mode goes to the backlog).
- [x] Skip link, landmarks (`header`, `nav`, `main`), visible focus rings,
      `aria-current` on the active nav item.
- [x] No horizontal scrolling at 320 px. The sidebar becomes a menu button.
- [x] Decoration respects `prefers-reduced-motion`.

## Tests

- [x] Each portal's pages render with the right `data-portal`.
- [x] Unit test: every text/background token pair in both themes meets 4.5:1
      (3:1 for large text), computed from a single token list that the CSS
      also uses.
- [x] Manual: keyboard pass and 320 px check, noted in the commit message.
