<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => User::factory(),
            'code' => Str::upper(Str::random(8)),
            'type' => CouponType::Percent,
            'value' => 20,
            'is_active' => true,
        ];
    }
}
