# 0004. Demo data: a sandbox per visitor

- Status: accepted
- Date: 2026-10-04

## Context

The public demo ("Watch it fly" and "Take the controls") lets anyone log in.
Options: one shared demo workspace reset nightly, or a fresh sandbox per
visitor, deleted after 24 hours.

## Decision

A **sandbox per visitor**. Starting the demo creates a `workspace` with its
own admin and client users, organizations and projects, copied from a seed
template. Every domain model belongs to a workspace and is scoped to it. A
scheduled command deletes sandboxes older than 24 hours, along with their
uploads.

The deciding reason: the "full story" autopilot tour has the admin send a
proposal and the client accept it. With one shared workspace, two visitors
watching at the same time would change each other's data and break each
other's tours. Per-visitor sandboxes make every tour run start from the same
known state.

## Consequences

- A `workspace_id` on every domain table from Phase 1, and authorization that
  checks the workspace as well as the organization. Cheap now, painful later.
- Sandbox creation must be fast (a few inserts) and rate limited per IP.
- Disk use for uploads is bounded by the 24-hour cleanup.
- The real studio workspace (Meagan's own) is just another workspace that is
  never cleaned up.
