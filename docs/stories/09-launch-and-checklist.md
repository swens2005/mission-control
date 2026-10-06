# 09. Launch Control: the launch and its manual checklist

As a studio admin, I want each project to have a launch with a site URL and a
pre-flight checklist that the studio and the client work through together,
so nothing gets forgotten on launch day.

## Acceptance criteria

- [x] A project has at most one **launch**: the site URL to check (http or
      https only, validated). Created from the project page ("Launch
      Control"), at `/admin/projects/{project}/launch`. The launch uses the
      project's own target launch date rather than keeping a second one.
- [x] Creating a launch adds the default **manual checklist**: 404 page,
      favicon, backups (studio); forms tested, DNS TTL lowered, analytics
      consent (client). Each item has an **owner role** and a short hint.
- [x] Admins can tick and untick any item, and add or remove items. Clients
      see the checklist in Launchpad (`/client/projects/{project}/launch`)
      and can tick only the client-owned items.
- [x] Ticking records who and when ("Ticked by Anna de Vries, 6 Oct").
- [x] Each tick and untick is recorded with `Activity::record()` and is
      visible to the client; adding and removing items is studio-only.
- [x] Open client-owned items appear in "Waiting on you" via
      `WaitingOnClient` (one item per launch, "3 items left"), due on the
      project's target date. Launched and archived projects are left out.
- [x] Tables: `launches`, `checklist_items`, both with `BelongsToWorkspace`.
      `ChecklistItemPolicy` reuses `AdminOfWorkspace` and
      `ProjectPolicy::viewAsClient`; a studio item gives a client 403,
      anything outside their organization 404.
- [x] Checklist items are a real list of native checkboxes with visible
      labels; owner role is shown as text, not color alone. Focus stays on
      the checkbox after it saves.
- [x] Demo sandboxes can only set the URL to codelaunch.nl (ADR 0007).
- [x] `data-tour` attributes: `open-launch-control`, `launch-url`,
      `launch-checklist`, `checklist-item`.
- [x] **Demo:** the demo client's project "Online pre-orders" is now the
      Launch-phase project ("Repair booking" moved to Proofmark), so the
      client can take part. Its launch checks `https://codelaunch.nl`, with
      three items ticked and two client items waiting on the demo client.

## Tests

- [x] Admin creates a launch; the default checklist is created with owners.
- [x] URL validation rejects non-http(s) schemes and garbage.
- [x] A client can tick their own items, gets 403 on studio items, and 404 on
      other organizations' launches.
- [x] Another workspace's launch returns 404 for an admin.
- [x] Open client items show up in "Waiting on you"; ticked ones don't.
- [x] A fresh sandbox has the demo launch and checklist.
