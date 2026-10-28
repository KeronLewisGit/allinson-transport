<?php

namespace App\Enums;

enum StorageDuration: string
{
    case UnderMonth = 'under_1m';
    case OneToThree = '1_3m';
    case ThreeToSix = '3_6m';
    case SixToTwelve = '6_12m';
    case Ongoing = 'ongoing';

    public function label(): string
    {
        return match ($this) {
            self::UnderMonth => 'Under a month',
            self::OneToThree => '1 to 3 months',
            self::ThreeToSix => '3 to 6 months',
            self::SixToTwelve => '6 to 12 months',
            self::Ongoing => 'Over a year or ongoing',
        };
    }
}
