<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\License;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(3, true));

        return [
            'seller_id' => fn () => SellerProfile::factory()->approved()->create()->user->assignRole(UserRole::Seller)->id,
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::Draft,
            'file_formats' => ['PSD'],
            'compatible_software' => ['Photoshop'],
            'version' => '1.0',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => ProductStatus::Approved, 'published_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(['status' => ProductStatus::Pending, 'submitted_at' => now()]);
    }

    public function free(): static
    {
        return $this->state(['is_free' => true, 'min_price_cents' => 0]);
    }

    public function forSeller(User $seller): static
    {
        return $this->state(['seller_id' => $seller->id]);
    }

    /** Attach a price for the given license (created if omitted). */
    public function priced(int $cents, ?License $license = null): static
    {
        return $this->state(['min_price_cents' => $cents])->afterCreating(function (Product $product) use ($cents, $license) {
            $product->licensePrices()->create([
                'license_id' => ($license ?? License::factory()->create())->id,
                'price_cents' => $cents,
            ]);
        });
    }
}
