<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class SubmitProduct
{
    public function handle(Product $product): void
    {
        $errors = [];

        if (! $product->previews()->exists()) {
            $errors['previews'] = __('Add at least one preview image.');
        }
        if (! $product->files()->exists()) {
            $errors['zip'] = __('Upload the product file (.zip).');
        }
        if (! $product->is_free && ! $product->licensePrices()->exists()) {
            $errors['prices'] = __('Set a price for at least one license, or mark the product as free.');
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $product->update([
            'status' => ProductStatus::Pending,
            'rejection_reason' => null,
            'submitted_at' => now(),
        ]);
    }
}
