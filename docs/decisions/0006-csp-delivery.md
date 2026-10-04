# 0006. Delivering the Content-Security-Policy

- Status: accepted
- Date: 2026-10-04

## Context

Story 07 adds a strict CSP with a per-request nonce, set as a response
header by `ContentSecurityPolicy` middleware. All tests passed, but on
production `curl -I` showed only `content-security-policy: frame-ancestors
'none'`: the portfolio's root `.htaccess` sets that header with
`Header always set`, and LiteSpeed lets it **replace** the header PHP sent.
The app's real policy never reached the browser.

The app can't change the portfolio's `.htaccess`, and a static header in the
app's own `.htaccess` can't carry a per-request nonce.

## Decision

Send the policy twice:

1. **`<meta http-equiv="Content-Security-Policy">`**, first in `<head>`:
   every directive except `frame-ancestors` (browsers ignore it in meta tags).
   This is what protects pages on codelaunch.nl.
2. **The response header**, with every directive: used locally, in CI and on
   any host that doesn't override it. On codelaunch.nl the portfolio's header
   (`frame-ancestors 'none'`) arrives instead.

Browsers enforce every policy they receive, so the result is the
intersection, which is never weaker than either one. This matches how the
portfolio itself delivers its CSP.

## Consequences

- `SecurityHeadersTest` checks that the meta tag carries the same nonce as
  the header, has no `frame-ancestors`, and comes before any script or
  stylesheet.
- A meta-delivered CSP can't use `report-uri`/`report-to`. Violations
  show up only in the browser console, which the Phase 7 audit checks.
- Lesson recorded: header behavior on the real host must be checked on the
  real host (`curl -I`) after deploy, not only in tests.
