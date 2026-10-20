# 25. Scope: the intake questionnaire

As a client, I want to tell the studio what I need in one short form; as the
studio, I want those answers in one place before I write a proposal.

## Acceptance criteria

- [x] A project has one **intake**. The studio opens it from the project page
      ("Scope") at `/admin/projects/{project}/scope` and clicks **Ask the
      client**, which makes it visible in Launchpad.
- [x] The client fills it in at `/client/projects/{project}/scope`:
    - **goals** (free text, required);
    - **pages** (a list: add, rename and remove rows, at least one);
    - **features** (tick boxes from a fixed list: contact form, online
      shop, booking, blog or news, newsletter, multiple languages, search,
      plus an "Other" text field);
    - **budget range** (native `<select>`: under €2,500, €2,500–5,000,
      €5,000–10,000, €10,000–20,000, over €20,000, not sure yet; stored as
      an enum, never as money);
    - **deadline** (optional date, not in the past, with an optional
      reason such as "Before Christmas").
- [x] **Save draft** keeps the answers without sending; **Send to the
      studio** submits them. A submitted intake is read-only for the
      client. The studio can **reopen** it (with an activity entry), which
      sends it back to the client.
- [x] The studio sees the answers read-only, with who sent them and when.
- [x] "Fill in the intake" appears in **Waiting on you** while the intake
      is asked for and not submitted (`WaitingOnClient`).
- [x] Asking, submitting and reopening are recorded in the activity log and
      visible to the client.
- [x] Forms use `FormField`, `focusFirstError`, unique ids for the page
      rows, and focus moves to the status heading after sending.
- [x] Cockpit look: `lc-card` sections with `lc-label`s ("● 01 · GOALS").
- [x] `data-tour`: `open-scope`, `ask-intake`, `intake-form`,
      `intake-pages`, `send-intake`, `intake-answers`.

## Tests

- [x] The client of the organization can save and submit; another
      organization gets 404; a client can't open the studio page (403).
- [x] Validation: goals required, at least one page, a known budget range,
      a deadline not in the past, sensible length limits.
- [x] A submitted intake can't be changed by the client until it's
      reopened. The client can't fill in an intake that wasn't asked for.
- [x] The waiting item appears and disappears with the intake's status.

## Done notes

- One endpoint saves and sends (`PUT /client/intakes/{intake}` with a
  `submit` flag). Inertia's `<Form>` doesn't pass the clicked button, so
  the buttons set a ref that `transform` reads. "Save draft" comes first in
  the DOM, so Enter in a field saves instead of asking to send.
- Empty page rows are dropped before validation (they arrive as `null`).
- The studio's Scope page lives at `/admin/projects/{project}/scope`; the
  proposal (stories 27 to 29) goes on the same page.
