/**
 * Where Navigator's stars sit in the star band (story 42): one star per
 * suggested line, left to right in a 600 × 150 box, clear of the emblem tag
 * on the left. Decoration only; the numbered list carries the content.
 */
export type Star = { x: number; y: number; size: number };

export const BAND = { width: 600, height: 150 } as const;

const LEFT = 250;
const RIGHT = 560;
/** A gentle zigzag, like the mock-up's constellation. */
const HEIGHTS = [48, 80, 56, 92, 62, 40, 86];
const SIZES = [24, 20, 22, 20, 18];

export function constellation(count: number): Star[] {
    if (count <= 0) {
        return [];
    }

    const step = count === 1 ? 0 : (RIGHT - LEFT) / (count - 1);

    return Array.from({ length: count }, (_, i) => ({
        x: Math.round(count === 1 ? (LEFT + RIGHT) / 2 : LEFT + step * i),
        y: HEIGHTS[i % HEIGHTS.length],
        size: SIZES[i % SIZES.length],
    }));
}
