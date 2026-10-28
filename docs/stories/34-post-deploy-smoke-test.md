# 34. A browser smoke test after every deploy

As the developer, I want each deploy checked in a real browser on the
real site, because subfolder and `.htaccess` header problems only show
up there (backlog; ADRs 0002 and 0006).

## Acceptance criteria

- [ ] After the deploy job, a CI step opens
      `https://codelaunch.nl/mission-control/login` in Playwright and:
    - clicks "Take the controls" and logs in (one sandbox per deploy,
      pruned after 24 hours like any other);
    - opens one page per module in the studio portal, then "View as
      client" and Launchpad's home;
    - checks every client-side URL it follows starts with
      `/mission-control/`;
    - fails on any CSP violation or JavaScript error in the console.
- [ ] Short and quiet: about a minute, nothing printed about the server
      except pass or fail per page. No secrets needed (it uses the public
      demo).
- [ ] A failure marks the run red after the files are already live; the
      step says so plainly, so the fix is a new commit, not a rollback.

## Tests

- [ ] The smoke test runs once against production from this story's own
      deploy and passes.
