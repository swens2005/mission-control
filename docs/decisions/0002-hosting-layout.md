# 0002. Hosting layout: a subfolder of codelaunch.nl

- Status: accepted
- Date: 2026-10-04

## Context

The app runs on Namecheap shared hosting (cPanel, LiteSpeed, PHP 8.5,
MariaDB 11.4), next to the static portfolio in `public_html/`. It must live at
`https://codelaunch.nl/mission-control` (no subdomain). There is no SSH; CI
deploys over FTPS. Laravel's code, `vendor/`, `storage/` and `.env` must never
be inside the web root.

## Decision

Split each release into two upload folders:

| Server path (from the cPanel home) | Contents                                                       | Web-accessible |
| ---------------------------------- | -------------------------------------------------------------- | -------------- |
| `~/mission-control-app/`           | The whole app except `public/`, plus `.env`                    | No             |
| `~/public_html/mission-control/`   | The contents of `public/` (`index.php`, `.htaccess`, `build/`) | Yes            |

`public/index.php` looks for `../bootstrap/app.php` (true locally and in CI)
and otherwise uses `../../mission-control-app`, then calls
`$app->usePublicPath(__DIR__)` so Vite's manifest is found.

Other parts of the decision:

- **Assets:** built in CI with `ASSET_URL=https://codelaunch.nl/mission-control`,
  so every asset and font URL includes the subfolder.
- **Session cookie:** `SESSION_PATH=/mission-control`, so the app's cookies
  are never sent to the portfolio.
- **Production `.env`:** the whole file is one GitHub secret,
  `ENV_MISSION_CONTROL_PROD`. The deploy job writes it into the app folder. No
  one edits `.env` on the server, and it is never committed. The template is
  `.env.production.example`.
- **No config or route caching:** stale caches would survive an FTPS upload
  (the cache files are not part of the release), and the app is small enough
  that OPcache is plenty.

## Consequences

- The portfolio's own FTPS deploy only deletes files it uploaded itself, so it
  leaves `public_html/mission-control/` alone. It must never be switched to
  `dangerous-clean-slate`.
- The portfolio's root `.htaccess` (HTTPS redirect, HSTS and the other security
  headers) also applies to the app. Phase 1 decides how the app's own CSP
  combines with the inherited `frame-ancestors 'none'` header.
- Locally the app runs at `/`, not `/mission-control`. The deploy job's smoke
  test covers the subfolder.
