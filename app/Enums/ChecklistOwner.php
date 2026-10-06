<?php

namespace App\Enums;

/**
 * Who is responsible for a launch checklist item.
 */
enum ChecklistOwner: string
{
    case Studio = 'studio';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Studio => 'Studio',
            self::Client => 'Client',
        };
    }
}
