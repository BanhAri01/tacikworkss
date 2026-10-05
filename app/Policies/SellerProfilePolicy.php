<?php

namespace App\Policies;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\User;

class SellerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SellerProfile $profile): bool
    {
        return $user->isAdmin() || $user->id === $profile->user_id;
    }

    public function approve(User $user, SellerProfile $profile): bool
    {
        return $user->isAdmin() && $profile->status !== SellerStatus::Approved;
    }

    public function suspend(User $user, SellerProfile $profile): bool
    {
        return $user->isAdmin()
            && $profile->status === SellerStatus::Approved
            && $profile->user_id !== $user->id;
    }
}
