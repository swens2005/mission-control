<?php

namespace App\Enums;

/**
 * Features a client can tick on the intake.
 */
enum IntakeFeature: string
{
    case ContactForm = 'contact_form';
    case Shop = 'shop';
    case Booking = 'booking';
    case Blog = 'blog';
    case Newsletter = 'newsletter';
    case Languages = 'languages';
    case Search = 'search';

    public function label(): string
    {
        return match ($this) {
            self::ContactForm => 'Contact form',
            self::Shop => 'Online shop',
            self::Booking => 'Booking',
            self::Blog => 'Blog or news',
            self::Newsletter => 'Newsletter',
            self::Languages => 'Multiple languages',
            self::Search => 'Search',
        };
    }
}
