<?php

namespace App\Enums;

/** Broad goods types for warehousing, after the sectors the firm's own site lists. */
enum GoodsType: string
{
    case Retail = 'retail';
    case Polymers = 'polymers';
    case Flooring = 'flooring';
    case Industrial = 'industrial';
    case Automotive = 'automotive';
    case Food = 'food';
    case Containers = 'containers';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Retail => 'Consumer and retail products',
            self::Polymers => 'Polymers and plastics',
            self::Flooring => 'Flooring',
            self::Industrial => 'Industrial components or materials',
            self::Automotive => 'Automotive parts',
            self::Food => 'Food or food packaging (ambient)',
            self::Containers => 'Import container cargo',
            self::Other => 'Something else',
        };
    }
}
