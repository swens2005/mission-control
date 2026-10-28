# 37. Lighthouse on production

As a recruiter clicking the link, I want the app to load fast and score
well, and as the developer I want the scores on record.

## Acceptance criteria

- [ ] Lighthouse (mobile and desktop) on production for: login,
      `/autopilot`, a studio page and a Launchpad page, in all four
      categories (performance, accessibility, best practices, SEO).
- [ ] Reasonable fixes are made, within the hosting limits (shared host,
      no SSH, `.htaccess` owned by the portfolio) and without loosening the
      CSP. Likely candidates: font loading, code splitting per page,
      caching headers on built assets, a meta description and
      `robots` rules (the portals are `noindex`; `/autopilot` and login can
      be indexed).
- [ ] Before and after scores, plus anything left unfixed and why, are
      recorded in `docs/lighthouse.md`.

## Tests

- [ ] A feature test for any new meta or robots tags. Asset changes are
      covered by the build and the existing browser tests.
