# 38. A real studio account in production

As Meagan, I want my own studio workspace in production, separate from
the demo sandboxes, so I can use Mission Control for real work.

## Acceptance criteria

- [ ] An Artisan command, `studio:create`, creates a real (non-sandbox)
      workspace and its first admin. It asks for the workspace name, the
      admin's name and email, and the password (hidden input, typed
      twice, checked against the app's password rules).
- [ ] It refuses to run if the email is already in use, and never
      prints the password.
- [ ] It runs in-process from cPanel's Terminal (`php artisan
studio:create`), the manual fallback from ADR 0003; no new web
      endpoint, no secret in CI, nothing in the repo with the details.
- [ ] The new workspace is never touched by `sandbox:prune` or the demo
      limits, and its users don't get the sandbox guardrails.
- [ ] An ADR records why a one-off CLI command and not a setup page or a
      seeder.
- [ ] Click-by-click steps for Meagan to run it in cPanel are in the
      story's done notes.

## Tests

- [ ] Feature tests for the command: creates the workspace and admin,
      refuses a taken email, refuses mismatched or weak passwords, and the
      result survives `sandbox:prune`.
