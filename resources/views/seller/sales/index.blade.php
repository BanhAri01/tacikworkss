@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Sales')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <flux:heading size="xl" level="1">{{ __('Sales') }}</flux:heading>
            <form method="GET" class="flex items-end gap-2">
                <label class="text-sm">
                    <span class="mb-1 block text-zinc-500">{{ __('Month') }}</span>
                    <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" class="rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                </label>
                @if ($month)
                    <a href="{{ route('seller.sales.index') }}" class="pb-2 text-sm underline">{{ __('All time') }}</a>
                @endif
                <flux:button size="sm" icon="arrow-down-tray" :href="route('seller.sales.export', request()->only('month'))">CSV</flux:button>
            </form>
        </div>

        <x-bar-chart :title="__('Net earnings per month (USD)')" :points="$chart" />

        <div class="grid gap-3 text-sm sm:grid-cols-3 lg:grid-cols-6">
            @foreach ([
                [__('Sales'), number_format((int) $totals->n)],
                [__('Item prices'), Money::format((int) $totals->price)],
                [__('Buyer fees'), '+'.Money::format((int) $totals->buyer_fee)],
                [__('PayPal fees'), '−'.Money::format((int) $totals->payment_fee)],
                [__('Platform fees'), '−'.Money::format((int) $totals->platform_fee)],
                [__('You received'), Money::format((int) $totals->net)],
            ] as [$label, $value])
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <p class="text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-lg font-semibold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-2">{{ __('Date') }}</th>
                        <th class="px-4 py-2">{{ __('Item') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('Price') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('Buyer fee') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('PayPal fee') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('Platform fee') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('Net') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($items as $item)
                        <tr @class(['opacity-60' => $item->refunded_at])>
                            <td class="whitespace-nowrap px-4 py-2">{{ $item->order->paid_at?->toFormattedDateString() }}</td>
                            <td class="px-4 py-2">
                                {{ $item->product_title }}
                                <span class="block text-xs text-zinc-500">
                                    {{ $item->license_name }} ·
                                    {{ $item->attribution === \App\Enums\TrafficSource::Own ? __('your traffic') : __('marketplace') }} ({{ Money::percent($item->fee_bps) }})
                                    @if ($item->discount_cents) · {{ __('coupon −:amount', ['amount' => Money::format($item->discount_cents)]) }} @endif
                                    @if ($item->refunded_at) · <strong>{{ __('refunded') }}</strong> @endif
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">{{ Money::format($item->price_cents) }}</td>
                            <td class="px-4 py-2 text-right">+{{ Money::format($item->buyer_fee_cents) }}</td>
                            <td class="px-4 py-2 text-right">−{{ Money::format($item->payment_fee_cents) }}</td>
                            <td class="px-4 py-2 text-right">−{{ Money::format($item->platform_fee_cents) }}</td>
                            <td class="px-4 py-2 text-right font-semibold">{{ Money::format($item->seller_net_cents) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-500">{{ __('No sales in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    </div>
</x-layouts.app>
