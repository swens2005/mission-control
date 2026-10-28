import LineItemController from '@/actions/App/Http/Controllers/Admin/LineItemController';
import type { Step, Tour } from '@/lib/tour/types';
import {
    caption,
    chapter,
    click,
    fresh,
    moveTo,
    type,
    visit,
    waitFor,
} from '@/lib/tour/types';
import { index as projects } from '@/routes/admin/projects';

/**
 * The studio's side of Mission Control (story 31). Parts are reused by the
 * chapters and the full story. Index pages are visited by URL (the same in
 * every sandbox); projects are opened by clicking their name, because their
 * ids differ per sandbox.
 */

/** A new demo sandbox, logged in as the studio. */
export const logIn: Step[] = [
    fresh(),
    caption(
        'Hi! I’m the autopilot. I’ll run Mission Control for you: real clicks and real requests, in a demo studio of your own.',
    ),
    waitFor('take-the-controls'),
    click('take-the-controls'),
    // Not there when the demo is busy or this visitor started too many;
    // the login page then says why.
    waitFor('demo-ready'),
    caption(
        'That made a fresh demo studio, just for this visit. Now I log in as Sam, the studio’s designer.',
    ),
    type('login-email', null),
    type('login-password', null),
    click('login-submit'),
    waitFor('switch-portal'),
];

/** Opens a project in the studio by its name. */
export function openProject(name: string, module: string): Step[] {
    return [
        visit(projects.url()),
        waitFor('project-link', { text: name }),
        click('project-link', { text: name }),
        waitFor(module),
        click(module),
    ];
}

export const scope: Step[] = [
    chapter('Scope'),
    visit(LineItemController.index.url()),
    waitFor('add-line-item'),
    caption(
        'Every project starts with Scope. This is the studio’s price list: fixed prices and hourly work, stored in whole cents.',
    ),
    ...openProject('Seasonal menu pages', 'open-scope'),
    waitFor('changes-request'),
    moveTo('changes-request'),
    caption(
        'Anna from Bakkerij de Vries filled in the intake, read proposal v1, and asked for Easter pages too.',
    ),
    moveTo('proposal-lines'),
    caption(
        'So the studio is preparing v2 with the Easter pages. A sent version never changes; changes always make a new one.',
    ),
    moveTo('proposal-totals'),
    caption(
        'The server does the sums: 10% off for a returning client, then 21% VAT, rounded once on the total.',
    ),
];

export const palette: Step[] = [
    chapter('Palette Lab'),
    ...openProject('codelaunch.nl', 'open-palette'),
    waitFor('contrast-matrix'),
    moveTo('contrast-matrix'),
    caption(
        'Palette Lab checks every text color on every background. This kit is this app’s own palette, plus one blue from the original CV site.',
    ),
    click('fix-it'),
    waitFor('fix-panel'),
    moveTo('fix-panel'),
    caption(
        'That blue fails WCAG AA. “Fix it” finds the nearest lightness that passes, keeping the hue, in OKLCH.',
    ),
    click('apply-fix'),
    waitFor('contrast-matrix'),
    caption(
        'Fixed: the same change this app’s own colors needed. The client approves the kit in their portal.',
    ),
];

export const proofmark: Step[] = [
    chapter('Proofmark'),
    ...openProject('Wedding cakes', 'open-proofmark'),
    waitFor('pin'),
    moveTo('design-viewer'),
    caption(
        'Proofmark is design review. The client pins comments right on the design, by mouse or with a keyboard crosshair.',
    ),
    moveTo('pin-list'),
    caption(
        'Every pin is also in this list, so the whole review works by keyboard and screen reader too.',
    ),
];

export const launch: Step[] = [
    chapter('Launch Control'),
    ...openProject('Online pre-orders', 'open-launch-control'),
    waitFor('run-checks'),
    moveTo('go-board'),
    caption(
        'Launch Control: automated pre-flight checks, a manual checklist and a go/no-go board.',
    ),
    click('run-checks'),
    caption(
        'Ten real checks against codelaunch.nl: HTTPS, security headers, headings, alt text and more, through a fetcher that refuses private addresses.',
    ),
    waitFor('check-result', undefined, 45_000),
    moveTo('check-results'),
    caption(
        'Each check passes, warns or fails with a plain explanation. A failing one can be waived, with a reason.',
    ),
    moveTo('go-board'),
    caption(
        'The board shows GO only when every check passes or is waived and every role has signed off.',
    ),
];

const end = (text: string): Step[] => [caption(text)];

export const studioTour: Tour = {
    id: 'studio',
    group: 'tour',
    title: 'The studio tour',
    duration: 'About 2½ minutes',
    description:
        'The four modules in the order a website project moves through them, from the studio’s side.',
    steps: [
        ...logIn,
        ...scope,
        ...palette,
        ...proofmark,
        ...launch,
        ...end(
            'That’s the studio. The demo is yours now: take the controls, or watch the full story.',
        ),
    ],
};

function chapterTour(
    id: string,
    title: string,
    description: string,
    steps: Step[],
): Tour {
    return {
        id,
        group: 'chapter',
        title,
        duration: 'About a minute',
        description,
        steps: [
            ...logIn,
            ...steps,
            ...end(
                'That’s the chapter. The demo is yours now: take the controls.',
            ),
        ],
    };
}

export const chapterTours: Tour[] = [
    chapterTour(
        'chapter-scope',
        'Scope',
        'Price list, versions, and money maths in whole cents.',
        scope,
    ),
    chapterTour(
        'chapter-palette',
        'Palette Lab',
        'Contrast checks and “Fix it” in OKLCH.',
        palette,
    ),
    chapterTour(
        'chapter-proofmark',
        'Proofmark',
        'Pinned comments on designs, by mouse or keyboard.',
        proofmark,
    ),
    chapterTour(
        'chapter-launch',
        'Launch Control',
        'Ten automated checks and the go/no-go board.',
        launch,
    ),
];
