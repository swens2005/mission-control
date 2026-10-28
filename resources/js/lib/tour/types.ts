/**
 * Autopilot tours (ADR 0011): scripts of steps that drive the real UI.
 * Targets are data-tour names, never CSS classes.
 */
export type Step =
    | { kind: 'caption'; text: string }
    | { kind: 'moveTo'; target: string }
    | { kind: 'type'; target: string; text: string }
    | { kind: 'click'; target: string; nth?: number }
    | { kind: 'visit'; url: string }
    | { kind: 'waitFor'; target: string; text?: string }
    | { kind: 'chapter'; title: string };

export type Tour = {
    id: string;
    title: string;
    /** Shown on the autopilot page, e.g. "About 3 minutes". */
    duration: string;
    description: string;
    steps: Step[];
};

/** Small helpers so tour scripts read like a story. */
export const caption = (text: string): Step => ({ kind: 'caption', text });
export const moveTo = (target: string): Step => ({ kind: 'moveTo', target });
export const type = (target: string, text: string): Step => ({
    kind: 'type',
    target,
    text,
});
export const click = (target: string, nth?: number): Step => ({
    kind: 'click',
    target,
    nth,
});
export const visit = (url: string): Step => ({ kind: 'visit', url });
/** Waits for the target to appear, optionally containing some text. */
export const waitFor = (target: string, text?: string): Step => ({
    kind: 'waitFor',
    target,
    text,
});
export const chapter = (title: string): Step => ({ kind: 'chapter', title });
