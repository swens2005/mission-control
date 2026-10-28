// Story 39: the README's screenshots and the frames for its autopilot GIF.
//
//   node scripts/record-media.mjs http://127.0.0.1:8133 [--screenshots-only]
//   python scripts/make-gif.py
//
// Run against a local server with the demo enabled (CLAUDE.md, "Local
// server without .env"). It uses the Playwright that the browser tests
// already install, and Chrome's own screencast for smooth frames; Pillow
// (Python) turns the frames into a GIF. Nothing new to install.
import { mkdir, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { chromium } from 'playwright';

const base = (process.argv[2] ?? 'http://127.0.0.1:8133').replace(/\/$/, '');
const media = new URL('../docs/media/', import.meta.url);
// Frames go to the system temp folder, not the repo (OneDrive syncs it).
const frames = new URL(
    'mission-control-frames/',
    pathToFileURL(`${tmpdir()}/`),
);
const viewport = { width: 1280, height: 800 };

await mkdir(media, { recursive: true });
await rm(frames, { recursive: true, force: true });
await mkdir(frames, { recursive: true });

const browser = await chromium.launch();
const hook = (page, name, text) =>
    text === undefined
        ? page.locator(`[data-tour="${name}"]`).first()
        : page.locator(`[data-tour="${name}"]`, { hasText: text }).first();

// --- Screenshots: one per module, in a fresh sandbox ----------------------
{
    const page = await browser.newPage({ viewport });
    await page.goto(`${base}/login`);
    await hook(page, 'take-the-controls').click();
    await hook(page, 'demo-ready').waitFor();
    await hook(page, 'login-submit').click();
    await hook(page, 'switch-portal').waitFor();

    const scrollTo = (name) => async () => {
        await hook(page, name).evaluate((el) =>
            window.scrollTo(
                0,
                el.getBoundingClientRect().top + window.scrollY - 90,
            ),
        );
    };
    const shots = [
        {
            file: 'scope',
            project: 'Seasonal menu pages',
            module: 'open-scope',
            ready: 'proposal-totals',
        },
        {
            file: 'palette-lab',
            project: 'codelaunch.nl',
            module: 'open-palette',
            ready: 'contrast-matrix',
            prepare: scrollTo('contrast-matrix'),
        },
        {
            file: 'proofmark',
            project: 'Wedding cakes',
            module: 'open-proofmark',
            ready: 'design-viewer',
            prepare: async () => {
                await page.waitForFunction(() =>
                    [
                        ...document.querySelectorAll(
                            '[data-tour="design-viewer"] img',
                        ),
                    ].every((img) => img.complete && img.naturalWidth > 0),
                );
                await scrollTo('design-viewer')();
            },
        },
        {
            file: 'launch-control',
            project: 'Online pre-orders',
            module: 'open-launch-control',
            ready: 'go-board',
            prepare: async () => {
                // Real checks against codelaunch.nl, as in the tour.
                await hook(page, 'run-checks').click();
                await hook(page, 'check-result').waitFor({ timeout: 60_000 });
                await page.evaluate(() => window.scrollTo(0, 0));
            },
        },
    ];

    for (const { file, project, module, ready, prepare } of shots) {
        await hook(page, 'nav-projects').click();
        await hook(page, 'project-link', project).click();
        await hook(page, module).click();
        await hook(page, ready).waitFor();
        await page.waitForLoadState('networkidle');
        await prepare?.();
        await page.waitForTimeout(600);
        await page.screenshot({
            path: fileURLToPath(new URL(`${file}.png`, media)),
        });
        console.log(`screenshot  docs/media/${file}.png`);
    }

    await page.close();
}

// --- GIF frames: the Palette Lab chapter, as a visitor sees it ------------
if (process.argv[3] !== '--screenshots-only') {
    const page = await browser.newPage({ viewport });
    await page.goto(`${base}/autopilot`);

    const cdp = await page.context().newCDPSession(page);
    const timings = [];
    cdp.on('Page.screencastFrame', async ({ data, metadata, sessionId }) => {
        const n = String(timings.length).padStart(4, '0');
        timings.push({ file: `${n}.jpg`, t: metadata.timestamp });
        await writeFile(
            new URL(`${n}.jpg`, frames),
            Buffer.from(data, 'base64'),
        );
        await cdp
            .send('Page.screencastFrameAck', { sessionId })
            .catch(() => {});
    });
    await cdp.send('Page.startScreencast', {
        format: 'jpeg',
        quality: 85,
        maxWidth: viewport.width,
        maxHeight: viewport.height,
    });

    await hook(page, 'start-chapter-palette').click();
    await page
        .locator('[aria-label="Autopilot"]', { hasText: 'THE END' })
        .waitFor({ timeout: 180_000 });
    await page.waitForTimeout(1500);

    await cdp.send('Page.stopScreencast');
    await writeFile(new URL('timings.json', frames), JSON.stringify(timings));
    const seconds = timings.at(-1).t - timings[0].t;
    console.log(
        `gif frames  ${timings.length} frames, ${seconds.toFixed(1)} s`,
    );
}

await browser.close();
