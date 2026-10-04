# 08. Demo sandbox

As a visitor from codelaunch.nl, I want to try the app straight away without
signing up, in my own copy that nobody else can mess up (ADR 0004).

## Acceptance criteria

- [ ] "Take the controls" on the login page (`POST /demo`) creates a sandbox
      workspace that expires in 24 hours. It contains one admin, one client,
      and seeded organizations and projects in different phases.
- [ ] The login form is then **pre-filled** with the sandbox admin's
      generated email and password, and the client's credentials are shown
      too. The visitor really logs in, which is the moment the autopilot
      (Phase 6) will type out.
- [ ] Sandbox users get emails like `demo-<id>@sandbox.codelaunch.nl` and
      random passwords. No email is ever sent.
- [ ] A sandbox banner offers "View as client" and "View as studio", which
      switches between the two sandbox users (needed for the full-story tour).
      It only switches within the same sandbox.
- [ ] **Guardrails:** sandbox users can't change their email or password,
      turn on two-factor, or delete their account. Those controls are hidden,
      and the routes return 403.
- [ ] **Limits:** 5 new sandboxes per IP per hour, and at most 200 active
      sandboxes in total (then a friendly "demo is busy" message).
- [ ] An hourly `sandbox:prune` command deletes expired sandboxes. It needs
      the cron job from `docs/production-setup.md` step 4.

## Tests

- [ ] `POST /demo` creates a complete sandbox and pre-fills the login form.
- [ ] Two sandboxes never see each other's data.
- [ ] The guardrail routes return 403 for sandbox users and work for real
      users.
- [ ] Switching identity is refused across sandboxes and for non-sandbox
      users.
- [ ] The rate limit and the 200-sandbox cap both apply.
- [ ] `sandbox:prune` deletes only expired sandboxes, with everything in them.
