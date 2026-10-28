// Story 34: after each deploy, click through the real site as a demo
// visitor. Subfolder URLs and the .htaccess headers (CSP, ADR 0006) only
// show their problems on production, so this runs against it for real.
//
//   node tests/Smoke/production.mjs https://codelaunch.nl/mission-control
//
// It prints one line per page (pass or fail) and nothing about the server.
// One demo sandbox is created per run; it's pruned after 24 hours.
import { chromium } from 'playwright';

const base = (
    process.argv[2] ?? 'https://codelaunch.nl/mission-control'
).replace(/\/$/, '');
const prefix = `${new URL(base).pathname}/`;
const problems = [];

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
const path = () => new URL(page.url()).pathname;

page.on('console', (message) => {
    if (message.type() === 'error') {
        problems.push(`console error on ${path()}: ${message.text()}`);
    }
});
page.on('pageerror', (error) =>
    problems.push(`JavaScript error on ${path()}: ${error.message}`),
);

const hook = (name, text) =>
    text === undefined
        ? page.locator(`[data-tour="${name}"]`).first()
        : page.locator(`[data-tour="${name}"]`, { hasText: text }).first();

/** Waits for a hook, then checks the URL kept the subfolder. */
async function arrive(label, ready) {
    try {
        await hook(ready).waitFor({ timeout: 30_000 });

        if (!path().startsWith(prefix)) {
            throw new Error(`left the ${prefix} subfolder: ${path()}`);
        }

        console.log(`pass  ${label}`);
    } catch (error) {
        problems.push(`${label}: ${error.message.split('\n')[0]}`);
        console.log(`FAIL  ${label}`);
    }
}

async function openProject(name, module, ready) {
    await hook('nav-projects').click();
    await hook('project-link', name).click();
    await hook(module).click();
    await arrive(`studio: ${name}, ${module.replace('open-', '')}`, ready);
}

try {
    await page.goto(`${base}/login`);
    await arrive('login page', 'take-the-controls');

    await hook('take-the-controls').click();
    await arrive('a new demo sandbox', 'demo-ready');

    await hook('login-submit').click();
    await arrive('studio: home', 'switch-portal');

    await hook('scope-library').click();
    await arrive('studio: Scope library', 'add-line-item');

    await openProject('Seasonal menu pages', 'open-scope', 'proposal-totals');
    await openProject('codelaunch.nl', 'open-palette', 'contrast-matrix');
    await openProject('Wedding cakes', 'open-proofmark', 'design-viewer');
    await openProject('Online pre-orders', 'open-launch-control', 'go-board');

    await hook('switch-portal').click();
    await arrive('client: Launchpad', 'waiting-item');

    await hook('client-project', 'Seasonal menu pages').click();
    await hook('open-scope').click();
    await arrive('client: Seasonal menu pages, scope', 'client-proposal');

    await page.goto(`${base}/autopilot`);
    await arrive('autopilot page', 'start-full-story');
} catch (error) {
    problems.push(`stopped at ${path()}: ${error.message.split('\n')[0]}`);
} finally {
    await browser.close();
}

if (problems.length > 0) {
    console.log(
        '\nThe files are already live; fix forward with a new commit.\n',
    );
    problems.forEach((problem) => console.log(`- ${problem}`));
    process.exit(1);
}

console.log('\nProduction looks good.');
