<?php

namespace App\Enums;

/**
 * What a brand color is for. Decides which contrast checks apply
 * (story 21): text and accents need 4.5:1 on surfaces, shape-only colors
 * (like codelaunch.nl's lime) need 3:1 as graphics and are never text.
 */
enum ColorRole: string
{
    case Text = 'text';
    case Surface = 'surface';
    case Accent = 'accent';
    case Shape = 'shape';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Surface => 'Surface',
            self::Accent => 'Accent',
            self::Shape => 'Shapes only',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Text => 'Body copy and headings.',
            self::Surface => 'Backgrounds and cards.',
            self::Accent => 'Links and buttons; must also work as text.',
            self::Shape => 'Icons, bars and decoration; never text.',
        };
    }
}
