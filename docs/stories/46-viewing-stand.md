# 46 Viewing stand: Launchpad in daylight

Studio Redesign, Mission Control step 4.
Mock-up: https://claude.ai/artifact/1ADg31HKjC9Jqn25GdvH3v (viewing stand).

## Story

As a client, when I open Launchpad I want to see at a glance how far each
of my projects has come and what the studio needs from me, in a page that
feels like watching my own launch from the stand.

## Acceptance criteria

- [x] The client portal sits under one day sky: the header is light (no
      navy bar), with the rocket mark on a cream tile, "Launchpad", and
      pill links ("Your projects", "Account"), the current one on a cream
      pill. The sky fades from `--sky` to the page color behind header and
      content.
- [x] Home hero: "Mission briefing · {organization}", "Your projects", the
      sentence, and the launch-site scene beside it (the approved liftoff
      scene with the viewing stand). Text never sits on the art. The art
      stacks under the text below 860 px.
- [x] Waiting on you: cream cards with an amber top edge, an amber label
      with lamp ("Proofmark · project"), a green link with an arrow, the due
      date. The `waiting-item` tour hook stays on the link. The empty state
      says nothing needs them (lit lamp, written out).
- [x] Projects: each card shows the rocket for its phase (Scope: blueprint;
      Palette Lab and Proofmark: being built; Launch Control: fueled;
      Launched: liftoff), "Project 01", the name (link, `client-project`
      hook), target launch, a five-step rail (Launched is the fifth step)
      and one plain sentence about what's happening now, plus what's
      waiting on them for that project.
- [x] One shared phase rail component for Launchpad and the flight deck:
      an ordered list, each step's state written out for screen readers
      ("done", "current", "to come"), `aria-current="step"`, and the
      one-line summary on phones. It replaces `PhaseSteps`, also on the
      client project page.
- [x] The phase sentence comes from the server (`ProjectPhase`), so the
      wording lives in one place.
- [x] Colors only through tokens; every new pair in `ThemeContrastTest`.
- [x] Feature tests for the new props (five steps, the sentence); the
      existing client tests and autopilot browser tests still pass.
- [x] 1280 px and 320 px, keyboard focus visible on the light header, no
      console errors.
