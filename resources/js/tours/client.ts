import type { Step, Tour } from '@/lib/tour/types';
import {
    caption,
    chapter,
    click,
    moveTo,
    press,
    type,
    visit,
    waitFor,
} from '@/lib/tour/types';
import { home } from '@/routes/client';
import { logIn, openProject } from './studio';

/**
 * The client's side, and the story of one proposal across both portals
 * (story 32). The switch between portals is the sandbox's real "View as
 * client" button, so the policies between the portals are in play too.
 */

const toClient: Step[] = [click('switch-portal'), waitFor('waiting-item')];

/** Back to Launchpad's home, then one of Anna's to-dos. */
function openToDo(title: string, ready: string): Step[] {
    return [
        visit(home.url()),
        waitFor('waiting-item', { text: title }),
        click('waiting-item', { text: title }),
        waitFor(ready),
    ];
}

export const clientTour: Tour = {
    id: 'client',
    group: 'tour',
    title: 'The client tour',
    duration: 'About 1½ minutes',
    description:
        'Launchpad, the client’s portal: one list of everything waiting on them, and each module from their side.',
    steps: [
        ...logIn,
        chapter('Launchpad'),
        caption(
            'Now the other side. The demo can switch to Anna, the bakery’s owner, with one click.',
        ),
        ...toClient,
        moveTo('waiting-item'),
        caption(
            'This is Launchpad, Anna’s portal. “Waiting on you” collects her to-dos from every module, soonest deadline first.',
        ),

        chapter('Palette Lab'),
        ...openToDo('Approve the brand kit', 'style-guide'),
        moveTo('style-guide'),
        caption(
            'The studio shared the Café corner brand kit as a live style guide: colors, readable pairs and type.',
        ),
        click('approve-kit'),
        waitFor('style-guide'),
        caption(
            'Approved. The kit is locked now; changing it needs a revision, which asks Anna again.',
        ),

        chapter('Proofmark'),
        ...openToDo('Review design round', 'add-comment'),
        moveTo('design-viewer'),
        caption(
            'Design round v2 of the wedding cake pages. Anna can pin a comment by mouse, or by keyboard like this.',
        ),
        click('add-comment'),
        waitFor('crosshair'),
        press('crosshair', 'ArrowRight', 4),
        press('crosshair', 'ArrowDown', 3),
        press('crosshair', 'Enter'),
        waitFor('comment-body'),
        type('comment-body', 'Could this photo be a little larger?'),
        click('comment-submit'),
        waitFor('pin-list'),
        moveTo('pin-list'),
        caption(
            'Pinned, and listed for keyboard and screen reader users. The studio resolves it before the next round.',
        ),

        chapter('Launch Control'),
        ...openToDo('Finish your launch checklist', 'launch-checklist'),
        click('checklist-label', { text: 'Analytics consent' }),
        caption(
            'Some checks are Anna’s to tick. When everything is green, she signs the go/no-go board here too.',
        ),
        moveTo('go-board'),
        caption(
            'That’s Launchpad. The demo is yours now: take the controls, or watch the full story.',
        ),
    ],
};

export const fullStory: Tour = {
    id: 'full-story',
    group: 'tour',
    title: 'The full story',
    duration: 'About 2 minutes',
    description:
        'One proposal from the studio to the client and back: sent, accepted with a typed name, and on to the next module.',
    steps: [
        ...logIn,
        chapter('The studio'),
        ...openProject('Seasonal menu pages', 'open-scope'),
        waitFor('intake-answers'),
        moveTo('intake-answers'),
        caption(
            'Bakkerij de Vries wants seasonal menu pages. Anna described what she needs in the intake.',
        ),
        moveTo('changes-request'),
        caption(
            'The studio sent proposal v1. Anna asked for Easter pages too, so the studio made v2.',
        ),
        moveTo('proposal-lines'),
        caption(
            'Here they are: Easter pages, a bit more writing and photo work. Every line is a copy from the studio’s price list.',
        ),
        moveTo('proposal-totals'),
        caption(
            'The server works out the money in whole cents: 10% off, then 21% VAT, rounded once on the total.',
        ),
        click('send-proposal'),
        waitFor('proposal-version', { text: 'sent' }),
        caption(
            'Sent. v2 is frozen now, exactly as Anna will see it. Let’s switch to her side.',
        ),

        chapter('The client'),
        ...toClient,
        moveTo('waiting-item', { text: 'Accept proposal v2' }),
        caption('Anna sees it straight away in “Waiting on you”.'),
        click('waiting-item', { text: 'Accept proposal v2' }),
        waitFor('client-proposal'),
        moveTo('client-proposal'),
        caption(
            'She reads the proposal (she can print it, too) and accepts it the way you’d sign a quote.',
        ),
        type('accept-name', 'Anna de Vries'),
        click('accept-confirm'),
        click('accept-submit'),
        waitFor('proposal-status', { text: 'Accepted' }),
        caption(
            'Accepted, with her typed name, the time and her IP address. This version can never change again.',
        ),

        chapter('Back in the studio'),
        click('switch-portal'),
        waitFor('switch-portal'),
        ...openProject('Seasonal menu pages', 'open-scope'),
        waitFor('move-to-palette'),
        click('move-to-palette'),
        waitFor('start-kit'),
        moveTo('start-kit'),
        caption(
            'The project moves on to Palette Lab: colors and type, then Proofmark for designs and Launch Control for launch day.',
        ),
        caption(
            'That’s the whole story. The demo is yours now: take the controls, or watch the studio tour for the other modules.',
        ),
    ],
};
