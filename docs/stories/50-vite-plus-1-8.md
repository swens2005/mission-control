# 50. Vite+ 1.8.0

## Story

As the maintainer, I want the build and test tooling on the current
Vite+ release, so `npm audit` is clean and all the studio's projects use
the same version.

## Why

Vite+ 0.3.0 pulls in a test runner and a Vite version that `npm audit`
flags. They are development tools: nothing of them is shipped to the
site. Vite+ 1.8.0 is clean.

## Acceptance criteria

- [x] `vite-plus` is 1.8.0, exact.
- [x] `vite` is the alias Vite+ 1.8.0 asks for
      (`@voidzero-dev/vite-plus-core`), as a dependency and as an
      override, and `vitest` is pinned to the bundled version, so the
      plugins and Vite+ share one copy of each.
- [x] Tests import from `vite-plus/test`.
- [x] `npm audit` reports no findings.
- [x] `npm run check`, `npm run types:check`, `npm run test:js` and
      `npm run build` pass, locally and in CI.
- [x] Pest, Larastan, Pint and the browser tours
      (`composer test:browser`) still pass.
- [ ] After the deploy, the live site works in a browser at desktop
      width and at 320 px, with a clean console.
- [x] CLAUDE.md says how to upgrade Vite+ next time (three entries
      move together).

## Not in this story

- TypeScript 7 or other major upgrades.
- New lint rules that the newer Oxlint offers.
