# 01. Workspaces, roles and core data

As the studio, I want every user, organization and project to belong to a
workspace, so that each demo visitor's data (and the real studio's) is
isolated from everyone else's (ADR 0004).

## Acceptance criteria

- [x] `workspaces` table: name, `is_sandbox`, `expires_at` (nullable).
- [x] `users` gain `workspace_id`, `role` (`admin` or `client`, a PHP backed
      enum) and `organization_id` (required for clients, null for admins,
      enforced in validation and a model guard).
- [x] `organizations` table: workspace, name, website URL, `archived_at`.
- [x] `projects` table: workspace, organization, name, description, `phase`
      (enum: `scope`, `palette`, `proofmark`, `launch`, `launched`), target
      launch date, `archived_at`.
- [x] A `BelongsToWorkspace` trait adds a global scope that filters by the
      signed-in user's workspace and fills `workspace_id` on create. With no
      signed-in user (console, scheduler) there is no implicit scope.
- [x] Deleting a workspace deletes everything in it (foreign keys cascade).
- [x] Factories for every model, with states for admin and client users.

## Tests

- [x] A user in workspace A never sees workspace B's organizations or
      projects, even by ID.
- [x] `workspace_id` is set automatically and can't be mass-assigned.
- [x] A client without an organization is rejected.
- [x] Deleting a workspace removes its users, organizations and projects.

## Out of scope

Screens (stories 03 to 05) and policies (story 04).
