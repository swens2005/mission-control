# 0011. Autopilot: a tour engine that drives the real UI

- Status: accepted
- Date: 2026-10-20

## Context

The autopilot is the showpiece: visitors from codelaunch.nl watch the app
run itself (plan §4). It has to prove the app works, so it can't be a
video. It must also stay accessible and keep the strict CSP (ADR 0006),
and it must not break silently when the UI changes.

## Decision

- **Scripts, not recordings.** A tour is a TypeScript list of steps
  (`caption`, `moveTo`, `type`, `click`, `visit`, `waitFor`). Steps find
  elements by `data-tour` names only; CSS classes can change freely.
- **Real requests.** Clicks are real clicks and typing fires real input
  events, so the tour logs in, sends and accepts through the same routes
  and policies as a person. Each run uses its own demo sandbox (ADR 0004);
  Restart makes a new one.
- **One engine above the pages.** The runner is a React provider in
  `app.tsx`, outside the Inertia page, so it survives visits and the
  portal switch. Its position is mirrored to `sessionStorage` for reloads.
- **Testable core.** The runner is a pure state machine with the DOM and
  the clock injected; Vitest covers it without a browser. A Vitest check
  also fails when a tour uses a `data-tour` name the app doesn't have.
- **CSP stays strict.** The cursor and ring are positioned with React's
  `style` prop (CSSOM, allowed without `unsafe-inline`); no library that
  injects `<style>` or `<script>`.
- **Accessibility.** Captions in a polite live region; Pause, Next,
  Restart and Exit buttons; `Esc` exits; the visitor's own input pauses
  the tour; reduced motion turns movement and typing instant; no sound
  or flashing.
- **Proof in CI.** Every tour runs end to end in Pest's browser tests
  (Playwright) at speed 0; a lost step, a JS error or a CSP violation
  fails the build.

## Consequences

- The tours double as end-to-end tests of the main flows.
- Tours depend on the demo data; changing `DemoTemplate` can break a
  tour, which the browser tests catch.
- Each run creates a sandbox, so the demo's existing rate limits and the
  active-sandbox cap also limit how often a visitor can restart.
