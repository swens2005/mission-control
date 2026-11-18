import { describe, expect, test } from 'vite-plus/test';
import { BAND, constellation } from './constellation';

describe('constellation', () => {
    test('no lines, no stars', () => {
        expect(constellation(0)).toEqual([]);
    });

    test('one star sits in the middle of the open sky', () => {
        expect(constellation(1)).toEqual([{ x: 405, y: 48, size: 24 }]);
    });

    test('stars run left to right, clear of the emblem, inside the band', () => {
        for (const count of [2, 5, 12]) {
            const stars = constellation(count);

            expect(stars).toHaveLength(count);
            expect(stars[0].x).toBe(250);
            expect(stars[count - 1].x).toBe(560);

            stars.forEach((star, i) => {
                if (i > 0) {
                    expect(star.x).toBeGreaterThan(stars[i - 1].x);
                }
                expect(star.x + star.size / 2).toBeLessThanOrEqual(BAND.width);
                expect(star.y - star.size / 2).toBeGreaterThanOrEqual(0);
                expect(star.y + star.size / 2).toBeLessThanOrEqual(BAND.height);
            });
        }
    });
});
