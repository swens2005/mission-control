# 06. Activity log

As the studio, I want one audit trail across all modules, so I can see who
did what and when. Clients see the parts that concern them.

## Acceptance criteria

- [ ] `activity_log` table: workspace, actor (nullable for system events), the
      subject it's about, event name (e.g. `project.created`), JSON
      properties, `visible_to_client`, `created_at`.
- [ ] An `Activity::record()` service is used by stories 04 and 05 (create,
      update, archive) and by every later module.
- [ ] Entries are append-only: the model refuses updates and deletes, and no
      route can change them. They disappear only when their workspace is
      deleted.
- [ ] **Admin:** `/admin/activity` shows the whole workspace, newest first,
      filterable by project. Each project page shows its own entries.
- [ ] **Client:** a project page shows only the entries marked
      `visible_to_client`.
- [ ] Times are shown relative ("2 hours ago") with the exact time in a
      `<time datetime>` element.

## Tests

- [ ] Creating, editing and archiving an organization or project records the
      right event and actor.
- [ ] Updating or deleting an entry throws an exception.
- [ ] Clients never see entries that aren't `visible_to_client`, or entries
      from other organizations.
