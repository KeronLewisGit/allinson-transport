<?php

namespace App\Enums;

enum ReferenceStatus: string
{
    case NotRequested = 'not_requested';
    case Requested = 'requested';
    case Received = 'received';
    case Concerns = 'concerns';

    public function label(): string
    {
        return match ($this) {
            self::NotRequested => 'Not requested',
            self::Requested => 'Requested',
            self::Received => 'Received, satisfactory',
            self::Concerns => 'Received, with concerns',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Received => 'good',
            self::Concerns => 'critical',
            self::Requested => 'attention',
            self::NotRequested => 'neutral',
        };
    }
}
