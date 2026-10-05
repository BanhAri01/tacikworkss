@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Seller dashboard')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ __('Seller dashboard') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $profile->display_name }} ·
                    <a href="{{ route('shop.show', $profile->username) }}" class="underline">{{ route('shop.show', $profile->username) }}</a>
                </flux:text>
            </div>
            <flux:button :href="route('seller.products.create')" variant="primary" icon="plus" wire:navigate>{{ __('New product') }}</flux:button>
        </div>

        <x-flash-status />
        @error('paypal') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <div @class([
            'rounded-xl border p-5',
            'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950' => $profile->canReceivePayments(),
            'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950' => ! $profile->canReceivePayments(),
        ])>
            @if ($profile->canReceivePayments())
                <p class="font-medium">{{ __('PayPal connected') }}</p>
                <p class="mt-1 text-sm">{{ __('Buyers pay you directly; our fee is deducted automatically by PayPal. Merchant ID: :id', ['id' => $profile->paypal_merchant_id]) }}</p>
            @else
                <p class="font-medium">{{ __('Connect PayPal to start selling') }}</p>
                <p class="mt-1 text-sm">{{ __('You need a PayPal Business account. Payments go straight to you — we never hold your money.') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('seller.paypal.connect') }}">
                        @csrf
                        <flux:button type="submit" variant="primary">{{ $profile->paypal_merchant_id ? __('Finish PayPal setup') : __('Connect PayPal') }}</flux:button>
                    </form>
                    @if ($profile->paypal_merchant_id)
                        <flux:button :href="route('seller.paypal.return')">{{ __('Check status') }}</flux:button>
                    @endif
                </div>
            @endif
            <p class="mt-3 text-xs text-zinc-600 dark:text-zinc-400">
                {{ __('Platform fee: :own when buyers come from your shop or ?ref=:user links, :mp from the marketplace.', [
                    'own' => Money::percent(\App\Models\Setting::int('fee_own_bps')),
                    'mp' => Money::percent(\App\Models\Setting::int('fee_marketplace_bps')),
                    'user' => $profile->username,
                ]) }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('Earned (all time)'), Money::format($netTotal)],
                [__('Earned this month'), Money::format($netThisMonth)],
                [__('Sales'), number_format($salesCount)],
                [__('Products'), number_format($productsCount)],
            ] as [$label, $value])
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <section>
            <flux:heading size="lg">{{ __('Recent sales') }}</flux:heading>
            <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-2">{{ __('Item') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('Price') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('Buyer fee') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('PayPal fee') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('Platform fee') }}</th>
                            <th class="px-4 py-2 text-right">{{ __('You got') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($recent as $item)
                            <tr>
                                <td class="px-4 py-2">
                                    {{ $item->product_title }}
                                    <span class="block text-xs text-zinc-500">{{ $item->license_name }} · {{ $item->order->paid_at?->toFormattedDateString() }} · {{ $item->attribution === \App\Enums\TrafficSource::Own ? __('your traffic') : __('marketplace') }}</span>
                                </td>
                                <td class="px-4 py-2 text-right">{{ Money::format($item->price_cents) }}</td>
                                <td class="px-4 py-2 text-right">+{{ Money::format($item->buyer_fee_cents) }}</td>
                                <td class="px-4 py-2 text-right">−{{ Money::format($item->payment_fee_cents) }}</td>
                                <td class="px-4 py-2 text-right">−{{ Money::format($item->platform_fee_cents) }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ Money::format($item->seller_net_cents) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">{{ __('No sales yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
