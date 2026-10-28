<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Temporary = 'temporary';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full time, permanent',
            self::PartTime => 'Part time',
            self::Temporary => 'Temporary',
        };
    }

    /** schema.org JobPosting employmentType */
    public function schema(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Temporary => 'TEMPORARY',
        };
    }
}
