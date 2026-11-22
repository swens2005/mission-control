# 48 The art: login hero, station emblems, empty states, share image

Studio Redesign, Mission Control step 6 (the last one).
Plan: https://claude.ai/artifact/VDRGUfcs8qgr1HNx1MdRRM ("Mission Control:
the launch site"). Art: approved 19 Nov, already in
`resources/images/mission-control/` (prompts in `docs/art/prompts.md`).

## Story

As a visitor or a client, I want Mission Control's own pictures on the login,
the module pages and the empty screens, so the app feels like the launch site
the CV climbs from and not like a template with art on one page.

## Acceptance criteria

- [x] **Login hero.** Guest pages (login, password reset, two-factor, studio
      setup) show the launch tower at dawn. From 1024 px the scene fills the
      screen with the rocket on the right and the form card in the open sky on
      the left; below that the scene is a band on top with the card under it,
      the rocket still in view. All text stays on the solid card. The picture
      is decorative (`alt=""`), has width and height, and loads first
      (`fetchpriority="high"`, not lazy) because it is the largest paint.
- [x] **Station emblems.** The Scope, Scope library, Palette Lab, Proofmark and
      Launch Control pages show their round badge next to the page title
      (48 px, 64 px from 640 px). The badge is decorative; the title stays the
      heading. Scope also gets its landmark (the drafting table) at the right
      of its header from 768 px.
- [x] **Simulator.** The autopilot page ("Watch it fly") and the "Take the
      controls" box on the login show the simulator badge, so the demo has
      its emblem too.
- [x] **Empty states** get their picture, decorative and lazy-loaded. No
      projects yet (admin projects, a client's page, the flight deck's
      missions, Launchpad): the empty pad. No clients yet (clients page, "Add
      a client first"): the viewing bench. No activity yet (activity page):
      the radar. Empty scope library: the card drawer. No design rounds yet,
      or no designs in a Proofmark round: the easel. Nothing waiting on clients (flight deck):
      the all-clear console. "No … match" states after a filter keep their
      text only.
- [x] **Share image.** `public/images/og.jpg`, 1200×630, made from
      `og-base.jpg` with the title added in code from a template in
      `docs/art/og.html` (fonts and colors from the app). Every page has
      `og:title`, `og:description`, `og:image` (absolute URL, with the
      subfolder), its size and alt text, and `twitter:card`
      `summary_large_image`. Feature test checks the tags and the file's size.
- [x] No new colors outside `themes.css`; text over art never relies on the
      art for contrast.
- [x] Every `data-tour` hook stays; autopilot browser tests pass.
- [x] 1280 px and 320 px, no sideways scroll, no console errors.
