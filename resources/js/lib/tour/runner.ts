import type { Match, Step } from './types';

/**
 * What the runner needs from the page. The browser implementation is in
 * dom.ts; tests pass a fake, so the runner needs no DOM (ADR 0011).
 */
export type TourAdapter<Target = unknown> = {
    /** The element for a data-tour name, or null when it isn't there. */
    find(target: string, match?: Match): Target | null;
    moveTo(target: Target, durationMs: number): Promise<void>;
    /** text null retypes the field's current value. */
    type(target: Target, text: string | null, perCharMs: number): Promise<void>;
    click(target: Target): void | Promise<void>;
    visit(url: string): Promise<void>;
    /** Logs out of any demo and lands on the login page. */
    fresh(): Promise<void>;
    sleep(ms: number): Promise<void>;
};

export type TourStatus =
    | 'idle'
    | 'playing'
    | 'paused'
    | 'lost'
    | 'done'
    | 'exited';

export type TourState = {
    status: TourStatus;
    /** The step being played (or next to play when paused). */
    index: number;
    total: number;
    caption: string | null;
    chapter: string | null;
    /** Why the tour stopped, for "lost" (and "paused" by the visitor). */
    message: string | null;
};

export type TourTiming = {
    /** 100 is normal speed, 0 makes every wait and movement instant. */
    speed: number;
    reducedMotion: boolean;
};

/** How long a step may wait for its target, whatever the speed. */
export const WAIT_TIMEOUT_MS = 10_000;
const POLL_MS = 100;

/**
 * Reading time for a caption: about 4 words a second, at least 2.5 s and
 * at most 7 s, scaled by the speed. Reduced motion doesn't shorten it.
 */
export function captionMs(text: string, timing: TourTiming): number {
    const words = text.trim().split(/\s+/).filter(Boolean).length;
    const base = Math.min(7000, Math.max(2500, words * 250));

    return Math.round((base * timing.speed) / 100);
}

/** Cursor glide time: instant under reduced motion. */
export function moveMs(timing: TourTiming): number {
    return timing.reducedMotion ? 0 : Math.round((700 * timing.speed) / 100);
}

/** Time per typed character: instant under reduced motion. */
export function charMs(timing: TourTiming): number {
    return timing.reducedMotion ? 0 : Math.round((60 * timing.speed) / 100);
}

/** A short pause after each action, so people see what happened. */
export function settleMs(timing: TourTiming): number {
    return Math.round((400 * timing.speed) / 100);
}

/**
 * Plays a tour's steps in order. Pause, Next, Restart and Exit can arrive at
 * any time; waits check them in small slices so the tour reacts quickly.
 */
export class TourRunner<Target = unknown> {
    private state: TourState;
    private generation = 0;
    /** The generation of the loop that is running, or 0. */
    private looping = 0;
    private resumeWaiters: Array<() => void> = [];

    constructor(
        private readonly steps: Step[],
        private readonly adapter: TourAdapter<Target>,
        private timing: TourTiming,
        private readonly onChange: (state: TourState) => void = () => {},
        startAt = 0,
    ) {
        this.state = {
            status: 'idle',
            index: Math.min(Math.max(0, startAt), steps.length),
            total: steps.length,
            caption: null,
            chapter: this.chapterBefore(startAt),
            message: null,
        };
    }

    get current(): TourState {
        return this.state;
    }

    setTiming(timing: TourTiming): void {
        this.timing = timing;
    }

    /** Starts (or continues after "lost") from the current step. */
    play(): Promise<void> {
        if (this.state.status === 'playing') {
            return Promise.resolve();
        }

        this.update({ status: 'playing', message: null });

        return this.loop(++this.generation);
    }

    pause(message: string | null = null): void {
        if (this.state.status === 'playing') {
            this.update({ status: 'paused', message });
        }
    }

    /** Starts paused, e.g. after a reload: nothing runs until resume(). */
    hold(message: string | null = null): void {
        if (this.state.status === 'idle') {
            this.update({ status: 'paused', message });
        }
    }

    resume(): void {
        if (this.state.status !== 'paused') {
            return;
        }

        this.update({ status: 'playing', message: null });

        if (this.looping !== 0 && this.looping === this.generation) {
            this.wake();
        } else {
            void this.loop(++this.generation);
        }
    }

    /** Skips the rest of the current step and plays the next one. */
    next(): Promise<void> {
        if (this.isOver()) {
            return Promise.resolve();
        }

        const index = Math.min(this.state.index + 1, this.steps.length);

        this.update({
            index,
            caption: null,
            chapter: this.chapterBefore(index),
        });

        return this.restartLoop();
    }

