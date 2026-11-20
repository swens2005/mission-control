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
     * One plain sentence for the client about what is happening now.
     */
    public function clientSummary(): string
    {
        return match ($this) {
            self::Scope => "We're agreeing on what to build and what it costs.",
            self::Palette => "We're choosing your colors and type.",
            self::Proofmark => "We're refining the designs with your comments.",
            self::Launch => "We're running the last checks before launch.",
            self::Launched => 'Your site is live.',
        };
    }

    /**
     * Every phase in order, for the phase rail (Launched is the fifth step).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $phase) => ['value' => $phase->value, 'label' => $phase->label()],
            self::cases(),
        );
    }

    /**
     * 1-based step number; Launched counts as past the last step.
     */
    public function step(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }
}
