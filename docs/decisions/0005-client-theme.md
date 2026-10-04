# 0005. Launchpad (client portal) theme

- Status: accepted
- Date: 2026-10-04

## Context

The admin portal matches codelaunch.nl. The client portal, Launchpad, needs a
look of its own that still fits the missions-to-space story and passes
WCAG 2.2 AA. Candidates: Risograph print studio, Blueprint, Botanical.

## Decision

**Risograph mission poster:** 1960s space-race screen-printed launch posters
and mission patches. Few spot colors on paper, halftone dots, slightly
misregistered overprint shapes.

| Token     | Value     | Contrast on paper | Use                                        |
| --------- | --------- | ----------------- | ------------------------------------------ |
| Paper     | `#f4efe6` | n/a               | Background                                 |
| Ink       | `#1d1a17` | 15.1:1            | Text                                       |
| Riso pink | `#ff48b0` | 2.7:1             | **Shapes only.** Ink text on pink is 5.6:1 |
| Riso teal | `#00838a` | 4.0:1             | **Shapes and large text only**             |
| Teal ink  | `#007379` | 4.9:1             | Text-safe teal: links, buttons             |

The original riso teal fails AA for body text on paper (4.0:1, AA needs
4.5:1), so text uses **teal ink**, the nearest darker shade that passes with
some margin. It was found by lowering lightness only, the same approach as
Palette Lab's "Fix it". The admin theme works the same way: lime is for
shapes, dark green for text.

## Consequences

- It fits the Launchpad name, the space theme, and Proofmark (marking up
  printed proofs), and looks clearly different from Mission Control.
- Palette Lab verifies this palette as a demo moment, including the teal fix.
- Halftone and misregistration effects are decoration only (CSS, no images
  of text), and are switched off under `prefers-reduced-motion` if animated.
