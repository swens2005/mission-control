# 22. Palette Lab: type pairing and a modular scale

As a studio admin, I want to pick the heading and body fonts and generate
a modular type scale, so the build gets consistent sizes.

## Acceptance criteria

- [ ] The kit has a **heading font** and a **body font**, chosen from the
      fonts the app self-hosts (Bricolage Grotesque, Figtree, JetBrains
      Mono) plus system serif, sans and mono stacks. No third-party font
      loading (CSP and privacy).
- [ ] A **modular scale**: base size (px), ratio (minor third 1.2, major
      third 1.25, perfect fourth 1.333, golden 1.618, or custom) and the
      number of steps up and down. Ratios are stored as integer
      thousandths.
- [ ] Sizes come from a pure function, `App\Support\Typography\TypeScale`,
      rounded to whole pixels and also given in rem.
- [ ] A **specimen** shows each step with its name (`xs` to `4xl`), size
      and a sample line in the chosen font.
- [ ] `data-tour`: `type-scale`, `type-specimen`.

## Tests

- [ ] Unit: the scale for each named ratio, rounding, rem conversion,
      steps below the base, and limits (no 0 px or 400 px sizes).
- [ ] Only listed fonts are accepted.
