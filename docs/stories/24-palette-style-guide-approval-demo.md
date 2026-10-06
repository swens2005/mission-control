# 24. Palette Lab: the client's style guide, approval, and the demo

As a client, I want to see my brand kit as a live style guide and approve
it; as a demo visitor, I want to see Palette Lab working on a real palette.

## Acceptance criteria

- [x] The studio **shares** the kit with the client. Launchpad shows a
      read-only **style guide** at `/client/projects/{project}/palette`:
      swatches with names and values, the pairs that pass (with grades),
      and the type specimen.
- [x] A **public read-only link** (`/style-guide/{token}`, no login) can be
      turned on and off by the studio, so the client can pass it to their
      own team. Random 40-character token, `noindex`, nothing editable, and
      a new token each time it's turned on.
- [x] The client **approves** the kit (confirmation, stores user, time and
      IP). An approved kit is locked; "Start a revision" unlocks it and
      clears the approval, with an activity entry.
- [x] "Approve the brand kit" appears in **Waiting on you** while a shared
      kit isn't approved (`WaitingOnClient`).
- [x] **Demo:**
    - an in-house client, "Orbit Web Studio (in-house)", with a project
      "codelaunch.nl" whose kit is the app's own palette from
      `themes.css`: ink, cream, card, lime (shape only), green (text),
      sky, and the console screen colors, with the CV site's original
      label blue, so "Fix it" has its demo moment;
    - Bakkerij de Vries gets a project in the Palette phase with a shared
      kit waiting for Anna's approval.
- [x] `data-tour`: `share-kit`, `style-guide`, `approve-kit`,
      `public-link`.

## Tests

- [x] The client sees only shared kits of their own organization (404
      otherwise); the public link works without login only while it's on,
      and shows nothing editable.
- [x] Approval: once, only by the organization's client, locks the kit;
      a revision clears it. The waiting item follows.
- [x] A fresh sandbox has both demo kits. The codelaunch.nl kit fails
      exactly where the app had to darken colors (the CV label blue on
      every surface, the brand green on the console screen: 5 pairs), and
      every Café corner text pair passes.

## Done notes

- The Bakkerij kit is on a new project, "Café corner" (Palette phase), so
  the demo client has something to do in Launch Control, Proofmark and
  Palette Lab.
- The in-house client is listed last, so the demo client's organization
  stays first in `DemoTemplate`.
- The public page uses its own layout (`public/` pages): the Launchpad bar
  and sky, no navigation. It sends `X-Robots-Tag: noindex, nofollow` and
  `Referrer-Policy: no-referrer`, and is throttled to 60 requests a minute.
