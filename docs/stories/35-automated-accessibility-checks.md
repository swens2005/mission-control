# 35. Automated accessibility checks on every page

As a visitor using a keyboard or assistive technology, I want every page
of both portals, the public pages and the autopilot to pass automated
accessibility checks, and as the developer I want CI to keep it that way.

## Acceptance criteria

- [ ] A browser test visits every page a visitor can reach (login, the
      sandbox welcome, studio home, each module page in Mission Control,
      Launchpad's home and each client module page, `/style-guide/{token}`,
      `/autopilot`, the settings pages) and runs axe with
      `assertNoAccessibilityIssues()`, failing on serious and critical
      issues (WCAG 2.2 A and AA rules).
- [ ] The autopilot bar is checked while a tour is running (captions,
      Pause, Next, Restart, Exit).
- [ ] axe runs without weakening the CSP; if the plugin's way of loading
      axe is blocked, the test injects it in a way the CSP allows (test
      only, never in production).
- [ ] Every issue axe finds is fixed, or, if it's a false positive,
      excluded by rule and element with a comment saying why.
- [ ] The two backlog items are fixed: the 2FA setup dialog's code input
      gets a name and id, and the login page's hidden "Remember me" mirror
      input stops causing the console notice on production.
- [ ] Runs in the existing "Autopilot tours (browser)" CI job; the job
      stays under 5 minutes.

## Tests

- [ ] The axe sweep is the test. One test proves it catches a planted
      issue (an image without alt text), so a green run means something.
