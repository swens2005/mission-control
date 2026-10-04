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

## Patterns to reuse

- **Policies:** `AdminOfWorkspace` trait; other workspaces get
  `Response::denyAsNotFound()` (404), the wrong portal gets 403 from
  `EnsurePortal`. Client access to a project: `ProjectPolicy::viewAsClient`.
- **Controllers** pass explicit arrays to Inertia (see `present()` methods),
  never whole models.
- **Activity:** every meaningful write calls `App\Support\Activity::record()`
  with a name snapshot; set `visibleToClient: true` for things clients should
  see. Add new events to `ActivityEntry::description()`.
- **Client to-dos:** modules register providers with
  `App\Support\Waiting\WaitingOnClient` (feeds "Waiting on you").
- **Demo:** extend `App\Support\Sandbox\DemoTemplate` / `SandboxFactory` so
  every new module has believable demo data. Sandbox users are blocked from
  account changes by `SandboxGuardrails`.
- **Forms:** `FormField` (links hint and error with aria-describedby),
  `focusFirstError` on submit errors, native `<select>` with
  `selectClassName`. Dynamic breadcrumbs: `useBreadcrumbs()`.
- **Colors:** only through tokens in `resources/css/themes.css`;
  `ThemeContrastTest` checks every pair. Contrast math:
  `App\Support\Color\Contrast` (reuse it in Palette Lab).
- **Wayfinder:** import controllers per file
  (`@/actions/App/Http/Controllers/Admin/ProjectController`), not from the
  folder index.

## Gotchas (each one cost time in Phase 1)

- **CSP:** never add a library that injects `<style>` or `<script>` tags at
  runtime (sonner was removed for this). Radix gets the nonce via
  `setNonce` in `app.tsx`. On codelaunch.nl the CSP header is replaced by the
  portfolio's `.htaccess`; the `<meta>` copy is what protects pages
  (ADR 0006). Check the browser console for violations after UI changes.
- **Subfolder:** pages can look fine on production while client-side URLs
  are wrong. After a deploy, click through the real site in a browser, not
  only `curl`. Wayfinder's prefix comes from `APP_URL` in the deploy build.
- **Production MariaDB defaults to MyISAM.** Laravel forces InnoDB;
  `DatabaseEngineTest` guards it. CI mirrors the MyISAM default.
- **Model docblocks** use `Carbon\CarbonImmutable` (the app uses immutable
  dates); Larastan fails otherwise.
- **Factories** must set `workspace_id` from the parent record, never rely on
  the signed-in user (see `ProjectFactory`).
- **Shell:** a local hook blocks any command whose text contains `.env`
  (including `import.meta.env`); edit such files with the file editor. Long
  heredocs with quotes break in Git Bash; write files with the editor.
- **CI watching:** after pushing, wait for the _new_ run id before
  `gh run watch`, or you'll watch the previous run.
- **Local server without `.env`:** pass settings as environment variables
  (`APP_ENV=local APP_KEY=... DB_CONNECTION=sqlite DB_DATABASE=<file>`);
  Laravel refuses destructive commands when it thinks it's production.
