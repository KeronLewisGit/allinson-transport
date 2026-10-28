<?php

namespace Database\Factories;

use App\Enums\JobRole;
use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StaffMember> */
class StaffMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => JobRole::CeDriver,
            'depot' => 'darlington',
            'licence_categories' => ['B', 'C', 'CE'],
            'cpc_expires_on' => today()->addYears(2),
            'tacho_card_expires_on' => today()->addYears(3),
            'licence_expires_on' => today()->addYears(5),
            'active' => true,
        ];
    }
}
