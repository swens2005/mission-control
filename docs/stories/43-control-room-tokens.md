# 43 Control room: the admin's dark tokens

Studio Redesign, Mission Control step 1. Plan: Claude Doc "Studio Redesign:
Visual Plan" (https://claude.ai/artifact/VDRGUfcs8qgr1HNx1MdRRM). Approved
mock-up with its style sheet: https://claude.ai/artifact/1ADg31HKjC9Jqn25GdvH3v.

## Story

As the studio, I want Mission Control (the admin portal) to be the dark navy
control room from the approved mock-up, so it reads as the launch site the
CV climbs from, not a stock admin.

## Scope

Colors only, through `resources/css/themes.css`: no layout, markup or
behavior changes (those are stories 44 to 48). Launchpad (client) and the
guest pages (login) keep today's light look until their own stories.

## Acceptance criteria

- [x] `[data-portal='admin']` gets the control room palette from the
      mock-up's style sheet: night `#0c1a30` page, bay `#172d52` cards,
      console `#13274a` sidebar, text `#eef3ff`, quiet text `#a9b6c9`,
      lime for go and links, amber for waiting, coral for failing.
- [x] The Launch Control console and its screen become dark enamel and a
      recessed readout in the admin; Launchpad keeps the light versions.
- [x] A new `--ink` token (`#10233a`, both portals) is the dark text on
      lamp-colored buttons (`lc-pill`, Navigator's violet button), so
      `--screen-ink` can turn light in the admin.
- [x] The admin declares `color-scheme: dark`, so native controls
      (selects, date fields, scrollbars) match.
- [x] Guest pages keep the light tokens (their own block).
- [x] `ThemeContrastTest` passes for every pair; it now also checks ink on
      the lime accent and on Navigator's button.
- [x] Every admin page checked in the browser for unreadable text, at
      1280 px and 320 px, and with the console clear of CSP errors.
- [x] Autopilot browser tests still pass (no `data-tour` hook changes).
