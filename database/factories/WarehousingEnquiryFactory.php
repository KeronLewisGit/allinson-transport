<?php

namespace Database\Factories;

use App\Enums\GoodsType;
use App\Enums\StorageDuration;
use App\Enums\WarehousingStatus;
use App\Models\WarehousingEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WarehousingEnquiry> */
class WarehousingEnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => WarehousingStatus::New,
            'company' => 'Sample Flooring Ltd (sample)',
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '0191 '.fake()->numerify('### ####'),
            'pallet_spaces' => 120,
            'duration' => StorageDuration::ThreeToSix,
            'goods_type' => GoodsType::cases()[0],
            'goods_description' => 'Rolls of vinyl flooring on long pallets',
            'handling' => ['storage', 'distribution'],
        ];
    }
}
