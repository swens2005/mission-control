# 38. A real studio account in production

As Meagan, I want my own studio workspace in production, separate from
the demo sandboxes, so I can use Mission Control for real work.

## Acceptance criteria

- [x] An Artisan command, `studio:create`, creates a real (non-sandbox)
      workspace and its first admin. It asks for the workspace name, the
      admin's name and email, and the password (hidden input, typed
      twice, checked against the app's password rules).
- [x] It refuses to run if the email is already in use, and never
      prints the password.
- [x] It runs in-process from cPanel's Terminal (`php artisan
studio:create`), the manual fallback from ADR 0003; no new web
      endpoint, no secret in CI, nothing in the repo with the details.
- [x] The new workspace is never touched by `sandbox:prune` or the demo
      limits, and its users don't get the sandbox guardrails.
- [x] An ADR records why a one-off CLI command and not a setup page or a
      seeder.
- [x] Click-by-click steps for Meagan to run it in cPanel are in the
      story's done notes.

## Tests

- [x] Feature tests for the command: creates the workspace and admin,
      refuses a taken email, refuses mismatched or weak passwords, and the
      result survives `sandbox:prune`.

## Done notes

- `app/Console/Commands/CreateStudio.php`, ADR 0012. The email is
  lower-cased and trimmed; the demo domain (`sandbox.codelaunch.nl`) is
  refused so a real account can't be mistaken for a demo.
- Tests (`tests/Feature/CreateStudioTest.php`): creates the workspace and
  admin; the admin can log in and the studio isn't a sandbox; refuses a
  taken email, the demo domain, mismatched passwords, and (with the
  production rules) a weak password; never prints the password; survives
  `sandbox:prune` 30 days later.

### Steps for Meagan (cPanel, about 3 minutes)

1. Log in to cPanel. In the search box at the top, type **Terminal** and
   open it (under Advanced). Accept the warning if it shows one.
2. Type `cd ~/mission-control-app` and press Enter.
3. Type `php -v` and press Enter. It should say PHP 8.5. If it doesn't,
   use `/opt/alt/php85/usr/bin/php` instead of `php` in the next step.
4. Type `php artisan studio:create` and press Enter. Answer the five
   questions:
    - **Studio name:** for example your business name.
    - **Your name** and **Your email address** (your real one; this is
      how you log in).
    - **Password**, twice. Nothing appears while you type; that's on
      purpose. It needs 12+ characters with upper and lower case, a
      number and a symbol. A password manager is ideal.
5. It answers `Created "…". Log in as …`. If it lists a problem instead,
   nothing was created; run step 4 again.
6. Type `clear` and press Enter, then close the Terminal tab.
7. Log in at https://codelaunch.nl/mission-control/login with that email
   and password. You'll see an empty studio, with no demo banner.
8. Optional, recommended: Settings → Security → turn on two-factor
   authentication.
