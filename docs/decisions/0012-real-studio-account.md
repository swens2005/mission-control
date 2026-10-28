# 0012. Creating a real studio account without SSH

- Status: accepted
- Date: 2026-10-28

## Context

Until now every workspace in production was a demo sandbox (ADR 0004),
deleted after 24 hours. Meagan needs one real, permanent studio with
herself as its admin. There's no registration page (on purpose: a public
sign-up would let anyone create a studio), no SSH in the deploy pipeline,
and nothing about her account (email, password) should end up in the
repository or in CI logs. Options considered:

- **(a) A seeder** run by the deploy. The details would have to live in
  the repo or in a CI secret, and a seeder that runs on every deploy needs
  guards against running twice.
- **(b) A one-time setup page** (a secret URL or token that creates the
  first admin). A new public endpoint that can create admins, even if it
  disables itself afterwards; the riskiest option for a one-off job.
- **(c) An Artisan command run by hand** from cPanel's Terminal, which
  ADR 0003 already names as the manual fallback.

## Decision

Option (c): `php artisan studio:create`
(`app/Console/Commands/CreateStudio.php`).

- It asks for the studio name, the admin's name and email, and the
  password twice, hidden. Nothing is passed on the command line, so
  nothing lands in the shell history.
- It validates with the app's own password rules (in production: 12+
  characters, mixed case, numbers, symbols, not in a known breach), a
  unique email, and refuses the demo sandbox email domain.
- It creates a workspace with `is_sandbox = false` and no expiry, and an
  admin with a verified email, in one transaction. On any error nothing
  is created.
- It runs in-process with PHP's CLI, so it doesn't need `proc_open`.

## Consequences

- No new endpoint, no new secret, no account details in git or CI.
- `sandbox:prune` and the sandbox guardrails only act on
  `is_sandbox = true`, so the real studio is never deleted or limited.
- Further studio users (more admins, client contacts) are added from the
  app itself, as in any studio.
- If cPanel's Terminal can't hide typed input, Symfony falls back to
  visible input; the steps say to clear the terminal afterwards.
- Tests: `tests/Feature/CreateStudioTest.php`.
