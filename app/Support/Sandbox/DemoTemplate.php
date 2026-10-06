<?php

namespace App\Support\Sandbox;

use App\Enums\ProjectPhase;

/**
 * The studio every demo visitor starts with: three clients and five
 * projects spread over the phases, so each module has something to show.
 * The first client is the one the demo client contact belongs to.
 */
final class DemoTemplate
{
    public const STUDIO = 'Orbit Web Studio';

    public const ADMIN_NAME = 'Sam Visser';

    public const CLIENT_NAME = 'Anna de Vries';

    /**
     * Launch Control's demo: the demo client's project in the Launch phase,
     * checked against the real codelaunch.nl. Items already ticked, and by
     * whom; the rest are left for the visitor.
     *
     * @return array{project: string, url: string, ticked: array<string, 'admin'|'client'>}
     */
    public static function launch(): array
    {
        return [
            'project' => 'Online pre-orders',
            'url' => 'https://codelaunch.nl',
            'ticked' => [
                '404 page' => 'admin',
                'Favicon' => 'admin',
                'Forms tested' => 'client',
            ],
        ];
    }

    /**
     * @return list<array{name: string, website: string, projects: list<array{name: string, description: string, phase: ProjectPhase, launch_in_days: int}>}>
     */
    public static function organizations(): array
    {
        return [
            [
                'name' => 'Bakkerij de Vries',
                'website' => 'https://bakkerij.example',
                'projects' => [
                    [
                        'name' => 'Online pre-orders',
                        'description' => "A new site where regulars order tomorrow's bread before 8 pm.",
                        'phase' => ProjectPhase::Launch,
                        'launch_in_days' => 6,
                    ],
                    [
                        'name' => 'Seasonal menu pages',
                        'description' => 'Christmas and Easter specials, updated by the shop itself.',
                        'phase' => ProjectPhase::Scope,
                        'launch_in_days' => 70,
                    ],
                ],
            ],
            [
                'name' => 'Fietsatelier Noord',
                'website' => 'https://fietsatelier.example',
                'projects' => [
                    [
                        'name' => 'Repair booking',
                        'description' => 'Book a repair slot and get a text when the bike is ready.',
                        'phase' => ProjectPhase::Proofmark,
                        'launch_in_days' => 21,
                    ],
                ],
            ],
            [
                'name' => 'Studio Lumen Yoga',
                'website' => 'https://lumen.example',
                'projects' => [
                    [
                        'name' => 'Class timetable',
                        'description' => 'A calm, fast timetable that works on any phone.',
                        'phase' => ProjectPhase::Palette,
                        'launch_in_days' => 42,
                    ],
                    [
                        'name' => 'Brand refresh',
                        'description' => 'New colors and type, live since spring.',
                        'phase' => ProjectPhase::Launched,
                        'launch_in_days' => -60,
                    ],
                ],
            ],
        ];
    }
}
