# 0003. Running migrations without SSH

- Status: accepted
- Date: 2026-10-04

## Context

After each upload the production database may need `php artisan migrate`.
The deploy pipeline has no SSH. Options considered:

- **(a) cPanel Terminal by hand:** works, but a person has to remember to do it
  after every deploy.
- **(b) A secret-protected endpoint that CI calls after uploading.**
- **(c) A cron job that runs pending migrations:** up to a cron interval of
  delay, and nobody notices when a migration fails.

## Decision

Option (b): `POST /_deploy/migrate` (`app/Http/Controllers/DeployController.php`).

- Authenticated by a bearer token compared with `hash_equals` against
  `DEPLOY_TOKEN`. An empty token disables the endpoint.
- Answers 404 to a wrong or missing token, so it doesn't advertise itself.
- Rate limited to 5 requests per minute per IP.
- Loaded outside the `web` middleware group: no session, cookies or CSRF.
- Runs `Artisan::call('migrate', ['--force' => true])` in-process, which works
  even if `proc_open` is disabled on the host.
- Returns the migration output, and HTTP 500 if it fails, so the CI job fails
  visibly.

Option (a) stays as the manual fallback.

## Consequences

Deploys are fully automatic: push to `main`, CI checks, uploads, migrates and
smoke-tests. The token is a credential and is stored only in GitHub secrets
and the production environment secret. Tests: `tests/Feature/DeployTest.php`.
