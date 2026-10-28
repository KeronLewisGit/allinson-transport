<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case New = 'new';
    case Pricing = 'pricing';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case NotTaken = 'not_taken';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Pricing => 'Pricing',
            self::Quoted => 'Quote sent',
            self::Accepted => 'Accepted',
            self::NotTaken => 'Not taken',
            self::Declined => 'We declined',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Accepted => 'good',
            self::NotTaken, self::Declined => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Pricing => 'calculator',
            self::Quoted => 'mail',
            self::Accepted => 'check-circle',
            self::NotTaken, self::Declined => 'x',
        };
    }

    /** Waiting for a price from traffic. */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::New, self::Pricing];
    }
}
