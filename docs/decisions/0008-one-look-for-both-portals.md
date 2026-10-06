# 0008. One look for both portals

- Status: accepted (supersedes [0005](0005-client-theme.md))
- Date: 2026-10-06

## Context

ADR 0005 gave Launchpad, the client portal, its own risograph look (paper,
ink, riso pink and teal, Archivo expanded) so the two portals would feel
different. After Launch Control's design pass (story 13) brought the
codelaunch.nl cockpit into Mission Control, Meagan reviewed both portals on
production: the studio side looked right, the client side looked plain and
unfinished next to it, and the riso style didn't read as part of her brand.

## Decision

Launchpad uses the same codelaunch.nl look as Mission Control:

- The client theme block in `resources/css/themes.css` has the same values
  as the admin block. It stays a separate block, so the portals can diverge
  again later without touching markup.
- Launchpad keeps its own identity through layout, not color: a navy top
  bar with the rocket logo, and a pale day sky (`--sky`) fading into the
  page, like the top of codelaunch.nl. Mission Control keeps its sidebar.
- Both portals use the cockpit pieces from `resources/css/launch.css`
  (cards with a colored top edge, mono labels, lamps, chunky buttons, the
  mission console).
- Removed: the riso CSS helpers, the riso tokens, the dark "space" board
  (replaced by the mission console in both portals) and the Archivo font.

## Consequences

- One design system to maintain; every new module gets the look for free.
- `ThemeContrastTest` checks the client block like before, plus the new
  `--sky` pairs (all text colors pass on it, including green links).
- Palette Lab (Phase 4) will demo the codelaunch.nl palette instead of the
  riso one, including the lime-for-shapes / green-for-text rule.
- The 14 KB Archivo font is no longer shipped.
