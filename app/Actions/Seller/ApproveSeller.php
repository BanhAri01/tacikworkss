<?php

namespace App\Actions\Seller;

use App\Enums\SellerStatus;
use App\Enums\UserRole;
use App\Models\SellerProfile;
use App\Notifications\SellerApproved;
use Illuminate\Support\Facades\DB;

class ApproveSeller
{
    public function handle(SellerProfile $profile): void
    {
        if ($profile->isApproved()) {
            return;
        }

        $firstApproval = $profile->approved_at === null;

        DB::transaction(function () use ($profile) {
            $profile->update([
                'status' => SellerStatus::Approved,
                'approved_at' => $profile->approved_at ?? now(),
            ]);

            $profile->user->assignRole(UserRole::Seller);
        });

        if ($firstApproval) {
            $profile->user->notify(new SellerApproved);
        }
    }
}
