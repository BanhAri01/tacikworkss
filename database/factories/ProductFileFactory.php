<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductFile>
 */
class ProductFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'files/'.fake()->uuid().'.zip',
            'original_name' => 'item.zip',
            'size' => 1024,
            'version' => '1.0',
        ];
    }
}
