import { router } from '@inertiajs/react';
import type { TourAdapter } from './runner';

export type Point = { x: number; y: number };
export type Box = { top: number; left: number; width: number; height: number };

/** Every element carrying this data-tour name (story 30). */
export function tourElements(name: string): HTMLElement[] {
    return Array.from(
        document.querySelectorAll<HTMLElement>(
            `[data-tour="${CSS.escape(name)}"]`,
        ),
    );
}

function isVisible(element: HTMLElement): boolean {
    return element.getClientRects().length > 0;
}

/**
 * The browser adapter: real elements, real clicks and input events, an
 * Inertia visit. The cursor and ring are drawn by the provider through
 * the callbacks (positioned with the style prop, so CSP stays strict).
 */
export function domAdapter(draw: {
    cursor: (point: Point, durationMs: number) => void;
    ring: (box: Box | null) => void;
}): TourAdapter<HTMLElement> {
    return {
        find(name, text, nth = 0) {
            const matches = tourElements(name).filter(
                (element) =>
                    isVisible(element) &&
                    (text === undefined ||
                        (element.textContent ?? '').includes(text)),
            );

            return matches[nth] ?? null;
        },

        async moveTo(element, durationMs) {
            element.scrollIntoView({
                block: 'center',
                inline: 'nearest',
                behavior: durationMs > 0 ? 'smooth' : 'auto',
            });
            await sleep(durationMs > 0 ? 250 : 0);

            const rect = element.getBoundingClientRect();
            draw.ring({
                top: rect.top - 4,
                left: rect.left - 4,
                width: rect.width + 8,
                height: rect.height + 8,
            });
            draw.cursor(
                {
                    x: rect.left + Math.min(rect.width / 2, 40),
                    y: rect.top + rect.height / 2,
                },
                durationMs,
            );
            await sleep(durationMs);
        },

        async type(element, text, perCharMs) {
            if (
                !(element instanceof HTMLInputElement) &&
                !(element instanceof HTMLTextAreaElement)
            ) {
                return;
            }

            element.focus();
            // React tracks the value; set it through the prototype's native
            // setter so its onChange (and Inertia's form) see each character.
            const prototype: object = Object.getPrototypeOf(element);
            const write = (value: string) => {
                Reflect.set(prototype, 'value', value, element);
                element.dispatchEvent(new Event('input', { bubbles: true }));
            };

            if (perCharMs === 0) {
                write(text);

                return;
            }

            write('');

            for (let i = 1; i <= text.length; i++) {
                write(text.slice(0, i));
                await sleep(perCharMs);
            }
        },

        click(element) {
            draw.ring(null);
            element.focus({ preventScroll: true });
            element.click();
        },

        visit(url) {
            return new Promise((resolve) =>
                router.visit(url, { onFinish: () => resolve() }),
            );
        },

        sleep,
    };
}

function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}
