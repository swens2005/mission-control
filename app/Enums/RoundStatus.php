<?php

namespace App\Enums;

/**
 * Where a Proofmark review round is: being prepared by the studio, with the
 * client, replaced by a newer round, or approved by the client.
 */
enum RoundStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Superseded = 'superseded';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In review',
            self::Superseded => 'Superseded',
            self::Approved => 'Approved',
        };
    }
}
