# 04. Organizations and projects (admin)

As a studio admin, I want to manage client organizations, their contacts and
their projects, so every later module has a client and a project to work on.

## Acceptance criteria

- [ ] **Organizations:** list (search, paginated, archived hidden by
      default), create, edit, archive and unarchive. Fields: name, website
      URL.
- [ ] **Client contacts:** on an organization, an admin adds a contact (name,
      email). The app generates a temporary password and shows it once. There
      is no email sending on this host (email invites go to the backlog).
- [ ] **Projects:** list (filter by organization and phase), create, edit,
      archive. Fields: organization, name, description, phase, target launch
      date.
- [ ] Laravel policies for `Organization`, `Project` and client `User`: only
      admins in the same workspace. Anything else returns 404, not 403, so IDs
      from other workspaces don't leak.
- [ ] Form requests validate everything. Errors are announced to screen
      readers and linked to their fields.
- [ ] Empty states with a clear next action ("Add your first client").

## Tests

- [ ] Full CRUD as an admin.
- [ ] A client, or an admin from another workspace, gets 404 on every
      organization, project and contact route.
- [ ] Validation rules (required fields, URL format, unique contact email).
- [ ] The temporary password is shown once and stored hashed.
