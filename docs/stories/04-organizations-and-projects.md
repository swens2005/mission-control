# 04. Organizations and projects (admin)

As a studio admin, I want to manage client organizations, their contacts and
their projects, so every later module has a client and a project to work on.

## Acceptance criteria

- [x] **Organizations:** list (search, paginated, archived hidden by
      default), create, edit, archive and unarchive. Fields: name, website
      URL.
- [x] **Client contacts:** on an organization, an admin adds a contact (name,
      email). The app generates a temporary password and shows it once. There
      is no email sending on this host (email invites go to the backlog).
- [x] **Projects:** list (filter by organization and phase), create, edit,
      archive. Fields: organization, name, description, phase, target launch
      date.
- [x] Laravel policies for `Organization` and `Project` (contacts go through
      the organization): only admins in the same workspace. An admin from
      another workspace gets 404, not 403, so ids don't leak. Clients never
      get that far: the portal guard from story 02 answers 403 first.
- [x] Form requests validate everything. Errors are announced to screen
      readers and linked to their fields.
- [x] Empty states with a clear next action ("Add your first client").

## Tests

- [x] Full CRUD as an admin.
- [x] An admin from another workspace gets 404 on every organization,
      project and contact route; a client gets 403.
- [x] Validation rules (required fields, URL format, unique contact email).
- [x] The temporary password is shown once and stored hashed.
