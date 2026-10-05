<?php

namespace App\Support;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

/**
 * Remembers who brought the buyer:
 * - a seller (?ref=username or storefront visit) → that seller's items get the low "own traffic" fee;
 * - an affiliate (?aff=code) → earns a share of the platform fee on marketplace sales.
 */
class Attribution
{
    public static function remember(string $username): void
    {
        $profile = SellerProfile::query()
            ->where('username', strtolower($username))
            ->where('status', SellerStatus::Approved)
            ->first(['user_id']);

        if ($profile) {
            self::queue(config('marketplace.attribution.cookie'), $profile->user_id);
        }
    }

    public static function rememberAffiliate(string $code): void
    {
        $userId = User::where('affiliate_code', strtolower($code))->whereNull('suspended_at')->value('id');

        if ($userId) {
            self::queue(config('marketplace.attribution.affiliate_cookie'), $userId);
        }
    }

    /** Seller user id credited for this request, if any. */
    public static function sellerId(): ?int
    {
        return self::read(config('marketplace.attribution.cookie'));
    }

    public static function affiliateId(): ?int
    {
        return self::read(config('marketplace.attribution.affiliate_cookie'));
    }

    private static function queue(string $name, int $userId): void
    {
        Cookie::queue($name, (string) $userId, config('marketplace.attribution.days') * 24 * 60);
    }

    private static function read(string $name): ?int
    {
        $value = request()->cookie($name);

        return ctype_digit((string) $value) ? (int) $value : null;
    }
}
