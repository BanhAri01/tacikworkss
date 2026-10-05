<?php

use App\Actions\Cart\CurrentCart;
use App\Actions\Checkout\QuoteCart;
use App\Models\Coupon;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public', ['title' => 'Cart', 'noindex' => true])] class extends Component {
    public string $couponCode = '';

    public function remove(int $cartItemId): void
    {
        app(CurrentCart::class)->get()?->items()->whereKey($cartItemId)->delete();
        $this->dispatch('cart-updated');
    }

    public function applyCoupon(): void
    {
        $this->validate(['couponCode' => ['required', 'string', 'max:40']]);

        $coupon = Coupon::findByCode($this->couponCode);

        if (! $coupon || ! $coupon->isUsable()) {
            $this->addError('couponCode', __('This coupon is not valid.'));

            return;
        }

        app(CurrentCart::class)->get(create: true)->update(['coupon_id' => $coupon->id]);
        $this->reset('couponCode');
    }

    public function removeCoupon(): void
    {
        app(CurrentCart::class)->get()?->update(['coupon_id' => null]);
    }

    public function with(): array
    {
        return ['quote' => app(QuoteCart::class)->handle(app(CurrentCart::class)->get())];
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <h1 class="text-2xl font-bold">{{ __('Cart') }}</h1>

    <x-flash-status class="mt-4" />
    @error('cart') <p class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ $message }}</p> @enderror

    @if ($quote->isEmpty() && $quote->unavailable->isEmpty())
        <p class="mt-8 text-zinc-500">{{ __('Your cart is empty.') }} <a href="{{ route('search') }}" class="underline">{{ __('Browse designs') }}</a></p>
    @else
        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                @foreach ($quote->groups as $group)
                    <section class="rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <header class="border-b border-zinc-200 px-4 py-2 text-sm dark:border-zinc-800">
                            {{ __('Sold by') }} <a href="{{ route('shop.show', $group['profile']->username) }}" class="font-medium underline">{{ $group['profile']->display_name }}</a>
                        </header>
                        <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($group['lines'] as $line)
                                <li wire:key="line-{{ $line['cart_item_id'] }}-{{ $line['product']->id }}" class="flex gap-4 p-4">
                                    <a href="{{ route('products.show', $line['product']) }}" class="h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        @if ($line['product']->cover)
                                            <img src="{{ $line['product']->cover->thumbUrl() }}" alt="" class="size-full object-cover">
                                        @endif
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('products.show', $line['product']) }}" class="block truncate font-medium hover:underline">{{ $line['product']->title }}</a>
                                        <p class="text-sm text-zinc-500">
                                            {{ $line['license']->name }} {{ __('license') }}
                                            @if ($line['bundle']) · {{ __('in bundle “:title”', ['title' => $line['bundle']->title]) }} @endif
                                        </p>
                                        <button type="button" wire:click="remove({{ $line['cart_item_id'] }})" class="mt-1 text-sm text-red-600">{{ $line['bundle'] ? __('Remove bundle') : __('Remove') }}</button>
                                    </div>
                                    <div class="text-right text-sm">
                                        @if ($line['discount'] > 0)
                                            <p class="text-zinc-400 line-through">{{ Money::format($line['list_price']) }}</p>
                                        @endif
                                        <p class="font-semibold">{{ Money::format($line['price']) }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                @if ($quote->unavailable->isNotEmpty())
                    <section class="rounded-xl border border-amber-300 p-4 dark:border-amber-800">
                        <h2 class="text-sm font-medium">{{ __('Unavailable — remove to continue') }}</h2>
                        <ul class="mt-2 space-y-2 text-sm">
                            @foreach ($quote->unavailable as $line)
                                <li wire:key="na-{{ $line['cart_item_id'] }}-{{ $loop->index }}" class="flex justify-between gap-3">
                                    <span>{{ $line['bundle']?->title ?? $line['product']?->title ?? __('Removed item') }} — <span class="text-zinc-500">{{ $line['reason'] }}</span></span>
                                    <button type="button" wire:click="remove({{ $line['cart_item_id'] }})" class="text-red-600">{{ __('Remove') }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="h-fit space-y-4 rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                <form wire:submit="applyCoupon" class="flex gap-2">
                    <input wire:model="couponCode" placeholder="{{ __('Coupon code') }}" aria-label="{{ __('Coupon code') }}" class="min-w-0 flex-1 rounded-lg border border-zinc-300 px-3 py-1.5 uppercase dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="submit" class="rounded-lg border border-zinc-300 px-3 py-1.5 dark:border-zinc-700">{{ __('Apply') }}</button>
                </form>
                @error('couponCode') <p class="text-red-600">{{ $message }}</p> @enderror
                @if ($quote->coupon)
                    <p class="flex justify-between text-green-700 dark:text-green-400">
                        <span>{{ __('Coupon :code', ['code' => $quote->coupon->code]) }}</span>
                        <button type="button" wire:click="removeCoupon" class="underline">{{ __('Remove') }}</button>
                    </p>
                @elseif ($quote->couponError)
                    <p class="flex justify-between text-amber-700"><span>{{ $quote->couponError }}</span> <button type="button" wire:click="removeCoupon" class="underline">{{ __('Remove') }}</button></p>
                @endif

                <dl class="space-y-1 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <div class="flex justify-between"><dt>{{ __('Items') }}</dt><dd>{{ Money::format($quote->subtotal()) }}</dd></div>
                    @if ($quote->discount() > 0)
                        <div class="flex justify-between text-green-700 dark:text-green-400"><dt>{{ __('Discount') }}</dt><dd>−{{ Money::format($quote->discount()) }}</dd></div>
                    @endif
                    <div class="flex justify-between">
                        <dt>{{ __('Processing fee') }} <span class="text-zinc-500" title="{{ __('Covers PayPal\'s fee so designers receive the full price.') }}">ⓘ</span></dt>
                        <dd>{{ Money::format($quote->buyerFee()) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-bold dark:border-zinc-800"><dt>{{ __('Total (USD)') }}</dt><dd>{{ Money::format($quote->total()) }}</dd></div>
                </dl>

                @auth
                    <form method="POST" action="{{ route('checkout.start') }}">
                        @csrf
                        <button type="submit" @disabled($quote->isEmpty() || $quote->unavailable->isNotEmpty())
                                class="w-full rounded-lg bg-[#ffc439] px-4 py-3 font-semibold text-zinc-900 hover:brightness-95 disabled:opacity-50">
                            {{ __('Pay with PayPal') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block w-full rounded-lg bg-zinc-900 px-4 py-3 text-center font-semibold text-white dark:bg-white dark:text-zinc-900">{{ __('Log in to check out') }}</a>
                @endauth
                <p class="text-xs text-zinc-500">{{ __('Each designer is paid directly through PayPal.') }}</p>
            </aside>
        </div>
    @endif
</div>
