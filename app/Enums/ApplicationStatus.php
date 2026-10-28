<?php

namespace App\Enums;

/** The recruitment pipeline, in order. */
enum ApplicationStatus: string
{
    case New = 'new';
    case Screening = 'screening';
    case Checks = 'checks';
    case Assessment = 'assessment';
    case Offer = 'offer';
    case Hired = 'hired';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Screening => 'Phone screen',
            self::Checks => 'Right to work & references',
            self::Assessment => 'Assessment drive',
            self::Offer => 'Offer made',
            self::Hired => 'Hired',
            self::Declined => 'Not this time',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Hired => 'good',
            self::Declined, self::Withdrawn => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Screening => 'phone',
            self::Checks => 'shield',
            self::Assessment => 'truck',
            self::Offer => 'mail',
            self::Hired => 'check-circle',
            self::Declined, self::Withdrawn => 'x',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Hired, self::Declined, self::Withdrawn], true);
    }
}
