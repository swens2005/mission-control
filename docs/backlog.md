# Backlog

Ideas that are out of scope for the current phase. Each module gets 3 to 5
stories at most; anything extra lands here instead of in the build.

- Run the app under `/mission-control` locally too, and add a post-deploy browser smoke test (log in through the demo) to CI. Two subfolder bugs so far only showed up on production: the CSP header override (ADR 0006) and route prefixes (ADR 0002).
- Starter-kit 2FA setup dialog: the code input has no name/id (browser warning); fix in the Phase 7 accessibility audit.
- Login page: Radix's hidden mirror input for "Remember me" has no autocomplete attribute (Chrome autofill issue); fix in the Phase 7 accessibility audit.
- Email invitations for client contacts (needs working mail on the host; story 04 shows a one-time password instead).
- Dark mode for both portals (the starter kit's appearance toggle is removed in story 03).
- A real, non-sandbox studio workspace for Meagan in production, and a way to create its admin without SSH.
- Launch Control: full page weight (CSS, JS, images and fonts), not just the HTML document. Needs many more outbound requests per run.
- Launch Control: re-run checks on a schedule (cron) and notify when something regresses after launch.
- Proofmark: replies on comments (a thread per pin). Phase 3 keeps one comment per pin, resolved or open.
- Proofmark: compare two rounds side by side, and carry open comments over to the next round.
- Proofmark: PDF and video uploads (images only for now, see story 15).
- Palette Lab: a P3 (wide gamut) preview next to the sRGB swatch, for colors that had to be adjusted.
- Palette Lab: compare the brand kit against the live site's CSS (would reuse SafeFetcher).
