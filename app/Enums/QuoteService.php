<?php

namespace App\Enums;

enum QuoteService: string
{
    case FullLoad = 'full_load';
    case PartLoad = 'part_load';
    case Pallets = 'pallets';
    case Hiab = 'hiab';
    case Abnormal = 'abnormal';
    case Warehousing = 'warehousing';

    public function label(): string
    {
        return match ($this) {
            self::FullLoad => 'Full load',
            self::PartLoad => 'Part load',
            self::Pallets => 'Pallets',
            self::Hiab => 'Hiab (crane offload)',
            self::Abnormal => 'Abnormal or out-of-gauge load',
            self::Warehousing => 'Store with us, then deliver',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::FullLoad => 'A whole trailer, door to door',
            self::PartLoad => 'Shares a trailer with other loads',
            self::Pallets => 'One or more pallets',
            self::Hiab => 'Lorry-mounted crane at delivery or collection',
            self::Abnormal => 'Long, wide, high or heavy',
            self::Warehousing => 'Storage at a depot before delivery',
        };
    }

    /** Pallet count matters for these. */
    public function countsPallets(): bool
    {
        return in_array($this, [self::PartLoad, self::Pallets, self::Warehousing], true);
    }

    /** Length × width × height is needed to price these. */
    public function needsDimensions(): bool
    {
        return in_array($this, [self::Hiab, self::Abnormal], true);
    }
}
