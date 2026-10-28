<?php

namespace App\Enums;

enum EnquiryTopic: string
{
    case Benchmark = 'benchmark';
    case Existing = 'existing';
    case General = 'general';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Benchmark => 'A free logistics benchmark',
            self::Existing => 'An existing delivery or account',
            self::General => 'General question',
            self::Other => 'Something else',
        };
    }
}
