<?php

namespace App\Enums;

/** Copies an applicant uploads through the signed documents link before an assessment drive. */
enum ApplicantDocumentKind: string
{
    case LicenceFront = 'licence_front';
    case LicenceBack = 'licence_back';
    case CpcCard = 'cpc_card';
    case TachoCard = 'tacho_card';
    case RightToWork = 'right_to_work';

    public function label(): string
    {
        return match ($this) {
            self::LicenceFront => 'Driving licence photocard (front)',
            self::LicenceBack => 'Driving licence photocard (back)',
            self::CpcCard => 'Driver CPC card (DQC)',
            self::TachoCard => 'Digital tachograph driver card',
            self::RightToWork => 'Right to work: passport, or a share code letter',
        };
    }

    /** @return list<self> */
    public static function forDriving(bool $drives): array
    {
        return $drives ? self::cases() : [self::RightToWork];
    }
}
