# 0014. The proposal assistant: tool use, library prices, hard limits

- Status: accepted (Meagan, 2026-11-08 and 2026-11-18)
- Date: 2026-11-18

## Context

Story 42 adds "Draft with AI": Claude turns a client's intake into a
first draft of a proposal. It is the first feature in Mission Control
that calls a paid outside API, from a public demo, on shared hosting
with no queue worker (ADR 0002). Money in a proposal must stay exact
(ADR 0010).

## Decision

- **One call, one strict tool.** The server sends one Messages API
  request to Claude Sonnet 5.5 (`claude-sonnet-5-5`, API version
  `2026-10-21`) with one tool, `propose_lines`, marked `strict`. The
  model can only answer with library ids, quantities, a reason per
  line and an introduction. Sonnet 5.5 doesn't accept a forced
  `tool_choice`, so it is `auto` and the prompt names the tool. Effort is
  `low`: picking lines from a short list doesn't need more, and it keeps
  the call well under the timeout.
- **Prices never come from the model.** The request shows prices so the
  model can respect the budget band, but the answer has no price field.
  Every suggested id must be an active item in the studio's own library
  (the workspace scope applies), every quantity passes `QuarterQuantity`,
  duplicates and anything else are dropped and counted. Lines are added
  at the library's price, and totals come from `ProposalTotals`, like a
  hand-made line.
- **The studio decides.** A draft is kept in the session, not saved. The
  studio keeps, changes or unticks each line and chooses whether to use
  the introduction; only then are lines added. Nothing goes to the
  client automatically. Activity records the number of lines added; the
  prompt and the raw answer are not stored.
- **Only the intake and the library leave the server.** No names,
  contact details, organization or other projects. The intake is the
  client's own text; the system prompt says to treat it as information,
  never as instructions, and the worst it can do is a wrong suggestion
  the studio reviews.
- **Laravel's HTTP client to a fixed host,** not `SafeFetcher`, which is
  for user-supplied URLs (ADR 0007). The key is `ANTHROPIC_API_KEY` in
  the production secret; without it the button is hidden. The timeout
  is 25 seconds, under the host's PHP limit.
- **Refusals fall back on the server** (`fallbacks: "default"`, beta
  `server-side-fallback-2026-07-01`); a refusal that still ends the call
  is a plain "no usable draft" message.
- **Limits, cheapest first:** 5 drafts a minute per user, 3 per demo
  sandbox, 50 a day for the whole demo, and a monthly cap for everyone
  (default $10, `AI_MONTHLY_CAP_DOLLARS`). Each limit is a plain message
  on the page, never an error page.
- **Cost in whole numbers.** Each call's cost is worked out from the
  answer's token counts at Sonnet 5.5's prices: 2 micro-dollars per input
  token and 10 per output token, stored as integer micro-dollars in
  `ai_drafts`. That table is a ledger, not workspace data: it has no
  workspace scope, and a pruned sandbox leaves its rows (workspace set to
  null) so the month's spend and the daily demo count stay right. If the
  model changes, `TokenCost` changes with it.
- **One email at the cap.** The call that takes the month to the cap
  inserts the month into `ai_cap_notices`; only the insert that wins
  sends the email to `AI_CAP_EMAIL`. Without an address, or if mail
  fails, the warning is logged and the draft still succeeds.
- **A second net outside the app:** a monthly spend limit on the
  Anthropic Console account, set by Meagan.

## Consequences

- A bug can't price a proposal: the worst case is a wrong suggestion the
  studio unticks.
- The demo costs at most the cap a month, and the Console limit backs it.
- A draft is lost if the session ends before it's added; drafting again
  costs one more call.
- Tests and CI never call the API: `Http::fake()` answers in the feature
  and browser tests.
