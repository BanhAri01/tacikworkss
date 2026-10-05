<x-layouts.app :title="__('Orders')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Orders') }}</flux:heading>

        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Order id, PayPal id, purchase code or email') }}" aria-label="{{ __('Search orders') }}"
                   class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <select name="status" onchange="this.form.submit()" aria-label="{{ __('Status') }}" class="rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (\App\Enums\OrderStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ ucfirst($s->value) }}</option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                    <tr><th class="px-4 py-2">{{ __('Order') }}</th><th class="px-4 py-2">{{ __('Buyer') }}</th><th class="px-4 py-2">{{ __('Status') }}</th><th class="px-4 py-2 text-right">{{ __('Total') }}</th><th class="px-4 py-2">{{ __('Date') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-2"><a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs underline">{{ \Illuminate\Support\Str::limit($order->uuid, 13, '…') }}</a> <span class="text-zinc-500">· {{ $order->items_count }}</span></td>
                            <td class="px-4 py-2">{{ $order->buyer->email }}</td>
                            <td class="px-4 py-2">{{ ucfirst($order->status->value) }} <span class="text-xs text-zinc-500">({{ $order->gateway }})</span></td>
                            <td class="px-4 py-2 text-right">{{ \App\Support\Money::format($order->total_cents) }}</td>
                            <td class="whitespace-nowrap px-4 py-2">{{ $order->created_at->toFormattedDateString() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-500">{{ __('No orders.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    </div>
</x-layouts.app>
