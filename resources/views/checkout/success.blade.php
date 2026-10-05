<x-layouts.public :title="__('Thank you')" :noindex="true">
    <div class="mx-auto max-w-2xl px-4 py-12 text-center sm:px-6">
        <p class="text-4xl">✓</p>
        <h1 class="mt-2 text-2xl font-bold">{{ __('Payment complete') }}</h1>
        <p class="mt-2 text-zinc-500">{{ __('A receipt was sent to :email.', ['email' => auth()->user()->email]) }}</p>

        <ul class="mt-8 divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-left text-sm dark:divide-zinc-800 dark:border-zinc-800">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-3 p-4">
                    <span>{{ $item->product_title }} <span class="text-zinc-500">· {{ $item->license_name }}</span></span>
                    <span>{{ \App\Support\Money::format($item->price_cents) }}</span>
                </li>
            @endforeach
            @if ($order->buyer_fee_cents > 0)
                <li class="flex justify-between gap-3 p-4 text-zinc-500"><span>{{ __('Processing fee') }}</span><span>{{ \App\Support\Money::format($order->buyer_fee_cents) }}</span></li>
            @endif
            <li class="flex justify-between gap-3 p-4 font-semibold"><span>{{ __('Total') }}</span><span>{{ \App\Support\Money::format($order->total_cents) }}</span></li>
        </ul>

        <a href="{{ route('downloads.index') }}" class="mt-8 inline-block rounded-lg bg-zinc-900 px-5 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">{{ __('Go to my downloads') }}</a>
    </div>
</x-layouts.public>
