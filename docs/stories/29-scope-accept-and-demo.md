# 29. Scope: accept or ask for changes, and the demo

As a client, I want to accept a proposal formally, or say what should
change; as a demo visitor, I want to see Scope working on a real project.

## Acceptance criteria

- [x] The client can **accept** the latest sent version: they type their
      full name and tick "I accept this proposal, v2, for €X incl. VAT".
      The app stores the user, the typed name, the time and the IP (the
      client approval pattern from Proofmark and Palette Lab).
- [x] An accepted version is **immutable**: no "Make changes", no new
      version, and both portals show "v2 · Accepted by Anna de Vries,
      19 Oct" with the typed name.
- [x] The client can instead **ask for changes** with a note (required).
      The studio sees the note on the Scope page; the version shows
      "Changes requested", and "Make changes" is the next step.
- [x] After acceptance the studio's Scope page offers **Move to Palette
      Lab** (the existing phase change, with its activity entry).
- [x] The waiting item from story 28 becomes "Accept the proposal" and
      disappears after accepting or asking for changes.
- [x] Accepting and asking for changes are recorded in the activity log
      and visible to the client. Focus moves to the status heading
      afterwards.
- [x] **Demo** (`DemoTemplate` / `SandboxFactory`), on Bakkerij de Vries'
      "Seasonal menu pages":
    - a submitted intake from Anna (goals, pages, a December deadline);
    - **v1** sent, with Anna's note "Could you add the Easter pages
      too?";
    - **v2** as a draft with the Easter pages added, ready to send. The
      full-story tour sends it as the studio, then switches to Anna and
      accepts it.
    - the workspace's line-item library: about eight believable items
      (design, development per page, hourly content work, hosting setup,
      training…).
- [x] `data-tour`: `accept-proposal`, `accept-name`, `accept-confirm`,
      `request-changes`, `proposal-status`, `move-to-palette`.

## Tests

- [x] Accept: only a client of the organization, only the latest sent
      version, only once; a typed name and the tick box are required;
      user, name, time and IP are stored. Another organization gets 404.
- [x] An accepted version refuses changes and new versions.
- [x] Asking for changes needs a note and ends the waiting item.
- [x] Moving to Palette Lab works only after acceptance.
- [x] A fresh sandbox has the intake, v1 with changes requested, v2 as a
      draft, and the library; v2's totals match `ProposalTotals`.

## Done notes

- A version is answered once: accepting or asking for changes closes it.
  Only the latest sent version can be answered.
- If the client accepts while the studio has a newer draft open, the draft
  is locked (it can't be sent) and the Scope page shows the accepted
  version. Nothing is deleted.
- Besides the policies, the models refuse to change or delete an accepted
  proposal, and to change lines of a sent one (safety net). Sandbox
  pruning deletes through the database cascade, so it still works.
- The accept form sits under the document, so the client reads first; the
  status card has a "Go to accept" link and the print button.
- Demo: v1 €1,666.17 (changes requested), v2 €2,902.19 as a draft. A fresh
  sandbox has no Scope to-do for Anna until the studio sends v2 (agreed
  when the stories were written).
