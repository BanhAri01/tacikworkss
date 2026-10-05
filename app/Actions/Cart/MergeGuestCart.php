<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** On login, move the guest cart's items into the user's cart. */
class MergeGuestCart
{
    public function handle(User $user): void
    {
        $guestId = session()->pull(CurrentCart::SESSION_KEY);
        $guest = $guestId ? Cart::whereKey($guestId)->whereNull('user_id')->with('items')->first() : null;

        if (! $guest) {
            return;
        }

        DB::transaction(function () use ($user, $guest) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            foreach ($guest->items as $item) {
                $cart->items()->updateOrCreate(['product_id' => $item->product_id], ['license_id' => $item->license_id]);
            }

            if ($guest->coupon_id && ! $cart->coupon_id) {
                $cart->update(['coupon_id' => $guest->coupon_id]);
            }

            $guest->delete();
        });
    }
}
