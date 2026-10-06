# 14. Launchpad design pass

As Meagan, I want the client portal to look as polished as Mission Control
and clearly part of codelaunch.nl, because clients judge the studio by it.

Decision: ADR 0008 (one look for both portals; replaces ADR 0005).

## Acceptance criteria

- [x] Launchpad uses the Mission Control colors and fonts.
- [x] A navy top bar with the rocket logo; the active page is marked with a
      lime underline and `aria-current`, not color alone.
- [x] A pale day sky fades into every Launchpad page; all text on it
      passes AA (`--sky` pairs in `ThemeContrastTest`).
- [x] Home: a mission-briefing header, "Waiting on you" as amber-edged
      cards, projects as lime-edged "● PROJECT 01" cards with the phase
      steps (current phase marked by a lamp, in bold, and in text).
- [x] Project page and launch page use the same cards; the launch page has
      the same mission console as the studio (without "Mark as launched").
- [x] Riso helpers, tokens, the space board and the Archivo font are gone.
- [x] 320 px with no horizontal scroll; no console errors; all `data-tour`
      attributes kept.
