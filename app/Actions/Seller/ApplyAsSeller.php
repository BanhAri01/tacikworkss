<?php

namespace App\Actions\Seller;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplyAsSeller
{
    /**
     * @param  array{username: string, display_name: string, country: string, paypal_email: string, bio?: ?string, portfolio_url?: ?string}  $data
     */
    public function handle(User $user, array $data): SellerProfile
    {
        $profile = $user->sellerProfile;

        // Only a fresh applicant may (re)submit; approved/suspended profiles are managed by admin.
        if ($profile && $profile->status !== SellerStatus::Pending) {
            throw ValidationException::withMessages(['username' => 'Your seller application has already been processed.']);
        }

        return $user->sellerProfile()->updateOrCreate([], [
            ...$data,
            'username' => strtolower($data['username']),
            'country' => strtoupper($data['country']),
            'status' => SellerStatus::Pending,
        ]);
    }
}
