# 07. Security baseline

As the studio, I want strict browser security headers on every page, so that
a single injection bug can't turn into stolen sessions.

## Acceptance criteria

- [x] A Content-Security-Policy middleware with a per-request nonce, passed to
      Vite (`Vite::useCspNonce()`). Policy: `default-src 'self'`;
      `script-src 'self' 'nonce-…'`; `style-src 'self'`;
      `img-src 'self' data:`; `font-src 'self'`; `connect-src 'self'`;
      `object-src 'none'`; `base-uri 'self'`; `form-action 'self'`;
      `frame-ancestors 'none'`.
- [x] No inline styles or scripts without the nonce. React's `style` prop is
      allowed because it uses the CSSOM, not inline style attributes.
- [x] It works alongside the portfolio's root `.htaccess` header
      (`frame-ancestors 'none'`), which also applies to the app. Checked on
      production with curl and the browser console (no violations).
- [x] Any other security header the portfolio doesn't already send is added
      in the app's `public/.htaccess`, without duplicates.
- [x] Locally (Vite dev server) the policy allows the dev server, so
      development still works.
- [x] Session cookies stay Secure, HttpOnly and SameSite=Lax, scoped to
      `/mission-control` (already true; covered by a test).

## Tests

- [x] Every HTML response has the CSP header with a fresh nonce, and the
      nonce appears on the Vite script tags.
- [x] Two requests get different nonces.
- [x] JSON responses (deploy endpoint) don't need the CSP but keep `nosniff`.
