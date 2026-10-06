# 19. Proofmark in the demo

As a demo visitor, I want to find a believable design review waiting for
me, in both portals, without the demo using up the server's disk.

## Acceptance criteria

- [x] **Demo designs** are small WebP files shipped in the repo
      (`resources/demo/proofmark/`): mock pages for a bakery site, made by
      screenshotting simple HTML mockups in headless Chrome. Together under
      600 KB.
- [x] Seeded designs **point to the shipped files** instead of being copied
      into each sandbox, so a new sandbox adds no files to the disk. Only
      the visitor's own uploads count towards the quota, and only those are
      deleted by `sandbox:prune`. Shipped files are served by the same
      authorized route.
- [x] **Bakkerij de Vries** (the demo client's organization) gets a new
      project, "Wedding cakes", in the Proofmark phase. Round v1 is
      superseded, with three resolved comments. Round v2 is in review (home
      and order page, desktop and mobile), with two open comments from Anna
      and one from the studio. "Review design round v2" is waiting for the
      demo client.
- [x] "Repair booking" (Fietsatelier Noord) keeps its Proofmark phase with
      one draft round, so the studio side shows a draft too.
- [x] Every Proofmark screen has the `data-tour` hooks from stories 15 to
      18, ready for the Phase 6 autopilot.
- [x] **Fresh visitor check on production:** start the demo, upload a
      design, send it, switch to the client with "View as client", pin a
      comment with the keyboard, approve, switch back; no console or CSP
      errors, images load under `/mission-control`.

## Tests

- [x] A fresh sandbox has the Wedding cakes rounds, designs and comments,
      and the waiting item.
- [x] Creating a sandbox writes no files; pruning it leaves the shipped
      files alone.
- [x] Every shipped demo image passes the same validation as an upload.
