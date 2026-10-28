# 33. Autopilot: browser tests in CI

As the developer, I want every tour to run end to end in CI, so the demo
can never break silently when the UI changes.

## Acceptance criteria

- [x] Pest's browser plugin (`pestphp/pest-plugin-browser`, Playwright)
      runs each tour and chapter against the app with speed 0 and reduced
      motion, and fails on: a tour reaching the "lost" state, a JavaScript
      error, or a Content-Security-Policy violation in the console.
- [x] Browser tests run in their own CI job, in parallel with the existing
      checks, so CI stays well under 10 minutes. The deploy waits for both.
      Playwright's browser is cached between runs.
- [x] Outside calls are faked as in the feature tests (the fake
      `Resolver` and `Http::fake()` for Launch Control's checks), so CI
      never depends on codelaunch.nl being up. The real network is only
      used by the post-deploy smoke test (story 34).
- [x] Browser tests are a separate Pest group, so `php artisan test`
      stays fast; `composer test:browser` runs them locally.
- [x] A short note in CLAUDE.md: how to run them, and that a renamed
      `data-tour` attribute breaks them on purpose.

## Tests

- [x] The tours themselves are the tests; plus one deliberately broken
      target in a test-only tour proves the "lost" state fails the run.

## Done notes

- `pestphp/pest-plugin-browser` 5.1 and Playwright (Chromium only). The
  plugin reserves ports for every Pest run, so PHP's `sockets` extension
  is now needed for all tests: CI lists it; locally see CLAUDE.md.
- `tests/Browser` uses `Tests\BrowserTestCase`, which turns Vite back on
  (feature tests run without built assets).
- The "lost" proof doesn't need a test-only tour in the app: the test
  strips a `data-tour` attribute as it appears and checks the tour stops
  with "Lost my way".
- Vitest is limited to `resources/js/**/*.test.ts`; the plugin ships
  example specs in `vendor/`.
- Locally: 9 browser tests in about 30 seconds.
