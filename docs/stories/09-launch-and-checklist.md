# 09. Launch Control: the launch and its manual checklist

As a studio admin, I want each project to have a launch with a site URL and a
pre-flight checklist that the studio and the client work through together,
so nothing gets forgotten on launch day.

## Acceptance criteria

- [ ] A project has at most one **launch**: the site URL to check (http or
      https only, validated) and a target launch date (defaults to the
      project's). Created from the project page ("Prepare launch"), at
      `/admin/projects/{project}/launch`.
- [ ] Creating a launch adds the default **manual checklist**: 404 page,
      favicon, forms tested, backups, DNS TTL lowered, analytics consent.
      Each item has an **owner role** (studio or client) and a short hint.
- [ ] Admins can tick and untick any item, and add or remove their own items.
      Clients see the checklist in Launchpad
      (`/client/projects/{project}/launch`) and can tick only the
      client-owned items.
- [ ] Ticking records who and when ("Ticked by Anna de Vries, 4 Oct").
- [ ] Each tick and untick is recorded with `Activity::record()`; client
      ticks are visible to the client.
- [ ] Open client-owned items appear in "Waiting on you" via
      `WaitingOnClient`, due on the launch's target date.
- [ ] Tables: `launches`, `checklist_items`, both with `BelongsToWorkspace`.
      Policies reuse `AdminOfWorkspace` and `ProjectPolicy::viewAsClient`.
- [ ] Checklist items are a real list of native checkboxes with visible
      labels; owner role is shown as text, not color alone.
- [ ] `data-tour` attributes: `launch-url`, `launch-checklist`,
      `checklist-item`.
- [ ] **Demo:** the "Repair booking" project (Launch phase) gets a launch for
      `https://codelaunch.nl` with three items already ticked, and a
      client-owned item waiting on the demo client.

## Tests

- [ ] Admin creates a launch; the default checklist is created with owners.
- [ ] URL validation rejects non-http(s) schemes and garbage.
- [ ] A client can tick their own items, gets 403 on studio items, and 404 on
      other organizations' launches.
- [ ] Another workspace's launch returns 404 for an admin.
- [ ] Open client items show up in "Waiting on you"; ticked ones don't.
- [ ] A fresh sandbox has the demo launch and checklist.