    restart(): Promise<void> {
        this.update({ index: 0, caption: null, chapter: null, message: null });

        return this.restartLoop();
    }

    exit(): void {
        this.generation++;
        this.update({ status: 'exited', caption: null, message: null });
        this.wake();
    }

    private restartLoop(): Promise<void> {
        const generation = ++this.generation;
        this.wake();

        if (this.state.index >= this.steps.length) {
            this.update({ status: 'done' });

            return Promise.resolve();
        }

        this.update({ status: 'playing', message: null });

        return this.loop(generation);
    }

    private async loop(generation: number): Promise<void> {
        this.looping = generation;

        try {
            await this.playSteps(generation);
        } finally {
            if (this.looping === generation) {
                this.looping = 0;
            }
        }
    }

    private async playSteps(generation: number): Promise<void> {
        while (this.state.index < this.steps.length) {
            if (!(await this.gate(generation))) {
                return;
            }

            const ok = await this.run(this.steps[this.state.index], generation);

            if (generation !== this.generation) {
                return;
            }

            if (!ok) {
                this.update({
                    status: 'lost',
                    message: `Lost my way at step ${this.state.index + 1}. Take the controls, or restart.`,
                });

                return;
            }

            this.update({ index: this.state.index + 1 });
        }

        if (generation === this.generation) {
            this.update({ status: 'done' });
        }
    }

    /** Returns false when the step's target never appeared. */
    private async run(step: Step, generation: number): Promise<boolean> {
        switch (step.kind) {
            case 'chapter':
                // The last chapter's caption shouldn't linger on the next.
                this.update({ chapter: step.title, caption: null });

                return true;

            case 'caption':
                this.update({ caption: step.text });
                await this.wait(captionMs(step.text, this.timing), generation);

                return true;

            case 'visit':
                await this.adapter.visit(step.url);

                return true;

            case 'fresh':
                await this.adapter.fresh();

                return true;

            case 'waitFor':
                return (
                    (await this.findTarget(
                        step.target,
                        generation,
                        step.match,
                        step.timeoutMs,
                    )) !== null
                );

            case 'moveTo':
            case 'click':
            case 'type': {
                const target = await this.findTarget(
                    step.target,
                    generation,
                    step.match,
                );

                if (target === null) {
                    return false;
                }

                await this.adapter.moveTo(target, moveMs(this.timing));

                // Exited or replaced while the cursor moved: don't act.
                if (!(await this.gate(generation))) {
                    return true;
                }

                if (step.kind === 'click') {
                    await this.adapter.click(target);
                }

                if (step.kind === 'type') {
                    await this.adapter.type(
                        target,
                        step.text,
                        charMs(this.timing),
                    );
                }

                await this.wait(settleMs(this.timing), generation);

                return true;
            }
        }
    }

    /** Polls for a target until it appears or the timeout passes. */
    private async findTarget(
        name: string,
        generation: number,
        match?: Match,
        timeoutMs = WAIT_TIMEOUT_MS,
    ): Promise<Target | null> {
        let waited = 0;

        while (generation === this.generation) {
            const target = this.adapter.find(name, match);

            if (target !== null) {
                return target;
            }

            if (waited >= timeoutMs) {
                return null;
            }

            // Pausing doesn't use up the timeout.
            await this.gate(generation);
            await this.adapter.sleep(POLL_MS);
            waited += POLL_MS;
        }

        return null;
    }

    /** Waits in short slices, holding while paused. */
    private async wait(ms: number, generation: number): Promise<void> {
        let left = ms;

        while (left > 0 && generation === this.generation) {
            await this.gate(generation);
            const slice = Math.min(left, POLL_MS);
            await this.adapter.sleep(slice);
            left -= slice;
        }
    }

    /** Resolves true to continue, false when this loop was replaced. */
    private async gate(generation: number): Promise<boolean> {
        while (
            this.state.status === 'paused' &&
            generation === this.generation
        ) {
            await new Promise<void>((resolve) =>
                this.resumeWaiters.push(resolve),
            );
        }

        return (
            generation === this.generation && this.state.status === 'playing'
        );
    }

    private wake(): void {
        const waiters = this.resumeWaiters;
        this.resumeWaiters = [];
        waiters.forEach((resolve) => resolve());
    }

    private isOver(): boolean {
        return this.state.status === 'exited' || this.state.status === 'done';
    }

    private chapterBefore(index: number): string | null {
        for (let i = Math.min(index, this.steps.length - 1); i >= 0; i--) {
            const step = this.steps[i];

            if (step?.kind === 'chapter') {
                return step.title;
            }
        }

        return null;
    }

    private update(patch: Partial<TourState>): void {
        this.state = { ...this.state, ...patch };
        this.onChange(this.state);
    }
}
