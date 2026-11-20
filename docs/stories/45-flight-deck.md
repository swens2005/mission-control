# 45 Flight deck: the admin dashboard

Studio Redesign, Mission Control step 3 (approved extra scope, 18 Nov 2026).
Mock-up: https://claude.ai/artifact/1ADg31HKjC9Jqn25GdvH3v (control room).

## Story

As the studio, when I open Mission Control I want to see every mission in
flight, what each client owes me and what just happened, so I know what to
do next without opening every project.

Today the dashboard is a placeholder ("Your studio's projects will appear
here"). The flight deck uses only data Mission Control already has.

## Acceptance criteria

- [x] A hero with a greeting ("Good morning, Sam", by the app's clock) and
      the date, one plain sentence about the studio, and the launch-site
      art beside it. Text sits on a solid panel, never on the art.
- [x] Four readouts: missions in flight (active, not launched), things
      waiting on clients, the next launch (date and project) and the
      total of proposals accepted this calendar month (frozen totals,
      formatted by `Money`, with the count).
- [x] Missions: active, not-launched projects, soonest target launch first
      (no date last). Each shows its client, a link to the project, the
      target launch, a five-step phase rail with the phase written out for
      screen readers, what's waiting on the client for it, and links to
      its four stations with their badges.
- [x] A Navigator note on a Scope project whose intake is in and whose
      draft proposal has no lines yet, when the assistant is configured,
      linking to its Scope page.
- [x] Waiting on clients: every item the clients' "Waiting on you" lists
      show, for the whole studio, with the client and module, linking to
      the admin project. Built from the existing `WaitingOnClient`
      providers (new `forOrganization()`), so the two can't drift.
- [x] Mission log: the 6 newest activity entries, linking to Activity.
- [x] Empty states that say what to do (no missions: "New project").
- [x] Only the admin's own workspace; archived projects never appear.
- [x] Feature tests for the props (order, counts, money, waiting items,
      Navigator note, isolation) and a unit test that `forOrganization()`
      matches what that client sees.
- [x] 1280 px and 320 px, keyboard order follows the reading order, no
      console errors; autopilot browser tests pass.
