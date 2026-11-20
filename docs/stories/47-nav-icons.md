# 47 Navigation icons: the control room's own set

Studio Redesign, Mission Control step 5.
Mock-up: https://claude.ai/artifact/1ADg31HKjC9Jqn25GdvH3v (style sheet, "Icons").

## Story

As the studio, I want the navigation to use Mission Control's own icons
instead of stock ones, so the control room feels like one designed place.

## Acceptance criteria

- [x] Six icons from the mock-up, copied as drawn (not redrawn): flight
      deck, clients, projects, scope library, activity, launch. 24 px
      grid, 1.75 strokes in `currentColor` with round ends, and one lime dot
      each, colored through a token (never a hex value in the markup).
- [x] The sidebar uses them for Flight deck, Clients, Projects, Scope library
      and Activity; the rocket buttons ("Open Launch Control", "Move to
      Launch Control", "Mark as launched") use the launch icon, its dot in
      ink on the lime button.
- [x] Lucide stays for chevrons, close buttons and other small controls.
- [x] Icons are decorative (`aria-hidden`); every link keeps its text, also
      in the collapsed sidebar (tooltip).
- [x] The lime dot is a graphic: it needs 3:1 against the sidebar, checked in
      `ThemeContrastTest`.
- [x] Every `data-tour` hook stays; autopilot browser tests pass.
- [x] 1280 px and 320 px (the sidebar sheet), no console errors.
