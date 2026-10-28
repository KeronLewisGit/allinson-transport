<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\JobRole;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobApplication> */
class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role' => JobRole::CeDriver,
            'depot' => 'darlington',
            'status' => ApplicationStatus::New,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '07700 900'.fake()->numerify('###'),
            'email' => fake()->safeEmail(),
            'experience' => '5',
            'licence_categories' => ['B', 'C', 'CE'],
            'cpc_expires_on' => today()->addYears(2),
            'tacho_card_expires_on' => today()->addYears(3),
            'licence_points' => 0,
            'right_to_work' => true,
            'consented_at' => now(),
        ];
    }

    public function office(): static
    {
        return $this->state(['role' => JobRole::TransportOffice, 'licence_categories' => ['B'], 'cpc_expires_on' => null, 'tacho_card_expires_on' => null, 'licence_points' => null]);
    }
}
