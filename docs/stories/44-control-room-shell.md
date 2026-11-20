# 44 Control room: the shell and instrument bays

Studio Redesign, Mission Control step 2. Mock-up:
https://claude.ai/artifact/1ADg31HKjC9Jqn25GdvH3v (control room).

## Story

As the studio, I want the admin's frame and cards to look like the control
room in the mock-up (a console sidebar, instrument bays with brass screws,
glossy buttons), so every page feels like one place, not only the dashboard.

## Scope

Styling of shared pieces only; every page picks it up. No behavior,
route or `data-tour` changes. Icons (story 47) and art (story 48) come
later; the dashboard becomes the flight deck in story 45.

## Acceptance criteria

- [x] The logo is the mock-up's rocket mark as SVG (cream body, lime nose
      and fins, round porthole, amber flame), replacing Lucide's rocket in
      every layout. Its colors are tokens.
- [x] The sidebar brand shows "Mission Control" and the studio's name in a
      HUD label underneath, on a console gradient.
- [x] The current page's nav item has a lime edge on the left as well as
      the lit background (`aria-current` unchanged).
- [x] In the admin, `lc-card` becomes an instrument bay: a bay gradient,
      a rim border, its colored top edge and two brass screws.
- [x] In the admin, the default button is the CV's glossy button with a
      darker bottom lip, ink text, pressing down when clicked; disabled
      buttons don't move.
- [x] The room has a faint glow behind the content; text never sits on
      anything but solid panels.
- [x] Colors only through tokens in `themes.css` (new brass and mark
      tokens); `ThemeContrastTest` passes.
- [x] Checked in the browser at 1280 px and 320 px, with the sidebar
      collapsed and open, no console errors; reduced motion stops the
      button press movement.
- [x] Autopilot browser tests still pass.
