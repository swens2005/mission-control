# 20. Palette Lab: a brand kit with colors in OKLCH

As a studio admin, I want to define a project's colors in OKLCH, so I can
reason about lightness directly and get reliable hex values for the build.

## Acceptance criteria

- [x] A project has one **brand kit**, at `/admin/projects/{project}/palette`,
      opened from the project page ("Palette Lab").
- [x] A kit has **colors**: a name ("Ink", "Lime"), a **role** (text,
      surface, accent, or shape only) and a value. Colors can be added,
      edited, reordered and removed.
- [x] Values are entered as **hex** (`#10233a`) or **OKLCH**
      (`oklch(0.27 0.05 255)`), and the kit always shows both.
- [x] OKLCH is stored as integers: lightness in hundredths of a percent
      (0 to 10 000), chroma in ten-thousandths (0 to 4 000), hue in
      hundredths of a degree (0 to 35 999). No floats in the database.
- [x] Conversion lives in a pure class, `App\Support\Color\Oklch`
      (OKLCH ↔ linear sRGB ↔ hex), next to `Contrast`. Colors outside sRGB
      are brought into gamut by lowering chroma only, and the kit says so
      ("adjusted to fit the screen").
- [x] Swatches use the stored hex through React's `style` prop (CSP-safe).
      Every swatch also shows its name, role and values as text.
- [x] Writes are recorded with `Activity::record()` (studio-only).
- [x] Cockpit look: the kit as a row of `lc-card` swatch cards with
      `lc-label`s ("● 01 · INK · TEXT").
- [x] `data-tour`: `open-palette`, `color-list`, `add-color`, `color-value`.

## Tests

- [x] Unit: hex → OKLCH → hex round trips for the codelaunch.nl colors;
      known reference values (white, black, pure sRGB red/green/blue);
      out-of-gamut colors keep their lightness and hue.
- [x] Hex and OKLCH input both save; garbage is refused.
- [x] Another workspace gets 404; a client gets 403 on the studio page.
