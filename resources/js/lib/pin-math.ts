/**
 * Pin positions for Proofmark (story 17). A position is stored as integer
 * hundredths of a percent of the image (0 to 10 000), so a pin stays on the
 * same spot at any zoom or screen width, and no floats reach the database.
 */

export const PIN_MAX = 10_000;

/** 1% and 10% of the image, the arrow-key steps. */
export const STEP_SMALL = 100;
export const STEP_LARGE = 1_000;

export type PinPoint = { x: number; y: number };

export function clampPin(value: number): number {
    if (!Number.isFinite(value)) {
        return 0;
    }

    return Math.min(PIN_MAX, Math.max(0, Math.round(value)));
}

/**
 * A click inside the image's box (in CSS pixels) as a pin position.
 * Clicks on the edge, or a pixel outside it, are clamped onto the image.
 */
export function pinFromPixels(
    offsetX: number,
    offsetY: number,
    width: number,
    height: number,
): PinPoint {
    if (width <= 0 || height <= 0) {
        return { x: 0, y: 0 };
    }

    return {
        x: clampPin((offsetX / width) * PIN_MAX),
        y: clampPin((offsetY / height) * PIN_MAX),
    };
}

/**
 * Moves the keyboard crosshair: one arrow-key press, 1% or (with Shift)
 * 10%, never off the image. Returns null for keys it doesn't handle.
 */
export function moveCrosshair(
    point: PinPoint,
    key: string,
    large: boolean,
): PinPoint | null {
    const step = large ? STEP_LARGE : STEP_SMALL;
    const moves: Record<string, [number, number]> = {
        ArrowLeft: [-step, 0],
        ArrowRight: [step, 0],
        ArrowUp: [0, -step],
        ArrowDown: [0, step],
    };
    const move = moves[key];

    if (!move) {
        return null;
    }

    return {
        x: clampPin(point.x + move[0]),
        y: clampPin(point.y + move[1]),
    };
}

/** For CSS `left`/`top`: 4025 becomes "40.25%". */
export function pinToCss(value: number): string {
    return `${clampPin(value) / 100}%`;
}

/** For people and screen readers: "40% across, 25% down". */
export function describePin(point: PinPoint): string {
    return `${Math.round(point.x / 100)}% across, ${Math.round(point.y / 100)}% down`;
}
