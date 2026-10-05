<?php

namespace Database\Factories;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerProfile>
 */
class SellerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->regexify('[a-z]{6,12}'),
            'display_name' => fake()->name(),
            'bio' => fake()->sentence(),
            'country' => 'ID',
            'paypal_email' => fake()->safeEmail(),
            'portfolio_url' => fake()->url(),
            'status' => SellerStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => SellerStatus::Approved, 'approved_at' => now()]);
    }

    public function paypalConnected(): static
    {
        return $this->state(fn () => [
            'paypal_merchant_id' => strtoupper(fake()->unique()->bothify('??##??##??##?')),
            'paypal_payments_receivable' => true,
            'paypal_email_confirmed' => true,
            'paypal_connected_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => SellerStatus::Suspended]);
    }
}
