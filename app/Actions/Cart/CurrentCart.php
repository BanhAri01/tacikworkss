<?php

namespace App\Actions\Cart;

use App\Models\Cart;

/** The visitor's cart: one per user, or a guest cart whose id lives in the session. */
class CurrentCart
{
    public const SESSION_KEY = 'cart_id';

    public function get(bool $create = false): ?Cart
    {
        if ($user = auth()->user()) {
            return $create
                ? Cart::firstOrCreate(['user_id' => $user->id])
                : Cart::where('user_id', $user->id)->first();
        }

        $cart = ($id = session(self::SESSION_KEY))
            ? Cart::whereKey($id)->whereNull('user_id')->first()
            : null;

        if (! $cart && $create) {
            $cart = Cart::create();
            session([self::SESSION_KEY => $cart->id]);
        }

        return $cart;
    }

    public function count(): int
    {
        return $this->get()?->items()->count() ?? 0;
    }
}
