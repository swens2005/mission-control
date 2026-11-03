# 0013. A one-time setup page for the first real studio

- Status: accepted (amends 0003 and 0012)
- Date: 2026-11-03

## Context

ADR 0012 creates the real studio with `php artisan studio:create` in
cPanel's Terminal. On this host that fails: "Shell access is not enabled
on your account". ADR 0003 named the same Terminal as the manual fallback
for migrations, so that fallback doesn't exist either.

Meagan chose a one-time web page over asking the host to enable shell
access. The token that unlocks it can't simply be a GitHub secret: all
production settings are one secret (`ENV_MISSION_CONTROL_PROD`), which
GitHub never shows again, so adding a line means re-pasting the whole
production config; and changing how the deploy passes secrets is
deliberately off-limits to Claude.

## Decision

`/setup` (`StudioSetupController`), sharing `StudioCreator` with
`studio:create`:

- **Open only while needed:** a token fingerprint is configured
  (`config/studio.php`) **and** no real (non-sandbox) studio exists.
  Otherwise 404. Once the first studio is created it is closed for good,
  whatever the token.
- **Fingerprint, not token:** Meagan makes a 256-bit random token on her
  own PC and keeps it; only its SHA-256 fingerprint is committed. A
  fingerprint can't be turned back into the token, so it's safe in a
  public repo. Neither the token nor the password passes through Claude.
- **Checked like the deploy token** (ADR 0003): `hash_equals`, one
  generic error, the token checked before anything else, 5 attempts a
  minute, guests only, CSRF as on every form.
- **Same rules as the command:** password rules, unique email, no demo
  domain, one transaction. The new admin is logged in.
- **Cleanup:** the fingerprint is removed from the code after use. Even if
  it weren't, the page stays closed.

## Consequences

- A public page that can create an admin exists for as long as the
  fingerprint is set and no real studio exists; in practice minutes.
  An attacker would need the 256-bit token.
- ADR 0003's manual fallback (Terminal) is not available on this host;
  migrations rely on the deploy endpoint alone.
- `studio:create` stays for hosts with shell access and for local use.
- Tests: `tests/Feature/StudioSetupTest.php`; the page is in the axe
  sweep.
