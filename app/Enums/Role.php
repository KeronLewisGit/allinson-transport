<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Traffic = 'traffic';
    case Warehouse = 'warehouse';
    case Hr = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Traffic => 'Traffic office',
            self::Warehouse => 'Warehouse',
            self::Hr => 'HR',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Everything, including staff accounts.',
            self::Traffic => 'Haulage quote requests and website enquiries.',
            self::Warehouse => 'Warehousing enquiries.',
            self::Hr => 'Driver and staff recruitment, job postings and staff compliance (CPC and tacho cards).',
        };
    }

    /**
     * Back-office areas this role works in.
     *
     * @return list<Area>
     */
    public function areas(): array
    {
        return match ($this) {
            self::Admin => Area::cases(),
            self::Traffic => [Area::Quotes, Area::Enquiries],
            self::Warehouse => [Area::Warehousing],
            self::Hr => [Area::Recruitment, Area::Compliance],
        };
    }
}
