# 39. README for recruiters

As a recruiter or hiring manager with ten minutes, I want the README to
show me what this is, let me watch it work, and point me at the code worth
reading.

## Acceptance criteria

- [ ] Opens with the one sentence ("the tool I wish I'd had during 14
      years of running a web design business"), then **Watch it fly** and
      **Take the controls** links, then a short GIF of the autopilot.
- [ ] Four screenshots, one per module, from a demo sandbox.
- [ ] "How it was built": stories, ADRs, CI (lint, static analysis, 750
      Pest tests, Vitest, the tours as browser tests, accessibility
      checks), and the production smoke test after every deploy. Short,
      with links.
- [ ] **The 5 most interesting files**, one line each on why:
    1. `app/Support/Http/SafeFetcher.php`: SSRF protection for fetching
       client sites (DNS pinning, private ranges, redirects).
    2. The Launch checks and `GoNoGo`: a plugin-style check registry and
       a sign-off that voids itself when anything gets less ready.
    3. `app/Support/Color/Oklch.php` + `ContrastFixer`: colour science in
       integers, fixing contrast while keeping the hue.
    4. `app/Support/Money/ProposalTotals.php`: money in integer cents,
       half-up rounding, VAT once on the total.
    5. `resources/js/lib/tour/runner.ts`: the autopilot engine, a pure
       state machine tested in Vitest that drives the real UI.
- [ ] "Run it locally" stays, checked against a clean clone.
- [ ] Media is recorded by a small script (`scripts/record-media.mjs`)
      that uses the Playwright already installed for the browser tests;
      frames are joined into a GIF with Python's Pillow, so no new project
      dependency. GIF under 5 MB, about 20 seconds, 1280 × 720 scaled down.

## Tests

- [ ] None beyond the link check: every file and link in the README
      exists (a small Pest test reads the README and checks local paths).
