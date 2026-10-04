# 0001. Product name and portals

- Status: accepted
- Date: 2026-10-04

## Context

The plan used "Launchpad" as a placeholder product name and "Mission Control"
for the admin portal. The repo was already called `mission-control`.

## Decision

- The product is **Mission Control**.
- The admin portal (studio staff, `/admin`) is **Mission Control**.
- The client portal (client contacts, `/client`) is **Launchpad**.
- Starter-kit auth features kept: login, password reset, two-factor
  authentication and password confirmation. Removed: public registration
  (clients are invited, demo users are created by the sandbox), email
  verification and passkeys. Fewer screens to secure and test.

## Consequences

One app, two portals, one user table with a role. Names fit the
codelaunch.nl "missions" story: the studio runs Mission Control, the client
watches their site leave the Launchpad.
