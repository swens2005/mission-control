import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    captionMs,
    charMs,
    moveMs,
    TourRunner,
    WAIT_TIMEOUT_MS,
} from './runner';
import type { TourAdapter, TourState, TourTiming } from './runner';
import { caption, chapter, click, fresh, type, visit, waitFor } from './types';

const normal: TourTiming = { speed: 100, reducedMotion: false };

/**
 * A fake page: targets are names in a set, or "name:text" for a match by
 * text; actions are logged.
 */
function fakePage(present: string[] = []) {
    const targets = new Set(present);
    const log: string[] = [];
    const adapter: TourAdapter<string> = {
        find: (name, match) => {
            const key = match?.text ? `${name}:${match.text}` : name;

            return targets.has(key) ? key : null;
        },
        fresh: async () => {
            log.push('fresh');
        },
        moveTo: async (name, ms) => {
            log.push(`move ${name} ${ms}`);
            await new Promise((r) => setTimeout(r, ms));
        },
        type: async (name, text, perChar) => {
            log.push(`type ${name} ${text} ${perChar}`);
        },
        click: (name) => {
            log.push(`click ${name}`);
        },
        visit: async (url) => {
            log.push(`visit ${url}`);
        },
        sleep: (ms) => new Promise((r) => setTimeout(r, ms)),
    };

    return { adapter, log, targets };
}

describe('timings', () => {
    it('gives captions reading time, scaled by speed', () => {
        expect(captionMs('Short one', normal)).toBe(2500);
        expect(captionMs('word '.repeat(20), normal)).toBe(5000);
        expect(captionMs('word '.repeat(100), normal)).toBe(7000);
        expect(captionMs('word '.repeat(20), { ...normal, speed: 50 })).toBe(
            2500,
        );
        expect(captionMs('Anything', { speed: 0, reducedMotion: false })).toBe(
            0,
        );
    });

    it('makes movement and typing instant under reduced motion, not reading', () => {
        const reduced = { speed: 100, reducedMotion: true };

        expect(moveMs(normal)).toBe(700);
        expect(charMs(normal)).toBe(60);
        expect(moveMs(reduced)).toBe(0);
        expect(charMs(reduced)).toBe(0);
        expect(captionMs('Short one', reduced)).toBe(2500);
    });
});

