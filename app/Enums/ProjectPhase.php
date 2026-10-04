<?php

namespace App\Enums;

/**
 * The four modules, in the order a website project moves through them.
 */
enum ProjectPhase: string
{
    case Scope = 'scope';
    case Palette = 'palette';
    case Proofmark = 'proofmark';
    case Launch = 'launch';
    case Launched = 'launched';

    public function label(): string
    {
        return match ($this) {
            self::Scope => 'Scope',
            self::Palette => 'Palette Lab',
            self::Proofmark => 'Proofmark',
            self::Launch => 'Launch Control',
            self::Launched => 'Launched',
        };
    }

    /**
     * 1-based step number; Launched counts as past the last step.
     */
    public function step(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }
}
