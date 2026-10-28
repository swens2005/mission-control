# 31. Autopilot: "Watch it fly" and the studio tour

As a hiring manager, I want one link that shows me the best of Mission
Control in about three minutes, without signing up.

## Acceptance criteria

- [x] A public page `/autopilot` (the target of codelaunch.nl's "Watch
      it fly" button, Phase 7) with a short intro and a choice:
    - **The full story** (about 4 minutes, story 32);
    - **The studio tour** (about 3 minutes);
    - **The client tour** (about 2 minutes, story 32);
    - **Chapters:** Scope, Palette Lab, Proofmark, Launch Control.
      Nothing plays until the visitor picks one (one click; no autoplay).
- [x] Every tour starts like "Take the controls": the cursor clicks it on
      the login page, which makes a fresh sandbox, then types the demo
      email and password and logs in for real. Restart makes a new
      sandbox, because the previous run changed the old one.
- [x] **The studio tour** visits the four modules in the order a project
      moves (Scope, Palette Lab, Proofmark, Launch Control), with one
      moment each:
    - Scope: the library, then the v2 draft with Anna's change request
      and the totals console;
    - Palette Lab: the codelaunch.nl kit's contrast matrix and **Fix it**
      on the failing screen label;
    - Proofmark: the Wedding cakes round with its pins and the pin list;
    - Launch Control: **Run checks** against codelaunch.nl and the go
      board lighting up.
- [x] Chapters run one module's part of the studio tour after the login.
- [x] At the end: "That's the studio. Take the controls, or watch the
      full story." The visitor keeps the logged-in sandbox.
- [x] The intro page uses `PublicLayout`, is keyboard friendly, and gets
      `noindex` like the public style guide.
- [x] `data-tour` hooks added where a step needs one that doesn't exist
      yet (listed in the done notes).

## Tests

- [x] Pest: `/autopilot` renders for guests with the tour list; a signed-in
      visitor is offered to continue in their sandbox or start fresh.
- [x] The studio tour and each chapter run end to end in the browser
      test (story 33).

## Done notes

- A signed-in visitor isn't offered to continue in their old sandbox: a
  tour always starts with `POST /autopilot/fresh` (logs out, opens the
  login page), because an earlier run may have changed the demo data.
  The page tells them so. This replaces "continue or start fresh".
- New engine pieces for this: a `fresh` step, targets matched by their
  text (project links differ per sandbox only by id), `type(target, null)`
  to retype a prefilled value (the generated demo credentials), and a
  longer wait for the live Launch checks (45 s).
- New `data-tour` hooks: `login-email`, `login-password`, `nav-projects`,
  `project-link`, `changes-request`, `fix-panel`, `apply-fix`, and
  `start-{tour id}` on the autopilot page.
- Index pages are reached with `visit()` (Wayfinder URLs, the same in
  every sandbox and with the subfolder prefix); the sidebar is a sheet on
  small screens, so clicking it wouldn't work everywhere.
- Checked in Chrome: the full studio tour (70 steps, live checks against
  codelaunch.nl: 9 of 10 pass), the chapters, Pause/Resume, Next, a real
  key press pausing, a reload holding the tour, Esc, 320 px with reduced
  motion.