describe('TourRunner', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('plays every step in order and finishes', async () => {
        const page = fakePage(['login-submit', 'email']);
        const states: TourState[] = [];
        const runner = new TourRunner(
            [
                chapter('Login'),
                caption('Hello'),
                type('email', 'demo@codelaunch.nl'),
                click('login-submit'),
                visit('/admin'),
            ],
            page.adapter,
            normal,
            (state) => states.push(state),
        );

        const done = runner.play();
        await vi.runAllTimersAsync();
        await done;

        expect(runner.current.status).toBe('done');
        expect(runner.current.chapter).toBe('Login');
        expect(states.some((s) => s.caption === 'Hello')).toBe(true);
        expect(page.log).toEqual([
            'move email 700',
            'type email demo@codelaunch.nl 60',
            'move login-submit 700',
            'click login-submit',
            'visit /admin',
        ]);
    });

    it('holds while paused and carries on after resume', async () => {
        const page = fakePage(['a']);
        const runner = new TourRunner(
            [caption('Read me'), click('a')],
            page.adapter,
            normal,
        );

        const done = runner.play();
        await vi.advanceTimersByTimeAsync(500);
        runner.pause('You took over. Resume?');

        await vi.advanceTimersByTimeAsync(60_000);
        expect(runner.current.status).toBe('paused');
        expect(runner.current.message).toBe('You took over. Resume?');
        expect(page.log).toEqual([]);

        runner.resume();
        await vi.runAllTimersAsync();
        await done;

        expect(page.log).toContain('click a');
        expect(runner.current.status).toBe('done');
    });

    it('skips the rest of a step with Next', async () => {
        const page = fakePage(['a']);
        const runner = new TourRunner(
            [caption('A long caption to read'), click('a')],
            page.adapter,
            normal,
        );

        void runner.play();
        await vi.advanceTimersByTimeAsync(100);
        expect(runner.current.index).toBe(0);

        const after = runner.next();
        await vi.runAllTimersAsync();
        await after;

        expect(page.log).toContain('click a');
        expect(runner.current.status).toBe('done');
    });

    it('restarts from the first step', async () => {
        const page = fakePage(['a']);
        const runner = new TourRunner(
            [click('a'), caption('End')],
            page.adapter,
            normal,
        );

        void runner.play();
        await vi.runAllTimersAsync();
        expect(page.log.filter((l) => l === 'click a')).toHaveLength(1);

        void runner.restart();
        await vi.runAllTimersAsync();

        expect(page.log.filter((l) => l === 'click a')).toHaveLength(2);
        expect(runner.current.status).toBe('done');
    });

    it('stops at once on exit, without acting', async () => {
        const page = fakePage(['a']);
        const runner = new TourRunner([click('a')], page.adapter, normal);

        void runner.play();
        // The cursor is still on its way.
        await vi.advanceTimersByTimeAsync(100);
        runner.exit();
        await vi.runAllTimersAsync();

        expect(runner.current.status).toBe('exited');
        expect(page.log).toEqual(['move a 700']);
    });

    it('waits for a target that appears later', async () => {
        const page = fakePage();
        const runner = new TourRunner(
            [waitFor('result'), click('result')],
            page.adapter,
            normal,
        );

        const done = runner.play();
        await vi.advanceTimersByTimeAsync(2000);
        page.targets.add('result');
        await vi.runAllTimersAsync();
        await done;

        expect(runner.current.status).toBe('done');
        expect(page.log).toContain('click result');
    });

    it('gets lost when a target never appears, and says where', async () => {
        const page = fakePage();
        const runner = new TourRunner(
            [caption('Hi'), click('missing')],
            page.adapter,
            {
                speed: 0,
                reducedMotion: true,
            },
        );

        const done = runner.play();
        await vi.advanceTimersByTimeAsync(WAIT_TIMEOUT_MS + 500);
        await done;

        expect(runner.current.status).toBe('lost');
        expect(runner.current.message).toBe(
            'Lost my way at step 2. Take the controls, or restart.',
        );
    });

    it('does not use up the wait while paused', async () => {
        const page = fakePage();
        const runner = new TourRunner([waitFor('slow')], page.adapter, normal);

        const done = runner.play();
        await vi.advanceTimersByTimeAsync(1000);
        runner.pause();
        await vi.advanceTimersByTimeAsync(WAIT_TIMEOUT_MS * 3);
        page.targets.add('slow');
        runner.resume();
        await vi.runAllTimersAsync();
        await done;

        expect(runner.current.status).toBe('done');
    });

    it('starts fresh, finds targets by text, and retypes prefilled values', async () => {
        const page = fakePage(['project-link:Seasonal menu pages', 'email']);
        const runner = new TourRunner(
            [
                fresh(),
                type('email', null),
                click('project-link', { text: 'Seasonal menu pages' }),
            ],
            page.adapter,
            { speed: 0, reducedMotion: true },
        );

        const done = runner.play();
        await vi.runAllTimersAsync();
        await done;

        expect(page.log).toEqual([
            'fresh',
            'move email 0',
            'type email null 0',
            'move project-link:Seasonal menu pages 0',
            'click project-link:Seasonal menu pages',
        ]);
    });

    it('lets a step wait longer than the default, e.g. for live checks', async () => {
        const page = fakePage();
        const runner = new TourRunner(
            [waitFor('check-result', undefined, 30_000)],
            page.adapter,
            normal,
        );

        const done = runner.play();
        await vi.advanceTimersByTimeAsync(WAIT_TIMEOUT_MS + 5000);
        expect(runner.current.status).toBe('playing');

        page.targets.add('check-result');
        await vi.runAllTimersAsync();
        await done;

        expect(runner.current.status).toBe('done');
    });

    it('can start held (after a reload) and does nothing until resumed', async () => {
        const page = fakePage(['a']);
        const runner = new TourRunner([click('a')], page.adapter, normal);

        runner.hold('The page reloaded. Resume the tour?');
        await vi.advanceTimersByTimeAsync(30_000);

        expect(runner.current.status).toBe('paused');
        expect(page.log).toEqual([]);

        runner.resume();
        await vi.runAllTimersAsync();

        expect(page.log).toContain('click a');
        expect(runner.current.status).toBe('done');
    });

    it('can start part-way, with the chapter it is in', () => {
        const runner = new TourRunner(
            [
                chapter('Scope'),
                caption('a'),
                chapter('Palette Lab'),
                caption('b'),
            ],
            fakePage().adapter,
            normal,
            () => {},
            3,
        );

        expect(runner.current.index).toBe(3);
        expect(runner.current.chapter).toBe('Palette Lab');
    });
});
