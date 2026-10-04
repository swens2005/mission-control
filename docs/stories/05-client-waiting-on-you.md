# 05. "Waiting on you" (client home)

As a client contact, I want my home page to show my projects and anything
waiting on me, so I know what to do without reading emails.

## Acceptance criteria

- [ ] `/client` lists the client's organization's active projects, each with
      a four-step phase indicator: Scope, Palette, Proofmark, Launch.
- [ ] The indicator is a real ordered list with text ("Step 2 of 4: Palette,
      current"), not color alone.
- [ ] A "Waiting on you" section is fed by a small `WaitingOnClient` service
      that modules register items with later. For now it returns nothing and
      the page shows a friendly empty state.
- [ ] A project detail page (`/client/projects/{project}`) shows the phase,
      target launch date and description.
- [ ] Clients see only their own organization's projects. Anything else
      returns 404.

## Tests

- [ ] A client sees their own organization's active projects only: no
      archived projects, other organizations or other workspaces.
- [ ] A project from another organization returns 404.
- [ ] Unit test: `WaitingOnClient` returns registered items, sorted by due
      date.
