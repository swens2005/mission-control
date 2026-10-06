# 13. Launch Control design pass

As Meagan, I want Launch Control to look like it belongs to codelaunch.nl,
not like a plain admin form, because it is the module visitors see first.

The look comes from the CV site (`../meagan-swenson/css/style.css`): the toy
cockpit console with rivets and a glowing screen, the altitude HUD readout,
chunky glossy buttons, cards with a colored top edge, and "● PROJECT 01"
mono labels.

## Acceptance criteria

- [x] **Mission console** at the top of the studio's Launch Control page: a
      riveted cream console with a sky-blue "screen" (mission briefing,
      project, site, a mono status line with a blinking cursor), a large
      status lamp (GO lime, NO-GO orange, CLEAR white), a T-minus HUD
      readout to the target launch date, and the five systems as lamp rows.
      Signing and "Mark as launched" live in the console as chunky buttons.
- [x] **Readouts** under it, like the CV's stat cards: checks passed,
      checklist done and sign-offs, each with a small semicircle gauge.
- [x] **Automated checks** as a grid of cards with a colored top edge and a
      "● 01 · SECURITY" label; status stays written out.
- [x] **Checklist** split into a Studio card and a Client card.
- [x] Launchpad keeps its riso look; shared pieces adapt per portal.
- [x] Every new color is a token in `themes.css`, text pairs are in
      `ThemeContrastTest`; decoration (rivets, lamps, glow) is never the
      only signal.
- [x] The blinking cursor and lamp glow stop under
      `prefers-reduced-motion`. No new runtime `<style>`/`<script>` (CSP).
- [x] 320 px with no horizontal scroll; keyboard order unchanged; all
      `data-tour` attributes kept.
