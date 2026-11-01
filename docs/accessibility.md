# Accessibility

Mission Control targets **WCAG 2.2 AA** in both portals, the public pages
and the autopilot. This page records how that's checked and what the
Phase 7 audit (stories 35 and 36, October 2026) found.

## What runs in CI

Every push runs these in a real browser (`tests/Browser/AccessibilityTest.php`):

- **axe** on 26 studio, Launchpad and public pages, the login page with
  fresh demo credentials, the confirm-password page, the 2FA setup dialog
  and login challenge, and the autopilot bar mid-tour. All are clean at
  every axe level, so any new finding fails the build, minor ones
  included. A planted image without alt text proves the check works.
- **Reflow:** no studio or Launchpad page scrolls sideways at 320 px.
- **Esc** ends a running tour and puts focus on the page.
- The **autopilot tours** themselves (story 33) drive the real UI through
  the same buttons and fields a visitor uses.

axe is injected as a Playwright init script, so the tests run under the
same strict Content-Security-Policy as production.

## The manual audit

| Check                                    | How                                                                                                            | Result                                                                                                             |
| ---------------------------------------- | -------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Keyboard: order, visible focus, no traps | A Tab walk through 49 pages as studio, client and visitor, flagging any stop without a visible outline or ring | Every stop has visible focus; Tab always reaches the end of the page                                               |
| Keyboard flows                           | Pinning a comment (button, arrow keys, Enter, type, submit), the 2FA dialog, the tour bar                      | All work without a mouse; focus lands on the result (the new comment, the copied key's status, the page after Esc) |
| 320 px                                   | Every page at 320 × 800                                                                                        | One overflow, fixed (below)                                                                                        |
| 200% zoom                                | Every page at 640 px wide (a 1280 px window at 200%)                                                           | No overflow                                                                                                        |
| Screen reader                            | What a screen reader receives (accessibility tree, focus, live regions) in five flows, in Chrome               | All five flows pass (below); a listen-through with NVDA is still open                                              |

### Found and fixed

- Studio sidebar wasn't in a landmark; it's now the "Main" navigation.
- Palette Lab: the contrast table's scroll area had the same name as its
  section.
- 2FA setup dialog: the setup key had no label and no visible focus; the
  copy button had no name and no visible focus, and now says "Setup key
  copied"; the QR code had no text alternative.
- 2FA code and recovery code fields had no labels.
- "Remember me" and "Show archived" used a custom checkbox with a hidden
  mirror input that browsers complained about; they're native checkboxes
  now, like every other checkbox in the app.
- Launchpad's Proofmark page scrolled sideways by 3 px at 320 px (the
  round list could grow wider than its column).
- Settings pages: an `h2` "Settings" came before the page's `h1`; the
  layout title is now plain text.
- Ending a tour while focus was on its bar left focus nowhere; it now
  moves to the page's main content (the login page's `main` wasn't
  focusable before).

### Screen reader spot check

Checked in Chrome's accessibility tree, following focus and live
regions through each flow (November 2026):

- **Log in:** "Take the controls" puts the new demo logins in a polite
  status region ("Your demo studio is ready for 24 hours…"), and focus
  moves to "Log in". Every field has its label.
- **Waiting on you:** a region named "Waiting on you" with a level 2
  heading; each item is a link whose name says what to do ("Review design
  round v2"), with its module and due date as text. Project progress is
  written out ("Step 3 of 4: Proofmark, current").
- **Accept a proposal:** after accepting, focus moves to the status
  heading, which reads "v2 · Accepted by Anna de Vries".
- **Pin a comment:** "Add comment" moves focus to a crosshair that
  explains its keys; Enter moves focus to the comment field, labelled
  with the spot ("New comment at 60% across, 7% down"); after submitting,
  "Comment pinned." is announced and focus is on the new comment.
- **Autopilot captions:** a region named "Autopilot" holds the caption
  in an `aria-live="polite"`, `aria-atomic` element, so each caption is
  read whole without interrupting; Esc ends the tour.

This checks what a screen reader is given, not how it sounds. A
listen-through with NVDA is still worth doing (in the backlog).

## Known limits

- Design images in Proofmark are the studio's uploads; their content
  can't be described automatically. Each image has the design's name as
  its text, and every comment pin is a button named by its number and
  author.
- The autopilot's fake cursor is decorative and hidden from assistive
  technology; captions carry the story.
