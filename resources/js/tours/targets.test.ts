import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, it } from 'vitest';
import { tours } from './index';

const root = join(__dirname, '..');

/** Every .tsx file outside the tour scripts themselves. */
function appSources(dir: string): string[] {
    return readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);

        if (statSync(path).isDirectory()) {
            return relative(root, path) === 'tours' ? [] : appSources(path);
        }

        return path.endsWith('.tsx') ? [path] : [];
    });
}

const source = appSources(root)
    .map((path) => readFileSync(path, 'utf8'))
    .join('\n');

describe('tour targets', () => {
    const targets = new Set(
        tours.flatMap((tour) =>
            tour.steps.flatMap((step) =>
                'target' in step ? [step.target] : [],
            ),
        ),
    );

    // A renamed data-tour attribute should fail here, before any browser.
    it.each([...targets])('"%s" exists in the app', (target) => {
        expect(
            source.includes(`"${target}"`) || source.includes(`'${target}'`),
        ).toBe(true);
    });

    it('finds the attributes it checks against', () => {
        expect(source).toContain('data-tour="login-submit"');
    });
});
