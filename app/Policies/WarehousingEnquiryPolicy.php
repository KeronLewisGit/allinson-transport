<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\User;
use App\Models\WarehousingEnquiry;

/** The warehouse team and administrators. */
class WarehousingEnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Warehousing);
    }

    public function view(User $user, WarehousingEnquiry $enquiry): bool
    {
        return $user->works(Area::Warehousing);
    }

    public function update(User $user, WarehousingEnquiry $enquiry): bool
    {
        return $user->works(Area::Warehousing);
    }
}
