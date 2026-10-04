# 00. Walking skeleton

As the developer, I want an empty app that is tested in CI and deployed to
`https://codelaunch.nl/mission-control`, so the hosting pipeline is proven
before any features exist.

## Acceptance criteria

- [x] Laravel 13 + Inertia + React/TS + Tailwind starter kit, with Pest,
      Larastan and Pint.
- [x] Public registration, email verification and passkeys removed
      (ADR 0001).
- [x] CI on every push and pull request: gitleaks secret scan, Pint, Larastan,
      Vite+ lint/format, TypeScript, production build, Pest against
      MariaDB 11.4.
- [x] `.env` and every `.env.*` variant are git-ignored, except the two
      templates.
- [x] `public/index.php` works both locally and in the split production
      layout (ADR 0002).
- [x] `POST /_deploy/migrate` runs migrations only with the right bearer
      token, answers 404 otherwise, is rate limited and sets no cookies
      (ADR 0003, `tests/Feature/DeployTest.php`).
- [ ] Deploy job uploads over FTPS, runs migrations and the `/up` smoke test
      passes on production (needs the one-time steps in
      `docs/production-setup.md`).
