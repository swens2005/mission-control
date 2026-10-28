import type { Tour } from '@/lib/tour/types';
import { chapterTours, studioTour } from './studio';

/**
 * Every autopilot tour (stories 31 and 32). The browser tests run each one
 * end to end (story 33); a Vitest check makes sure their targets exist.
 */
export const tours: Tour[] = [studioTour, ...chapterTours];

export function findTour(id: string): Tour | undefined {
    return tours.find((tour) => tour.id === id);
}
