<?php

namespace Database\Factories;

use App\Enums\QuoteService;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quote> */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => QuoteStatus::New,
            'service' => QuoteService::Pallets,
            'company' => 'Sample Polymers Ltd (sample)',
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '01325 '.fake()->numerify('######'),
            'collection_postcode' => 'DL1 4PZ',
            'collection_date' => today()->addDays(3),
            'delivery_postcode' => 'GL2 7AA',
            'goods' => 'Bagged polymer granules on pallets',
            'pallets' => 6,
            'weight_kg' => 5400,
        ];
    }

    public function quoted(float $price = 640): static
    {
        return $this->state(['status' => QuoteStatus::Quoted, 'price' => $price, 'valid_until' => today()->addDays(14), 'quoted_at' => now()]);
    }
}
