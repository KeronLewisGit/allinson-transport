<?php

namespace App\Enums;

enum AssessmentResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Passed',
            self::Failed => 'Not passed',
            self::NoShow => 'Did not attend',
        };
    }

    public function tone(): string
    {
        return $this === self::Passed ? 'good' : 'critical';
    }
}
