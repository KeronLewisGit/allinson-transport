<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobRole;
use App\Enums\JobStatus;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JobPosting> */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        $title = 'C+E driver, nights '.fake()->unique()->numberBetween(1, 9999);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'role' => JobRole::CeDriver,
            'depot' => 'darlington',
            'employment_type' => EmploymentType::FullTime,
            'status' => JobStatus::Open,
            'pay' => null,
            'hours' => 'Nights',
            'openings' => 1,
            'summary' => 'Trunking between depots.',
            'duties' => "Night trunking\nDaily walkaround checks",
            'requirements' => "C+E licence\nDriver CPC and digital tacho card",
        ];
    }
}
