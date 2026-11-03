# 40. The Mission 05 card on codelaunch.nl

As a visitor to codelaunch.nl, I want to find Mission Control from the
portfolio and start it in one click.

This story changes the `../meagan-swenson` repo, not this one. Nothing is
touched there until Meagan has approved the plan.

## Acceptance criteria

- [x] A Mission 05 card in the same style as the other missions, with
      the one sentence and two buttons: **Watch it fly**
      (`/mission-control/autopilot`) and **Take the controls**
      (`/mission-control/login`).
- [x] The portfolio's CSP and `.htaccess` keep working; no new outside
      sources. Mission Control's own pages are unaffected (ADR 0006).
- [x] The CV timeline gets Meagan's 13-year design business, with
      details she provides (name, years, what it did). Nothing invented.
- [x] The card works with a keyboard, at 320 px, and passes the
      portfolio's own Lighthouse budget (`lighthouserc.json`).
- [x] After it's live, both buttons are clicked on the real site.

## Tests

- [x] Whatever the portfolio repo already runs in CI; plus the click-
      through above.

## Done notes

- Option A, approved by Meagan: a new floor after Mission 04, "Mission 05 ·
  2026 (portfolio project)", in the lime card style, with the contact
  card's buttons: Watch it fly, Take the controls, Source on GitHub.
  One CSS rule (buttons in a row on wide screens, no mid-word breaks).
  `llms.txt` lists the project; the sitemap date is updated.
- Checked locally: html-validate 11.16.1 (the portfolio CI's version),
  `node --check`, 320 px without sideways scroll, visible focus, a clean
  console, Lighthouse 100/100/100 on mobile.
- Live since 2 November (portfolio 21a8d27, pushed by Meagan); "13 years"
  fix and the new CV PDF in 19cae3b. Both buttons clicked on the live
  site: the autopilot and the login page open.
- The design business: Meagan added Adroidea herself (a "Side mission"
  floor and a Client work page, portfolio commit f427343): 13 years,
  2009–2022. The Mission 05 card and this repo's README now say 13 years,
  not 14 as the plan had it.
- Jobby's CV lists Adroidea and Mission Control too (Meagan asked; Jobby
  34e1b39), and the portfolio's CV PDF was regenerated from it (4 pages
  now, readability check passed).
