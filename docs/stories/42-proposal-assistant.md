# 42. The proposal assistant

As the studio, I want Claude to draft a proposal from the client's intake
and our own price list, so a first version takes a minute instead of an
hour, and I still decide every line before it's sent.

This is Star Search's first proof project (Star Search story 05): an AI
feature in a real, live product, with the same tests and guardrails as
the rest of the app. Meagan approved it on 2026-11-08.

## Acceptance criteria

- [ ] On a draft proposal whose project has a submitted intake, the
      studio sees "Draft with AI". It sends the intake (goals, pages,
      features, budget band, deadline) and the workspace's active
      line items (name, description, kind, unit price, default quantity)
      to the Claude API from the server, with tool use: the model can
      only choose line items by id, set quantities, and write an
      introduction. It never sets prices.
- [ ] The answer is validated before anything is saved: unknown or
      archived ids are dropped, quantities go through `QuarterQuantity`,
      prices come from the library, and totals from `ProposalTotals`, as
      for hand-made lines. Each suggested line shows why ("Intake: menu
      pages for each season"), and the studio can keep, change or remove
      it before saving. Nothing is sent to the client automatically.
- [ ] The call is server-side only, through Laravel's HTTP client to the
      fixed API host (not SafeFetcher, which is for user-supplied URLs).
      The API key lives in the production config secret; Meagan adds it
      (Claude never sees or edits secrets). Without a key the button is
      hidden.
- [ ] Limits for a public demo on shared hosting: a timeout under the
      host's PHP limit, at most 3 drafts per sandbox and a daily cap for
      the whole demo, and a plain message (not an error page) when a
      limit or the API is unavailable.
- [ ] A monthly spending cap: each call's cost is worked out from the
      answer's token counts at Sonnet 5.5's prices ($2 per million tokens
      in, $10 out), kept in integer micro-dollars, and added to the
      month's total. At the cap (default $10 a month, a config value)
      "Draft with AI" is switched off for everyone until the next month,
      with a plain message, and Meagan gets one email saying so (not one
      per refused draft). Without a mail setting the app logs the warning
      instead of failing.
- [ ] Activity records "AI draft created" with the number of lines; the
      prompt and the raw answer are not stored.
- [ ] Accessible like the rest of Scope: a status message while drafting,
      focus to the first suggested line after, keyboard-only review.
- [ ] The autopilot gets a short chapter showing a draft being made and
      edited; the browser test fakes the API so CI never calls it.
- [ ] An ADR records the design: tool use over free text, prices never
      from the model, server-side only, demo limits, the monthly cap and
      its email.

## Tests

- [ ] Feature tests with `Http::fake()`: the request carries only the
      intake and library; invalid ids, quantities and prices in the answer
      are rejected; limits and a missing key; the monthly cap (cost
      counted, drafts stopped at the cap, one email with `Mail::fake()`);
      activity. A browser test for
      the review flow and the new tour chapter.

## Decisions (Meagan, 2026-11-18)

- **Model:** Claude Sonnet 5.5 (`claude-sonnet-5-5`). About 2–5 US cents a
  draft (a few thousand tokens in, one to two thousand out), so the $10
  cap covers a few hundred drafts a month.
- **Demo:** real API calls, with the per-sandbox and daily caps above and
  the monthly cap with an email.
- **A second safety net outside the app:** a monthly spend limit on the
  Anthropic Console account, set by Meagan, so a bug can't run up a bill.
- **Secrets Meagan adds herself** to `ENV_MISSION_CONTROL_PROD`: the API
  key and the mail settings (an SMTP mailbox on codelaunch.nl). Claude
  gives click-by-click steps and never sees the values.
