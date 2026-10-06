# 24. Palette Lab: the client's style guide, approval, and the demo

As a client, I want to see my brand kit as a live style guide and approve
it; as a demo visitor, I want to see Palette Lab working on a real palette.

## Acceptance criteria

- [ ] The studio **shares** the kit with the client. Launchpad shows a
      read-only **style guide** at `/client/projects/{project}/palette`:
      swatches with names and values, the pairs that pass (with grades),
      and the type specimen.
- [ ] A **public read-only link** (`/style-guide/{token}`, no login) can be
      turned on and off by the studio, so the client can pass it to their
      own team. Random 40-character token, `noindex`, nothing editable, and
      a new token each time it's turned on.
- [ ] The client **approves** the kit (confirmation, stores user, time and
      IP). An approved kit is locked; "Start a revision" unlocks it and
      clears the approval, with an activity entry.
- [ ] "Approve the brand kit" appears in **Waiting on you** while a shared
      kit isn't approved (`WaitingOnClient`).
- [ ] **Demo:**
    - an in-house client, "Orbit Web Studio (in-house)", with a project
      "codelaunch.nl" whose kit is the app's own palette from
      `themes.css`: ink, cream, card, lime (shape only), green (text),
      sky, and the console screen colors, with the CV site's original
      label blue, so "Fix it" has its demo moment;
    - Bakkerij de Vries gets a project in the Palette phase with a shared
      kit waiting for Anna's approval.
- [ ] `data-tour`: `share-kit`, `style-guide`, `approve-kit`,
      `public-link`.

## Tests

- [ ] The client sees only shared kits of their own organization (404
      otherwise); the public link works without login only while it's on,
      and shows nothing editable.
- [ ] Approval: once, only by the organization's client, locks the kit;
      a revision clears it. The waiting item follows.
- [ ] A fresh sandbox has both demo kits; the codelaunch.nl kit has
      exactly one failing pair, which "Fix it" resolves.
