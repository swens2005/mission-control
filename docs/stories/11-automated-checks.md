# 11. Automated pre-flight checks

As a studio admin, I want to run automated checks against the site with one
click and see each one pass, warn or fail with a plain explanation, so I can
fix problems before launch.

## Acceptance criteria

- [x] Each check is a small class implementing `LaunchCheck`, returning a
      `CheckResult` (pass, warn or fail, plus a one-line message and optional
      details). The checks:
    - HTTPS, and `http://` redirects to `https://`
    - HSTS (warn when `max-age` is under 6 months)
    - Content-Security-Policy, from the header **or** a `<meta http-equiv>`
      tag (codelaunch.nl delivers its real policy in a meta tag, ADR 0006);
      warn on `unsafe-inline`/`unsafe-eval` in `script-src`
    - Other security headers: `X-Content-Type-Options`, `Referrer-Policy`,
      clickjacking protection (`X-Frame-Options` or `frame-ancestors`),
      `Permissions-Policy`
    - Title and meta description (present, sensible length)
    - Open Graph image (present, absolute URL, reachable)
    - Image alt coverage (`alt=""` counts as decorative, missing `alt` fails)
    - Exactly one `h1`, and no skipped heading levels
    - `robots.txt` and `sitemap.xml` reachable
    - Response time and page weight
- [x] All network access goes through `SafeFetcher` (story 10). The page is
      fetched once and shared by the checks that only read it.
- [x] **Runs synchronously** in the request (no queue on the host, ADR 0002),
      with a 25 s total budget: checks that don't get a turn are marked
      "skipped: time budget".
- [x] Each run is stored (`check_runs`, `check_results`) with who ran it and
      when. The page shows the latest run and the previous run's time.
- [x] **Waive:** an admin can waive a failing or warning check with a
      required reason. Waivers belong to the launch, survive new runs, and
      can be withdrawn. Shown as "Waived: reason, by whom".
- [x] Rate limit: 5 runs per user per 10 minutes, and per launch, with a
      friendly message showing when to try again.
- [x] Demo sandboxes can only run checks against `codelaunch.nl`
      (ADR 0007); other URLs get a friendly explanation.
- [x] Clients see the latest results read-only in Launchpad.
- [x] Results are a list with status as text and icon (not color alone); the
      run button announces progress and the summary via a live region.
- [x] Activity: "ran launch checks (9 passed, 1 warning)", visible to the
      client.
- [x] `data-tour`: `run-checks`, `check-results`, `check-result`, `waive`.
- [x] **Demo:** the sandbox launch ("Online pre-orders") targets codelaunch.nl, and running the
      checks there shows every check green.

## Tests

- [x] Unit test per check, with fixture HTML and headers: pass, warn and
      fail cases.
- [x] Feature: running checks stores a run (fetcher faked); rate limit
      applies; a waiver survives a new run; clients can't run or waive.
- [x] Another workspace's launch returns 404.

## Notes

- Status colors are new theme tokens (`--status-pass/warn/fail`, text-safe
  in both portals, covered by `ThemeContrastTest`); status is always also
  written out and shown with an icon.
- HTML is parsed with PHP 8.5's HTML5 parser (`Dom\HTMLDocument`), which
  copes with real-world markup and supports CSS selectors.
- Checks can be unit tested without HTTP: `CheckContext::fake()` takes the
  responses as fixtures.
- Live run against codelaunch.nl from a development machine: all 10 checks
  pass in about 2.7 s. The first run on production is the outbound HTTP
  test (ADR 0007).
