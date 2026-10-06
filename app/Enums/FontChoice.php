<?php

namespace App\Enums;

/**
 * Fonts a brand kit can use (story 22): the three the app self-hosts, plus
 * system stacks. No third-party font loading (CSP and visitor privacy).
 */
enum FontChoice: string
{
    case Bricolage = 'bricolage';
    case Figtree = 'figtree';
    case JetBrains = 'jetbrains';
    case SystemSans = 'system-sans';
    case SystemSerif = 'system-serif';
    case SystemMono = 'system-mono';

    public function label(): string
    {
        return match ($this) {
            self::Bricolage => 'Bricolage Grotesque',
            self::Figtree => 'Figtree',
            self::JetBrains => 'JetBrains Mono',
            self::SystemSans => 'System sans-serif',
            self::SystemSerif => 'System serif',
            self::SystemMono => 'System monospace',
        };
    }

    /**
     * The CSS font-family stack, used in the specimen and the exports.
     */
    public function stack(): string
    {
        return match ($this) {
            self::Bricolage => "'Bricolage Grotesque', ui-sans-serif, sans-serif",
            self::Figtree => "'Figtree', ui-sans-serif, system-ui, sans-serif",
            self::JetBrains => "'JetBrains Mono', ui-monospace, monospace",
            self::SystemSans => 'ui-sans-serif, system-ui, sans-serif',
            self::SystemSerif => "ui-serif, Georgia, 'Times New Roman', serif",
            self::SystemMono => 'ui-monospace, Menlo, Consolas, monospace',
        };
    }
}
