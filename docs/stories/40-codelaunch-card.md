# 40. The Mission 05 card on codelaunch.nl

As a visitor to codelaunch.nl, I want to find Mission Control from the
portfolio and start it in one click.

This story changes the `../meagan-swenson` repo, not this one. Nothing is
touched there until Meagan has approved the plan.

## Acceptance criteria

- [ ] A Mission 05 card in the same style as the other missions, with
      the one sentence and two buttons: **Watch it fly**
      (`/mission-control/autopilot`) and **Take the controls**
      (`/mission-control/login`).
- [ ] The portfolio's CSP and `.htaccess` keep working; no new outside
      sources. Mission Control's own pages are unaffected (ADR 0006).
- [ ] The CV timeline gets Meagan's 14-year design business, with
      details she provides (name, years, what it did). Nothing invented.
- [ ] The card works with a keyboard, at 320 px, and passes the
      portfolio's own Lighthouse budget (`lighthouserc.json`).
- [ ] After it's live, both buttons are clicked on the real site.

## Tests

- [ ] Whatever the portfolio repo already runs in CI; plus the click-
      through above.
