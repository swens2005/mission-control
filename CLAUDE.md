# Mission Control

A Laravel app that runs a small web studio from first conversation to launch
day. Admin portal: **Mission Control** (`/admin`). Client portal:
**Launchpad** (`/client`). Four modules: Scope, Palette Lab, Proofmark,
Launch Control, plus an autopilot demo. The build plan lives outside the repo
in `../mission-05-plan.md`.

## Stack

Laravel 13, PHP 8.5, Inertia 3, React 19 + TypeScript, Tailwind 4, Vite+
(`vp`: build, Oxlint, formatter), Pest, Larastan (level 7), Pint. Production
database is MariaDB 11.4. Tests use SQLite in-memory locally and MariaDB in CI.

## Commands

```
php artisan test --compact     # Pest
vendor/bin/pint                # PHP code style (CI runs --test)
vendor/bin/phpstan analyse     # Larastan
npm run check                  # lint + format check (npm run check:fix to fix)
npm run types:check            # tsc
npm run build                  # production build
```

All of these must pass before committing. CI runs the same set.

## Workflow

1. Each user story goes in `docs/stories/NN-title.md` with acceptance
   criteria, before any code.
2. Implement one story at a time, with Pest feature tests (and unit tests for
   real logic), in small commits.
3. Review each story's diff separately (correctness, security,
   accessibility) and note findings in the commit message.
4. Record significant decisions as ADRs in `docs/decisions/NNNN-title.md`.
5. Out-of-scope ideas go into `docs/backlog.md`.

## Rules

- Never read, print or edit a real `.env` file. Use `.env.example` and
  `.env.production.example` for key names. Production config is the
  `ENV_MISSION_CONTROL_PROD` GitHub secret.
- Ask before anything hard to undo, or that touches hosting, GitHub settings
  or secrets.
- Production lives in a subfolder (`/mission-control`) on shared hosting with
  no SSH, no queue worker and possibly no `proc_open`. Don't rely on any of
  them. See ADR 0002.
- Money is integer cents; never floats.
- Every domain model uses `BelongsToWorkspace` (ADR 0004). The scope relies on
  the already-resolved user, so every route touching workspace data must be
  behind the `auth` middleware (it runs before route-model binding).
- Target WCAG 2.2 AA: keyboard support, contrast, 320 px width.
- Tour steps target `data-tour="..."` attributes, never CSS classes.
