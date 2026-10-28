# 31. Autopilot: "Watch it fly" and the studio tour

As a hiring manager, I want one link that shows me the best of Mission
Control in about three minutes, without signing up.

## Acceptance criteria

- [ ] A public page `/autopilot` (the target of codelaunch.nl's "Watch
      it fly" button, Phase 7) with a short intro and a choice:
    - **The full story** (about 4 minutes, story 32);
    - **The studio tour** (about 3 minutes);
    - **The client tour** (about 2 minutes, story 32);
    - **Chapters:** Scope, Palette Lab, Proofmark, Launch Control.
      Nothing plays until the visitor picks one (one click; no autoplay).
- [ ] Every tour starts like "Take the controls": the cursor clicks it on
      the login page, which makes a fresh sandbox, then types the demo
      email and password and logs in for real. Restart makes a new
      sandbox, because the previous run changed the old one.
- [ ] **The studio tour** visits the four modules in the order a project
      moves (Scope, Palette Lab, Proofmark, Launch Control), with one
      moment each:
    - Scope: the library, then the v2 draft with Anna's change request
      and the totals console;
    - Palette Lab: the codelaunch.nl kit's contrast matrix and **Fix it**
      on the failing screen label;
    - Proofmark: the Wedding cakes round with its pins and the pin list;
    - Launch Control: **Run checks** against codelaunch.nl and the go
      board lighting up.
- [ ] Chapters run one module's part of the studio tour after the login.
- [ ] At the end: "That's the studio. Take the controls, or watch the
      full story." The visitor keeps the logged-in sandbox.
- [ ] The intro page uses `PublicLayout`, is keyboard friendly, and gets
      `noindex` like the public style guide.
- [ ] `data-tour` hooks added where a step needs one that doesn't exist
      yet (listed in the done notes).

## Tests

- [ ] Pest: `/autopilot` renders for guests with the tour list; a signed-in
      visitor is offered to continue in their sandbox or start fresh.
- [ ] The studio tour and each chapter run end to end in the browser
      test (story 33).
