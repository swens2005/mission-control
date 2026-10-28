# Lighthouse

Measured on production (`https://codelaunch.nl/mission-control`) on
28 October 2026, story 37. Accessibility, best practices and SEO come
from Lighthouse; speed comes from Chrome performance traces with mobile
throttling (Fast 4G, 4× slower CPU, 412 px phone), because the Lighthouse
build in the Chrome tooling used here reports Core Web Vitals through
traces instead of a single performance score.

## Scores

Accessibility / Best practices / SEO, mobile and desktop:

| Page                   | Before         | After (mobile)  | After (desktop) |
| ---------------------- | -------------- | --------------- | --------------- |
| Login                  | 100 / 100 / 91 | 100 / 100 / 100 | 100 / 100 / 100 |
| `/autopilot`           | 100 / 100 / 54 | 100 / 100 / 100 | 100 / 100 / 100 |
| Studio: Launch Control | 100 / 100 / 91 | 100 / 100 / 100 | 100 / 100 / 100 |
| Launchpad home         | not measured   | 100 / 100 / 100 | 100 / 100 / 100 |

## Speed (throttled mobile)

| Page           | Metric            | Before                  | After                                |
| -------------- | ----------------- | ----------------------- | ------------------------------------ |
| Login          | LCP, first visit  | 1.8 s                   | 1.8 s                                |
| Login          | CLS               | 0                       | 0                                    |
| Launch Control | LCP, first visit  | 3.4 s                   | 2.3 s                                |
| Launch Control | LCP, repeat visit | (every file re-checked) | 1.2 s, all 20 build files from cache |
| Launch Control | CLS               | 0.102                   | 0                                    |

Good is LCP under 2.5 s and CLS under 0.1.

## What changed

- **Caching.** The portfolio's root `.htaccess` sends
  `Cache-Control: no-cache` for every `.js` and `.css`, which is right for its own files
  but made browsers re-check every Mission Control build file on every
  page. The build files have a content hash in their names, so
  `public/.htaccess` now caches them for a year (`immutable`). The
  post-deploy smoke test checks the header on the real host.
- **Layout shift.** The fonts were only discovered after the CSS loaded;
  when JetBrains Mono swapped in, Launch Control's big countdown digits
  changed size. The three Latin fonts are now preloaded.
- **SEO.** Every page has a meta description; `/autopilot` and the login
  page can be indexed. The public style guide stays `noindex`, and the
  portals are behind a login.

## Left as is

- **Render delay.** Most of the first-visit LCP is the JavaScript
  starting up: Inertia renders on the client. Server-side rendering would
  need a Node process next to PHP, which the shared host doesn't run
  (ADR 0002). The page code is already split per page and preloaded in
  one round trip.
- **HTML is never cached** (`no-cache, private`): pages are per user and
  carry a fresh CSP nonce and CSRF token.
- **Text compression** is on already (Brotli for JS, gzip for CSS).
