# 35. Automated accessibility checks on every page

As a visitor using a keyboard or assistive technology, I want every page
of both portals, the public pages and the autopilot to pass automated
accessibility checks, and as the developer I want CI to keep it that way.

## Acceptance criteria

- [x] A browser test visits every page a visitor can reach (login, the
      sandbox welcome, studio home, each module page in Mission Control,
      Launchpad's home and each client module page, `/style-guide/{token}`,
      `/autopilot`, the settings pages) and runs axe,
      failing on serious and critical issues (WCAG 2.2 A and AA rules).
- [x] The autopilot bar is checked while a tour is running (captions,
      Pause, Next, Restart, Exit).
- [x] axe runs without weakening the CSP; if the plugin's way of loading
      axe is blocked, the test injects it in a way the CSP allows (test
      only, never in production).
- [x] Every issue axe finds is fixed, or, if it's a false positive,
      excluded by rule and element with a comment saying why.
- [x] The two backlog items are fixed: the 2FA setup dialog's code input
      gets a name and id, and the login page's hidden "Remember me" mirror
      input stops causing the console notice on production.
- [x] Runs in the existing "Autopilot tours (browser)" CI job; the job
      stays under 5 minutes.

## Tests

- [x] The axe sweep is the test. One test proves it catches a planted
      issue (an image without alt text), so a green run means something.

## Done notes

- `tests/Browser/AccessibilityTest.php`: 26 studio, Launchpad and public
  pages, the confirm-password page, the 2FA setup dialog (both steps), the
  2FA login challenge (code and recovery code) and the autopilot bar,
  paused mid-tour. Stricter than planned: every page was clean at every
  axe level once fixed, so **any** finding fails, minor included.
- The plugin injects axe as a Playwright init script, which the page's
  CSP doesn't apply to; the CSP stays exactly as in production.
- Found and fixed:
    - the studio sidebar wasn't in a landmark (now `navigation "Main"`);
    - Palette Lab's matrix scroll area had the same name as its section;
    - the 2FA setup dialog: the setup key field had no label, id or name
      (this was the console notice in the backlog, not the code input) and
      no visible focus; the copy button had no name and no visible focus
      and now announces "Setup key copied"; the QR code had no text
      alternative;
    - the 6-digit code fields (setup dialog and login challenge) and the
      recovery code field had no label.
- "Remember me" and the two "Show archived" filters are now native
  checkboxes, like the rest of the app's checkboxes, so Radix's hidden
  mirror input is gone, and so is `@radix-ui/react-checkbox`.
- Noted for story 36: on the settings pages an `h2` ("Settings") comes
  before the page's `h1`.
- Local tooling: with the `sockets` extension loaded, PHP 8.5.6's
  built-in web server (`php -S`, `artisan serve`) fails with internal
  class errors. Serve with a php.ini copy without `sockets` (CLAUDE.md).
