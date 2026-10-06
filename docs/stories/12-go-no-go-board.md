# 12. The go/no-go board

As the studio and the client, we each want to sign off before launch, and
see one clear GO only when everything is really ready.

## Acceptance criteria

- [x] A **go/no-go board** on the launch page, styled in the deep-space
      colors of the admin theme, with one row per area: automated checks,
      studio checklist, client checklist, studio sign-off, client sign-off.
- [x] The board shows **CLEAR** when every automated check in the latest run
      passes or is waived, and every checklist item is ticked. Warnings count
      as not clear unless waived.
- [x] When the board is clear, the studio (any admin) and the client (any
      contact of the organization) can each **sign off** by typing their
      name. The sign-off stores the user, name typed, time and IP.
- [x] **GO** appears only when the board is clear and both roles have signed.
      Otherwise it shows **NO-GO** with the reasons as a list.
- [x] If anything stops being clear (a new run fails, an item is unticked, a
      waiver is withdrawn), existing sign-offs are **voided**, kept in the
      history, and recorded in the activity log.
- [x] Once GO, an admin can mark the project **launched**, which moves the
      project to the Launched phase.
- [x] "Sign off the launch" appears in the client's "Waiting on you" when the
      board is clear and the client hasn't signed.
- [x] Status is text first ("NO-GO: 2 checks failing"), with color as a
      second signal; the board's colors pass AA in `ThemeContrastTest`.
- [x] `data-tour`: `go-board`, `sign-off`, `go-status`, `mark-launched`.
- [x] **Demo:** after the visitor runs the checks against codelaunch.nl and
      ticks the remaining items, they can sign as studio, switch to the
      client with "View as client", sign there, and see GO.

## Tests

- [x] Unit: the board's state for every combination (failing check, waived
      check, open item, missing sign-off).
- [x] Sign-off is refused while not clear, and a client of another
      organization gets 404.
- [x] A failing new run voids existing sign-offs.
- [x] Marking launched is refused unless GO.
- [x] The sign-off waiting item appears and disappears at the right times.

## Notes

- `GoNoGo::evaluate()` is a pure function of the latest run, waivers,
  checklist and active sign-offs; unit tested for every combination.
- The typed name must match the signer's account name (spaces and case
  ignored); the sign-off stores the user, typed name, time and IP.
- Sign-offs are voided (kept, with the reason) after a check run that isn't
  clear, an unticked or added item, a withdrawn waiver or a new site URL.
- Only one signature per side. After signing or launching, focus moves to
  the board's status so keyboard users aren't dropped at the top.
- Board colors: new `--space-*` tokens per portal (deep space with lime in
  Mission Control, riso ink with teal and pink in Launchpad), in
  `ThemeContrastTest`.
