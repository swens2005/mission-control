# 33. Autopilot: browser tests in CI

As the developer, I want every tour to run end to end in CI, so the demo
can never break silently when the UI changes.

## Acceptance criteria

- [ ] Pest's browser plugin (`pestphp/pest-plugin-browser`, Playwright)
      runs each tour and chapter against the app with speed 0 and reduced
      motion, and fails on: a tour reaching the "lost" state, a JavaScript
      error, or a Content-Security-Policy violation in the console.
- [ ] Browser tests run in their own CI job, in parallel with the existing
      checks, so CI stays well under 10 minutes. The deploy waits for both.
      Playwright's browser is cached between runs.
- [ ] Outside calls are faked as in the feature tests (the fake
      `Resolver` and `Http::fake()` for Launch Control's checks), so CI
      never depends on codelaunch.nl being up. The real network is only
      used by the post-deploy smoke test (story 34).
- [ ] Browser tests are a separate Pest group, so `php artisan test`
      stays fast; `composer test:browser` runs them locally.
- [ ] A short note in CLAUDE.md: how to run them, and that a renamed
      `data-tour` attribute breaks them on purpose.

## Tests

- [ ] The tours themselves are the tests; plus one deliberately broken
      target in a test-only tour proves the "lost" state fails the run.
