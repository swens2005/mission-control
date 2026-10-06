# 18. Proofmark: resolve comments and approve a round

As the studio, I want to work through the client's comments and mark them
resolved; as the client, I want to approve a round when the designs are
right, so we both know what will be built.

## Acceptance criteria

- [ ] An admin can **resolve** and **reopen** a comment on the round in
      review. Status is written out ("Open", "Resolved by Sam Visser") and
      the pin changes shape as well as color (filled versus outlined
      with a tick).
- [ ] The pin list can be filtered: All, Open, Resolved (native radio
      group).
- [ ] A client contact can **approve** the round in review. Approving asks
      for confirmation and says how many comments are still open ("2
      comments are still open; approving keeps them for the record").
      The approval stores the user, time and IP.
- [ ] An approved round is **locked**: no new comments, no resolving. It
      shows "v2 · Approved by Anna de Vries, 8 Oct" in both portals.
- [ ] After approval the waiting item disappears, and the studio's
      Proofmark page offers "Move to Launch Control", which changes the
      project's phase (the existing phase change, with its activity entry).
- [ ] Resolving, reopening and approving are recorded in the activity log
      and visible to the client.
- [ ] Focus stays on the resolve button after it saves, and moves to the
      round status after approving.
- [ ] `data-tour`: `resolve-comment`, `pin-filter`, `approve-round`,
      `move-to-launch`.

## Tests

- [ ] Resolve and reopen; a client can't resolve (403).
- [ ] Approve: only a client of the organization, only the round in
      review, only once; another organization gets 404.
- [ ] An approved round refuses comments and resolving.
- [ ] Moving to Launch Control works only after approval.
