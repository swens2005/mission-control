/**
 * Autopilot tours (ADR 0011): scripts of steps that drive the real UI.
 * Targets are data-tour names, never CSS classes.
 */

/** Which of the elements with a data-tour name: by its text, or by order. */
export type Match = { text?: string; nth?: number };

export type Step =
    | { kind: 'caption'; text: string }
    | { kind: 'moveTo'; target: string; match?: Match }
    /** text null: retype the field's current value (e.g. demo credentials). */
    | { kind: 'type'; target: string; text: string | null; match?: Match }
    | { kind: 'click'; target: string; match?: Match }
    | { kind: 'visit'; url: string }
    | { kind: 'waitFor'; target: string; match?: Match; timeoutMs?: number }
    | { kind: 'chapter'; title: string }
    /** Logs out of any demo and opens the login page, for a new sandbox. */
    | { kind: 'fresh' };

export type Tour = {
    id: string;
    /** Whole tours, or one module's chapter, on the autopilot page. */
    group: 'tour' | 'chapter';
    title: string;
    /** Shown on the autopilot page, e.g. "About 3 minutes". */
    duration: string;
    description: string;
    steps: Step[];
};

/** Small helpers so tour scripts read like a story. */
export const caption = (text: string): Step => ({ kind: 'caption', text });
export const moveTo = (target: string, match?: Match): Step => ({
    kind: 'moveTo',
    target,
    match,
});
export const type = (
    target: string,
    text: string | null,
    match?: Match,
): Step => ({ kind: 'type', target, text, match });
export const click = (target: string, match?: Match): Step => ({
    kind: 'click',
    target,
    match,
});
export const visit = (url: string): Step => ({ kind: 'visit', url });
/** Waits for the target to appear (after a request, say). */
export const waitFor = (
    target: string,
    match?: Match,
    timeoutMs?: number,
): Step => ({ kind: 'waitFor', target, match, timeoutMs });
export const chapter = (title: string): Step => ({ kind: 'chapter', title });
export const fresh = (): Step => ({ kind: 'fresh' });
