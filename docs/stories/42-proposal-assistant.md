# 42. Navigator, the proposal assistant

As the studio, I want Claude to draft a proposal from the client's intake
and our own price list, so a first version takes a minute instead of an
hour, and I still decide every line before it's sent.

This is Star Search's first proof project (Star Search story 05): an AI
feature in a real, live product, with the same tests and guardrails as
the rest of the app. Meagan approved it on 2026-11-08.

**Name (2026-11-18): Navigator.** A named sub-project inside Mission
Control, with its own emblem (a brass sextant over a star) and its own
line on the Switchboard ("Navigator · part of Mission Control"). Its
review panel is built in the star-chart look from the Studio Redesign
plan (Claude Doc "Studio Redesign: Visual Plan",
https://claude.ai/artifact/VDRGUfcs8qgr1HNx1MdRRM). The button text stays
plain ("Draft with AI") so studios know what it does.

**Redesign (approved 2026-11-18, mock-up
https://claude.ai/artifact/6sRehQSsWjW7GwFEvo5HTv):** the story is built;
the redesign restyles the review panel and adds one thing:

- [x] A draft totals readout (subtotal, VAT, total) for the suggested
      lines at their drafted quantities, worked out by the presenter with
      `ProposalTotals` and formatted with `Money::format`. No money math
      in TypeScript, so it doesn't change when a line is switched off;
      the proposal's own totals update once the lines are added.
- [x] Keep becomes a switch (`role="switch"`), and the hint reads "Keep,
      change or switch off each line".

## Acceptance criteria

- [x] On a draft proposal whose project has a submitted intake, the
      studio sees "Draft with AI". It sends the intake (goals, pages,
      features, budget band, deadline) and the workspace's active
      line items (name, description, kind, unit price, default quantity)
      to the Claude API from the server, with tool use: the model can
      only choose line items by id, set quantities, and write an
      introduction. It never sets prices.
- [x] The answer is validated before anything is saved: unknown or
      archived ids are dropped, quantities go through `QuarterQuantity`,
      prices come from the library, and totals from `ProposalTotals`, as
      for hand-made lines. Each suggested line shows why ("Intake: menu
      pages for each season"), and the studio can keep, change or remove
      it before saving. Nothing is sent to the client automatically.
- [x] The call is server-side only, through Laravel's HTTP client to the
      fixed API host (not SafeFetcher, which is for user-supplied URLs).
      The API key lives in the production config secret; Meagan adds it
      (Claude never sees or edits secrets). Without a key the button is
      hidden.
- [x] Limits for a public demo on shared hosting: a timeout under the
      host's PHP limit, at most 3 drafts per sandbox and a daily cap for
      the whole demo, and a plain message (not an error page) when a
      limit or the API is unavailable.
- [x] A monthly spending cap: each call's cost is worked out from the
      answer's token counts at Sonnet 5.5's prices ($2 per million tokens
      in, $10 out), kept in integer micro-dollars, and added to the
      month's total. At the cap (default $10 a month, a config value)
      "Draft with AI" is switched off for everyone until the next month,
      with a plain message, and Meagan gets one email saying so (not one
      per refused draft). Without a mail setting the app logs the warning
      instead of failing.
- [x] Activity records "AI draft created" with the number of lines; the
      prompt and the raw answer are not stored.
- [x] Accessible like the rest of Scope: a status message while drafting,
      focus to the first suggested line after, keyboard-only review.
- [x] The autopilot gets a short chapter showing a draft being made and
      edited; the browser test fakes the API so CI never calls it.
- [x] An ADR records the design: tool use over free text, prices never
      from the model, server-side only, demo limits, the monthly cap and
      its email.

## Tests

- [x] Feature tests with `Http::fake()`: the request carries only the
      intake and library; invalid ids, quantities and prices in the answer
      are rejected; limits and a missing key; the monthly cap (cost
      counted, drafts stopped at the cap, one email with `Mail::fake()`);
      activity. A browser test for
      the review flow and the new tour chapter.

## Decisions (Meagan, 2026-11-18)

- **Model:** Claude Sonnet 5.5 (`claude-sonnet-5-5`). About 2–5 US cents a
  draft (a few thousand tokens in, one to two thousand out), so the cap
  covers about a hundred drafts a month. The app's cap is $4, under the
  $5 Console spend limit, so the app stops first and its email goes out
  (changed from $10 on 2026-11-18, when the Console limit was set).
- **Demo:** real API calls, with the per-sandbox and daily caps above and
  the monthly cap with an email.
- **A second safety net outside the app:** a monthly spend limit on the
  Anthropic Console account, set by Meagan, so a bug can't run up a bill.
- **Secrets Meagan adds herself:** the API key and the mail settings (an
  SMTP mailbox on codelaunch.nl), as five separate GitHub secrets set
  with `scripts\set-ai-secrets.ps1`; the deploy appends them to the
  production environment, so `APP_KEY` never changes. Claude never sees
  the values. Live since 2026-11-18; one real draft tested on the demo.
