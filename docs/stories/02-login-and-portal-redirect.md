# 02. Login and portal redirect

As a studio admin or a client contact, I want one login page that takes me to
my own portal, so I never see the other side's screens.

## Acceptance criteria

- [ ] One login page (the starter kit's Fortify login, restyled in story 03).
- [ ] After login, admins land on `/admin` (Mission Control) and clients on
      `/client` (Launchpad).
- [ ] An `EnsurePortal` middleware guards each route group: a client opening
      any `/admin` URL gets 403, and so does an admin opening `/client`.
- [ ] `/` sends a signed-in user to their own portal and a guest to the login
      page. The starter kit's `/dashboard` is removed.
- [ ] Account settings (profile, password, two-factor) work for both roles,
      inside their own portal.
- [ ] Login stays rate limited (Fortify: 5 attempts per minute per email and
      IP).

## Tests

- [ ] Admin login redirects to `/admin`; client login redirects to `/client`.
- [ ] Every `/admin` route returns 403 for a client, and vice versa (one test
      loops over the registered routes, so new routes are covered
      automatically).
- [ ] Guests are redirected to login from both portals.
- [ ] The sixth failed login within a minute is throttled.
