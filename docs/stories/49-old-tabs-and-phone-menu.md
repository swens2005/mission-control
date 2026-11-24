# 49 Old tabs after a deploy, and the phone menu

Found on 24 November 2026 in Trajectory (its story 73), which took its
skeleton from Mission Control. Both apps had the same two faults.

## Story

As anyone with Mission Control open, I want a tab left open across a deploy
to keep working, and the phone menu to close when I pick a page, so the app
never ignores a click or hides the page I asked for.

## Acceptance criteria

- [x] **An old tab after a deploy** reloads itself on the next click. The
      cause: Inertia's asset version came from the asset URL, which
      production sets for its subfolder and which never changes, so the
      "new build, reload" answer (409) never came and the tab asked for
      page files that were gone. The version is now the build's manifest.
- [x] **Safety net.** A page file that fails to load reloads the tab, at
      most once a minute so a truly missing file can't cause a loop.
- [x] **Phone menu.** Picking a page in the phone menu (a page, the logo,
      Settings) closes the menu. It stayed open over the new page.
- [x] The autopilot tours still pass: they visit pages while the tour's
      layer is open.
- [x] Checked on the live site at desktop and 320 px, console clean.

## Tests

- [x] Feature test: an Inertia request from an old build gets 409, also
      with an asset URL set; the current build's version passes.
