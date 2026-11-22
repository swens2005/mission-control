/**
 * Mission Control's art (story 48), imported once so every page uses the
 * same files and sizes. All of it is decorative: pages give it alt="".
 */
import launchBadge from '../../images/mission-control/badges/launch.webp';
import paletteBadge from '../../images/mission-control/badges/palette.webp';
import proofmarkBadge from '../../images/mission-control/badges/proofmark.webp';
import scopeBadge from '../../images/mission-control/badges/scope.webp';
import simulatorBadge from '../../images/mission-control/badges/simulator.webp';
import emptyActivity from '../../images/mission-control/empty/activity.webp';
import emptyAllClear from '../../images/mission-control/empty/all-clear.webp';
import emptyClients from '../../images/mission-control/empty/clients.webp';
import emptyDesigns from '../../images/mission-control/empty/designs.webp';
import emptyLibrary from '../../images/mission-control/empty/library.webp';
import emptyProjects from '../../images/mission-control/empty/projects.webp';
import scopeLandmark from '../../images/mission-control/landmarks/scope.webp';

export type Art = { src: string; width: number; height: number };

/** Round station badges, 256 px squares. */
export const badges = {
    scope: scopeBadge,
    palette: paletteBadge,
    proofmark: proofmarkBadge,
    launch: launchBadge,
    simulator: simulatorBadge,
} as const;

const wide = (src: string): Art => ({ src, width: 480, height: 320 });

export const emptyArt = {
    projects: { src: emptyProjects, width: 480, height: 480 },
    clients: wide(emptyClients),
    activity: wide(emptyActivity),
    library: wide(emptyLibrary),
    designs: wide(emptyDesigns),
    allClear: wide(emptyAllClear),
} satisfies Record<string, Art>;

export const landmarks = {
    scope: { src: scopeLandmark, width: 720, height: 480 },
} satisfies Record<string, Art>;
