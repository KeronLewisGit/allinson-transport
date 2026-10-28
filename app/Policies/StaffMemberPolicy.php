<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\StaffMember;
use App\Models\User;

/** HR and administrators. */
class StaffMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Compliance);
    }

    public function view(User $user, StaffMember $staff): bool
    {
        return $user->works(Area::Compliance);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Compliance);
    }

    public function update(User $user, StaffMember $staff): bool
    {
        return $user->works(Area::Compliance);
    }
}
