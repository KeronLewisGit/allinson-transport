<?php

namespace App\Enums;

enum WarehousingStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Proposal => 'Proposal sent',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'attention',
            self::Won => 'good',
            self::Lost => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'dot',
            self::Contacted => 'phone',
            self::Proposal => 'file',
            self::Won => 'check-circle',
            self::Lost => 'x',
        };
    }
}
