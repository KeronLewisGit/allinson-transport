<?php

namespace App\Enums;

enum JobRole: string
{
    case CeDriver = 'ce_driver';
    case HiabDriver = 'hiab_driver';
    case Warehouse = 'warehouse';
    case TransportOffice = 'transport_office';

    public function label(): string
    {
        return match ($this) {
            self::CeDriver => 'C+E (Class 1) driver',
            self::HiabDriver => 'Hiab driver',
            self::Warehouse => 'Warehouse operative',
            self::TransportOffice => 'Transport office',
        };
    }

    public function blurb(): string
    {
        return match ($this) {
            self::CeDriver => 'Articulated trunking and distribution across the UK.',
            self::HiabDriver => 'Lorry-mounted crane deliveries and crane-lifted loads.',
            self::Warehouse => 'Goods in, storage, picking and loading, often on forklifts.',
            self::TransportOffice => 'Traffic planning, customer service and administration.',
        };
    }

    /** Driving roles ask for licence, Driver CPC and tacho card details. */
    public function drives(): bool
    {
        return in_array($this, [self::CeDriver, self::HiabDriver], true);
    }

    /** Office roles ask for a CV. */
    public function needsCv(): bool
    {
        return $this === self::TransportOffice;
    }

    public function icon(): string
    {
        return match ($this) {
            self::CeDriver => 'truck',
            self::HiabDriver => 'crane',
            self::Warehouse => 'warehouse',
            self::TransportOffice => 'headset',
        };
    }
}
