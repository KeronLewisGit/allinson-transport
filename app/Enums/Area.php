<?php

namespace App\Enums;

/** A part of the back office. Role::areas() says who works where; policies and the nav read it. */
enum Area: string
{
    case Quotes = 'quotes';
    case Warehousing = 'warehousing';
    case Recruitment = 'recruitment';
    case Compliance = 'compliance';
    case Enquiries = 'enquiries';

    public function label(): string
    {
        return match ($this) {
            self::Quotes => 'Haulage quotes',
            self::Warehousing => 'Warehousing enquiries',
            self::Recruitment => 'Recruitment',
            self::Compliance => 'Staff compliance',
            self::Enquiries => 'Enquiries',
        };
    }

    /** Which ALLINSON_NOTIFY_* inbox hears about new submissions in this area. */
    public function inbox(): string
    {
        return $this->value;
    }
}
