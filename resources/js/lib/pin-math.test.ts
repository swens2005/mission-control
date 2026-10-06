import { describe, expect, test } from 'vite-plus/test';
import {
    clampPin,
    describePin,
    moveCrosshair,
    pinFromPixels,
    pinToCss,
} from './pin-math';

describe('pinFromPixels', () => {
    test('converts a click to hundredths of a percent', () => {
        expect(pinFromPixels(360, 225, 1440, 900)).toEqual({
            x: 2500,
            y: 2500,
        });
        expect(pinFromPixels(1, 1, 3, 3)).toEqual({ x: 3333, y: 3333 });
    });

    test('clamps clicks on or past the edges onto the image', () => {
        expect(pinFromPixels(0, 0, 800, 600)).toEqual({ x: 0, y: 0 });
        expect(pinFromPixels(800, 600, 800, 600)).toEqual({
            x: 10_000,
            y: 10_000,
        });
        expect(pinFromPixels(-5, 900, 800, 600)).toEqual({
            x: 0,
            y: 10_000,
        });
    });

    test('an image without a size gives the top left corner', () => {
        expect(pinFromPixels(10, 10, 0, 0)).toEqual({ x: 0, y: 0 });
    });
});

describe('moveCrosshair', () => {
    const middle = { x: 5000, y: 5000 };

    test('arrow keys move 1%, with Shift 10%', () => {
        expect(moveCrosshair(middle, 'ArrowRight', false)).toEqual({
            x: 5100,
            y: 5000,
        });
        expect(moveCrosshair(middle, 'ArrowUp', true)).toEqual({
            x: 5000,
            y: 4000,
        });
    });

    test('never leaves the image', () => {
        expect(moveCrosshair({ x: 50, y: 9950 }, 'ArrowLeft', false)).toEqual({
            x: 0,
            y: 9950,
        });
        expect(moveCrosshair({ x: 50, y: 9950 }, 'ArrowDown', true)).toEqual({
            x: 50,
            y: 10_000,
        });
    });

    test('ignores other keys', () => {
        expect(moveCrosshair(middle, 'Enter', false)).toBeNull();
        expect(moveCrosshair(middle, 'a', true)).toBeNull();
    });
});

describe('formatting', () => {
    test('pinToCss gives a percentage', () => {
        expect(pinToCss(4025)).toBe('40.25%');
        expect(pinToCss(0)).toBe('0%');
        expect(pinToCss(12_000)).toBe('100%');
    });

    test('describePin rounds to whole percentages', () => {
        expect(describePin({ x: 4025, y: 2549 })).toBe('40% across, 25% down');
    });

    test('clampPin handles odd input', () => {
        expect(clampPin(Number.NaN)).toBe(0);
        expect(clampPin(4999.6)).toBe(5000);
        expect(clampPin(-1)).toBe(0);
    });
});
