<?php

namespace App\Actions\Seller;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;

class SuspendSeller
{
    public function handle(SellerProfile $profile): void
    {
        // Seller role is kept so history stays intact; panel access is gated on approved status.
        $profile->update(['status' => SellerStatus::Suspended]);
    }
}
