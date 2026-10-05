<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\License>
 */
class LicenseFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return ['name' => $name, 'slug' => Str::slug($name), 'description' => fake()->sentence()];
    }
}
