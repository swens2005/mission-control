<?php

namespace App\Support\Sandbox;

use App\Enums\ColorRole;
use App\Enums\IntakeBudget;
use App\Enums\LineItemKind;
use App\Enums\ProjectPhase;
use App\Enums\RoundStatus;

/**
 * The studio every demo visitor starts with: four clients and eight
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
     * Scope's demo (story 29): the studio's price list, and Bakkerij de
     * Vries's "Seasonal menu pages" part-way through: the intake sent, v1
     * sent with Anna's request for Easter pages, and v2 as a draft with
     * them added, ready for the full-story tour to send and accept.
     *
     * Prices in cents, quantities in hundredths (ADR 0010). Lines name a
     * library item and may override its name, description or quantity.
     *
     * @return array{
     *     project: string,
     *     library: list<array{0: string, 1: string, 2: LineItemKind, 3: int, 4: int}>,
     *     intake: array{goals: string, pages: list<string>, features: list<string>, budget: IntakeBudget, deadline_in_days: int, deadline_reason: string},
     *     title: string,
     *     introduction: string,
     *     discount: array{basis_points: int, label: string},
     *     v1: list<array{item: string, name?: string, description?: string, quantity: int}>,
     *     changes_note: string,
     *     v2: list<array{item: string, name?: string, description?: string, quantity: int}>
     * }
     */
    public static function scope(): array
    {
        return [
            'project' => 'Seasonal menu pages',
            'library' => [
                ['Design: home page and templates', 'The look of the site, the home page and the page templates.', LineItemKind::Fixed, 180000, 100],
                ['Seasonal page', 'A page with its own photos, menu and pre-order link.', LineItemKind::Fixed, 45000, 100],
                ['Extra page', 'A standard content page in the existing templates.', LineItemKind::Fixed, 35000, 100],
                ['Online pre-order form', 'Pick-up day, items and a confirmation email.', LineItemKind::Fixed, 65000, 100],
                ['Content and copywriting', 'Writing and editing the text with you.', LineItemKind::Hourly, 8500, 400],
                ['Photo editing', 'Cropping and colour-correcting your product photos.', LineItemKind::Hourly, 6500, 200],
                ['Hosting setup and launch', 'Domain, SSL, backups and going live.', LineItemKind::Fixed, 25000, 100],
                ['Training: updating the site yourself', 'A session on changing menus and photos each season.', LineItemKind::Hourly, 8500, 150],
            ],
            'intake' => [
                'goals' => "Customers should see our Christmas and New Year specials and pre-order them online, so we don't have to take every order by phone.\n\nWe'd like to change the pages ourselves each season.",
                'pages' => ['Christmas specials', "New Year's treats"],
                'features' => ['contact_form'],
                'budget' => IntakeBudget::From2500,
                'deadline_in_days' => 45,
                'deadline_reason' => 'Before the Christmas pre-orders open',
            ],
            'title' => 'Seasonal menu pages',
            'introduction' => "Hi Anna,\n\nThanks for the intake. Here's what we propose: a page per season with its own photos and pre-order link, written together with you, and a short training so you can update them yourself.",
            'discount' => ['basis_points' => 1000, 'label' => 'Returning client'],
            'v1' => [
                ['item' => 'Seasonal page', 'description' => 'Christmas specials and New Year’s treats, each with its own photos and pre-order link.', 'quantity' => 200],
                ['item' => 'Content and copywriting', 'quantity' => 400],
                ['item' => 'Photo editing', 'quantity' => 250],
                ['item' => 'Training: updating the site yourself', 'quantity' => 150],
            ],
            'changes_note' => 'Could you add the Easter pages too? We do hot cross buns and Easter bread every spring.',
            'v2' => [
                ['item' => 'Seasonal page', 'description' => 'Christmas specials and New Year’s treats, each with its own photos and pre-order link.', 'quantity' => 200],
                ['item' => 'Seasonal page', 'name' => 'Easter pages', 'description' => 'Easter bread and hot cross buns, with their own photos and pre-order link.', 'quantity' => 200],
                ['item' => 'Content and copywriting', 'quantity' => 600],
                ['item' => 'Photo editing', 'quantity' => 350],
                ['item' => 'Training: updating the site yourself', 'quantity' => 150],
            ],
        ];
    }

    /**
     * Palette Lab's demo (story 24), per project.
     *
     * - codelaunch.nl: the app's own palette from resources/css/themes.css,
     *   except the screen label, which is the CV site's original blue. It
     *   fails on the console screen colors, as does the brand green on the
     *   screen: the two colors the app really had to darken (ADR 0008).
     *   "Fix it" shows how. Not shared: a studio showcase.
     * - Café corner: Bakkerij de Vries's kit, shared and waiting for the
     *   demo client's approval. Every text pair passes.
     *
     * @return array<string, array{shared: bool, heading: string, body: string, ratio: int, colors: list<array{0: string, 1: ColorRole, 2: string}>}>
     */
    public static function palette(): array
    {
        return [
            'codelaunch.nl' => [
                'shared' => false,
                'heading' => 'bricolage',
                'body' => 'figtree',
                'ratio' => 1250,
                'colors' => [
                    ['Ink', ColorRole::Text, '#10233a'],
                    ['Soft', ColorRole::Text, '#4a5a6c'],
                    ['Green', ColorRole::Accent, '#4a7300'],
                    ['Screen label', ColorRole::Text, '#4a7fc4'],
                    ['Lime', ColorRole::Shape, '#8ac800'],
                    ['Cream', ColorRole::Surface, '#f5f2ea'],
                    ['Card', ColorRole::Surface, '#fffdf8'],
                    ['Sky', ColorRole::Surface, '#d4edfa'],
                    ['Screen', ColorRole::Surface, '#cfe8fa'],
                ],
            ],
            'Café corner' => [
                'shared' => true,
                'heading' => 'system-serif',
                'body' => 'figtree',
                'ratio' => 1333,
                'colors' => [
                    ['Bakery brown', ColorRole::Text, '#4a2c1a'],
                    ['Caramel', ColorRole::Accent, '#8a5a2b'],
                    ['Wheat', ColorRole::Shape, '#d9a441'],
                    ['Rosé', ColorRole::Shape, '#e98f9b'],
                    ['Cream', ColorRole::Surface, '#f7efe3'],
                    ['Paper', ColorRole::Surface, '#fffaf2'],
                ],
            ],
        ];
    }

    /**
     * Proofmark's demo (story 19), per project: rounds oldest first. Images
     * are files shipped in resources/demo/proofmark/ (never copied, so a
     * sandbox adds nothing to the disk). Pins are in hundredths of a
     * percent; "by" is the demo admin or the demo client.
     *
     * @return array<string, list<array{status: RoundStatus, sent_days_ago: int|null, designs: list<array{title: string, file: string}>, comments: list<array{design: int, x: int, y: int, by: 'admin'|'client', body: string, resolved: bool}>}>>
     */
    public static function proofmark(): array
    {
        return [
            'Wedding cakes' => [
                [
                    'status' => RoundStatus::Superseded,
                    'sent_days_ago' => 12,
                    'designs' => [
                        ['title' => 'Home, desktop', 'file' => 'wedding-home-desktop-v1.webp'],
                        ['title' => 'Order page, desktop', 'file' => 'wedding-order-desktop-v1.webp'],
                    ],
                    'comments' => [
                        ['design' => 0, 'x' => 7300, 'y' => 2300, 'by' => 'client', 'resolved' => true,
                            'body' => 'The pink feels more like a baby shower than a wedding. Could it be warmer, like the shop?'],
                        ['design' => 0, 'x' => 2200, 'y' => 7200, 'by' => 'client', 'resolved' => true,
                            'body' => 'Can we show prices? People always ask us that first.'],
                        ['design' => 1, 'x' => 3000, 'y' => 4500, 'by' => 'admin', 'resolved' => true,
                            'body' => 'Suggest splitting this form into steps, so it feels less like a tax return.'],
                    ],
                ],
                [
                    'status' => RoundStatus::InReview,
                    'sent_days_ago' => 2,
                    'designs' => [
                        ['title' => 'Home, desktop', 'file' => 'wedding-home-desktop-v2.webp'],
                        ['title' => 'Home, mobile', 'file' => 'wedding-home-mobile-v2.webp'],
                        ['title' => 'Order page, desktop', 'file' => 'wedding-order-desktop-v2.webp'],
                        ['title' => 'Order page, mobile', 'file' => 'wedding-order-mobile-v2.webp'],
                    ],
                    'comments' => [
                        ['design' => 0, 'x' => 8500, 'y' => 1350, 'by' => 'client', 'resolved' => false,
                            'body' => "Love this! Could the badge say 'Saturday mornings'? We close at one."],
                        ['design' => 2, 'x' => 8000, 'y' => 6000, 'by' => 'client', 'resolved' => false,
                            'body' => 'The deposit is 25%, not 30%.'],
                        ['design' => 0, 'x' => 7400, 'y' => 2200, 'by' => 'admin', 'resolved' => false,
                            'body' => "A photo of the real cakes goes here once Anna's photographer delivers."],
                    ],
                ],
            ],
            'Repair booking' => [
                [
                    'status' => RoundStatus::Draft,
                    'sent_days_ago' => null,
                    'designs' => [
                        ['title' => 'Booking, desktop', 'file' => 'repair-booking-desktop.webp'],
                    ],
                    'comments' => [],
                ],
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
                        'name' => 'Wedding cakes',
                        'description' => 'A showcase for wedding cakes, with tastings and an order form.',
                        'phase' => ProjectPhase::Proofmark,
                        'launch_in_days' => 35,
                    ],
                    [
                        'name' => 'Café corner',
                        'description' => 'A small café next to the bakery: menu, opening hours and the terrace.',
                        'phase' => ProjectPhase::Palette,
                        'launch_in_days' => 56,
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
            [
                'name' => 'Orbit Web Studio (in-house)',
                'website' => 'https://codelaunch.nl',
                'projects' => [
                    [
                        'name' => 'codelaunch.nl',
                        'description' => "Meagan Swenson's portfolio, and the look of this app (ADR 0008).",
                        'phase' => ProjectPhase::Launched,
                        'launch_in_days' => -120,
                    ],
                ],
            ],
        ];
    }
}
