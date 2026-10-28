# 37. Lighthouse on production

As a recruiter clicking the link, I want the app to load fast and score
well, and as the developer I want the scores on record.

## Acceptance criteria

- [x] Lighthouse (mobile and desktop) on production for: login,
      `/autopilot`, a studio page and a Launchpad page, in all four
      categories (performance, accessibility, best practices, SEO).
- [x] Reasonable fixes are made, within the hosting limits (shared host,
      no SSH, `.htaccess` owned by the portfolio) and without loosening the
      CSP. Likely candidates: font loading, code splitting per page,
      caching headers on built assets, a meta description and
      `robots` rules (the portals are `noindex`; `/autopilot` and login can
      be indexed).
- [x] Before and after scores, plus anything left unfixed and why, are
      recorded in `docs/lighthouse.md`.

## Tests

- [x] A feature test for any new meta or robots tags. Asset changes are
      covered by the build and the existing browser tests.

## Done notes

- Results and what's left: `docs/lighthouse.md`. Every measured page is
  now 100/100/100 (accessibility, best practices, SEO) on mobile and
  desktop.
- Hashed build files were `no-cache` (the portfolio's root `.htaccess`);
  now cached for a year, checked by the post-deploy smoke test.
- Launch Control's CLS (0.102) came from JetBrains Mono swapping in late;
  the three Latin fonts are preloaded and CLS is 0. First-visit LCP on
  throttled mobile went from 3.4 s to 2.3 s; repeat visits 1.2 s.
- `/autopilot` is indexable now (story 31 had made it `noindex` "like the
  style guide"; the style guide stays `noindex`).
- The Chrome tooling's Lighthouse doesn't produce a performance score,
  so speed was measured with throttled performance traces.
