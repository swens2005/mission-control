<?php

namespace App\Support\Launch\Checks;

/**
 * Every automated launch check, in display order.
 */
final class CheckRegistry
{
    /**
     * @return list<LaunchCheck>
     */
    public static function all(): array
    {
        return [
            new HttpsCheck,
            new HstsCheck,
            new CspCheck,
            new SecurityHeadersCheck,
            new TitleDescriptionCheck,
            new OpenGraphImageCheck,
            new ImageAltCheck,
            new HeadingsCheck,
            new RobotsSitemapCheck,
            new PerformanceCheck,
        ];
    }

    /**
     * key => label, for showing waivers and older results.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::all() as $check) {
            $labels[$check->key()] = $check->label();
        }

        return $labels;
    }
}
