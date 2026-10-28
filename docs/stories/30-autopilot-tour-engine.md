# 30. Autopilot: the tour engine

As a visitor from codelaunch.nl, I want to watch the app run itself, with
captions, and take over whenever I like; as the developer, I want tours
to be short scripts that drive the real UI.

## Acceptance criteria

- [ ] A tour is a TypeScript script of steps (ADR 0011):
    - `caption(text)`: shows a caption and waits long enough to read it;
    - `moveTo(target)`: moves the fake cursor to an element;
    - `type(target, text)`: types into a field, character by character;
    - `click(target)`: clicks an element (a real click, real requests);
    - `visit(url)`: an Inertia visit;
    - `waitFor(target | condition)`: waits until something appears (for
      example after a request), with a timeout.
      Targets are `data-tour` names only, never CSS classes.
- [ ] The engine lives above the Inertia pages (in `app.tsx`), so a tour
      keeps running across page visits, logins and the sandbox's "View as
      client" switch. Its position is kept in `sessionStorage`, so a reload
      resumes or cleanly offers to resume.
- [ ] **Controls** in a fixed bar (`lc-hud` look): Pause/Resume, Next,
      Restart, Exit, with the step count ("Step 7 of 31") and the current
      chapter. `Esc` exits and hands control to the visitor. Any click or
      key press by the visitor outside the bar pauses the tour ("You took
      over. Resume?").
- [ ] **Captions** go to an `aria-live="polite"` region, as text in the bar
      (no speech, no sound, no flashing).
- [ ] **Cursor:** an SVG arrow positioned with React's `style` prop
      (CSP-safe, no `<style>` tags), `aria-hidden`. It animates between
      targets; the target gets a visible ring while it's acted on.
- [ ] **Reduced motion:** with `prefers-reduced-motion`, the cursor jumps
      instead of gliding, typing and scrolling are instant, and captions
      still wait for reading time.
- [ ] **Speed:** caption and step timings come from one setting, shared
      as an Inertia prop; tests run it at 0 (story 33).
- [ ] When a target doesn't appear in time, the tour pauses with a plain
      message ("Lost my way at step 12. Take the controls, or restart.")
      instead of failing silently.
- [ ] Works at 320 px (the bar wraps; captions stay readable) and in both
      portals' looks.

## Tests

- [ ] Vitest (pure TypeScript): the runner plays steps in order, pauses,
      resumes, skips with Next, restarts, stops on Esc; timings scale with
      the speed and collapse under reduced motion; a missing target
      times out into the "lost" state. The DOM and the clock are injected,
      so the tests need no browser.
- [ ] Vitest: every `data-tour` name used by any tour script exists in the
      app's source, so a renamed attribute fails the build.
- [ ] Pest: the speed prop is shared, and is 0 in testing.
