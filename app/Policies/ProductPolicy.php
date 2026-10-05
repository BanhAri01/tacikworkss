<?php

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->isApprovedSeller();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isApprovedSeller() && $product->seller_id === $user->id;
    }

    public function submit(User $user, Product $product): bool
    {
        return $this->update($user, $product)
            && in_array($product->status, [ProductStatus::Draft, ProductStatus::Rejected], true);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function moderate(User $user, Product $product): bool
    {
        return $user->isAdmin() && $product->status === ProductStatus::Pending;
    }

    public function feature(User $user, Product $product): bool
    {
        return $user->isAdmin() && $product->status === ProductStatus::Approved;
    }
}
