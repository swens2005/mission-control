# 12. The go/no-go board

As the studio and the client, we each want to sign off before launch, and
see one clear GO only when everything is really ready.

## Acceptance criteria

- [ ] A **go/no-go board** on the launch page, styled in the deep-space
      colors of the admin theme, with one row per area: automated checks,
      studio checklist, client checklist, studio sign-off, client sign-off.
- [ ] The board shows **CLEAR** when every automated check in the latest run
      passes or is waived, and every checklist item is ticked. Warnings count
      as not clear unless waived.
- [ ] When the board is clear, the studio (any admin) and the client (any
      contact of the organization) can each **sign off** by typing their
      name. The sign-off stores the user, name typed, time and IP.
- [ ] **GO** appears only when the board is clear and both roles have signed.
      Otherwise it shows **NO-GO** with the reasons as a list.
- [ ] If anything stops being clear (a new run fails, an item is unticked, a
      waiver is withdrawn), existing sign-offs are **voided**, kept in the
      history, and recorded in the activity log.
- [ ] Once GO, an admin can mark the project **launched**, which moves the
      project to the Launched phase.
- [ ] "Sign off the launch" appears in the client's "Waiting on you" when the
      board is clear and the client hasn't signed.
- [ ] Status is text first ("NO-GO: 2 checks failing"), with color as a
      second signal; the board's colors pass AA in `ThemeContrastTest`.
- [ ] `data-tour`: `go-board`, `sign-off`, `go-status`, `mark-launched`.
- [ ] **Demo:** after the visitor runs the checks against codelaunch.nl and
      ticks the remaining items, they can sign as studio, switch to the
      client with "View as client", sign there, and see GO.

## Tests

- [ ] Unit: the board's state for every combination (failing check, waived
      check, open item, missing sign-off).
- [ ] Sign-off is refused while not clear, and a client of another
      organization gets 404.
- [ ] A failing new run voids existing sign-offs.
- [ ] Marking launched is refused unless GO.
- [ ] The sign-off waiting item appears and disappears at the right times.
