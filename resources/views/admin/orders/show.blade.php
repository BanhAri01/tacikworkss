@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Order')">
    <div class="flex max-w-5xl flex-col gap-6">
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('Orders') }}</a>
        <div>
            <flux:heading size="xl" level="1">{{ __('Order') }} <span class="font-mono text-base">{{ $order->uuid }}</span></flux:heading>
            <p class="mt-1 text-sm text-zinc-500">
                {{ $order->buyer->name }} &lt;{{ $order->buyer->email }}&gt; · {{ ucfirst($order->status->value) }} · {{ $order->gateway }}
                @if ($order->gateway_ref) · PayPal {{ $order->gateway_ref }} @endif
                · {{ $order->paid_at?->toDayDateTimeString() }}
            </p>
        </div>

        <x-flash-status />
        @error('refund') <p class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">{{ $message }}</p> @enderror

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2">{{ __('Item') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Price') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Buyer fee') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('PayPal') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Platform') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Affiliate') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Seller net') }}</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-3 py-2">
                                {{ $item->product_title }}
                                <span class="block text-xs text-zinc-500">
                                    {{ $item->license_name }} · {{ '@'.$item->seller->sellerProfile?->username }} · {{ $item->attribution->value }} ({{ Money::percent($item->fee_bps) }})
                                    <br><span class="font-mono">{{ $item->purchase_code }}</span>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ Money::format($item->price_cents) }}</td>
                            <td class="px-3 py-2 text-right">{{ Money::format($item->buyer_fee_cents) }}</td>
                            <td class="px-3 py-2 text-right">{{ Money::format($item->payment_fee_cents) }}</td>
                            <td class="px-3 py-2 text-right">{{ Money::format($item->platform_fee_cents) }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->affiliate ? Money::format($item->affiliate_commission_cents).' ('.$item->affiliate->name.')' : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ Money::format($item->seller_net_cents) }}</td>
                            <td class="px-3 py-2 text-right">
                                @if ($item->refunded_at)
                                    <span class="text-xs text-zinc-500">{{ __('Refunded :date', ['date' => $item->refunded_at->toFormattedDateString()]) }}</span>
                                @elseif ($order->isPaid())
                                    <form method="POST" action="{{ route('admin.orders.refund', [$order, $item]) }}" onsubmit="return confirm(@js(__('Refund :amount to the buyer from the seller\'s PayPal and revoke the license?', ['amount' => Money::format($item->price_cents + $item->buyer_fee_cents)])))">
                                        @csrf
                                        <button type="submit" class="text-red-600 underline">{{ __('Refund') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <dl class="ml-auto grid w-64 grid-cols-2 gap-1 text-sm">
            <dt class="text-zinc-500">{{ __('Subtotal') }}</dt><dd class="text-right">{{ Money::format($order->subtotal_cents) }}</dd>
            <dt class="text-zinc-500">{{ __('Discount') }}</dt><dd class="text-right">−{{ Money::format($order->discount_cents) }}</dd>
            <dt class="text-zinc-500">{{ __('Processing fee') }}</dt><dd class="text-right">{{ Money::format($order->buyer_fee_cents) }}</dd>
            <dt class="font-semibold">{{ __('Total') }}</dt><dd class="text-right font-semibold">{{ Money::format($order->total_cents) }}</dd>
        </dl>
    </div>
</x-layouts.app>
