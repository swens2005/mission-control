# 41. A one-time setup page for the real studio

As Meagan, I want to create my real studio account from a web page,
because the host has shell access switched off, so story 38's
`studio:create` can't be run in production.

## Acceptance criteria

- [x] `/setup` shows a form (setup token, studio name, your name, email,
      password twice) only while setup is **open**: a token fingerprint
      is configured and no real (non-sandbox) studio exists. Otherwise it
      answers 404, so it doesn't advertise itself.
- [x] The token is never stored or sent anywhere but the form: the code
      holds only its SHA-256 fingerprint, compared with `hash_equals`.
      Meagan makes the token on her own PC; neither the token nor the
      password passes through Claude.
- [x] A wrong token gets one generic error and creates nothing; attempts
      are rate limited (5 a minute).
- [x] The same rules as `studio:create` (one shared class): password
      rules, unique email, no demo-domain email, one transaction.
- [x] On success the new admin is logged in and lands in their empty
      studio. From then on `/setup` is 404 for good, even with the right
      token, because a real studio exists.
- [x] The page uses the login page's look, works by keyboard, passes axe
      and fits 320 px.
- [ ] An ADR records why (and amends ADR 0003 and 0012: cPanel Terminal
      isn't available on this host); the fingerprint is removed from the
      code once the studio exists.

## Tests

- [x] Feature tests: 404 without a fingerprint; 404 once a real studio
      exists (with the right token too); wrong token creates nothing;
      the right token creates the studio, logs in and closes setup;
      validation errors; rate limit; signed-in users are redirected.
- [x] The page in the axe browser sweep.
