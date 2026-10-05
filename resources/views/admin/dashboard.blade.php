@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Admin')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Admin overview') }}</flux:heading>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('GMV (30 days)'), Money::format($gmv30), null],
                [__('Platform revenue (30 days)'), Money::format($revenue30), null],
                [__('Orders (30 days)'), number_format($orders30), route('admin.orders.index')],
                [__('New users (30 days)'), number_format($newUsers30).' / '.number_format($usersCount), route('admin.users.index')],
            ] as [$label, $value, $href])
                <a @if ($href) href="{{ $href }}" @endif class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $value }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('Approved sellers'), $sellersCount, route('admin.sellers.index', ['status' => 'approved'])],
                [__('Seller applications'), $pendingSellers, route('admin.sellers.index', ['status' => 'pending'])],
                [__('Products to review'), $pendingProducts, route('admin.products.index')],
                [__('Open reports'), $openReports, route('admin.reports.index')],
            ] as [$label, $value, $href])
                <a href="{{ $href }}" @class([
                    'rounded-xl border p-5 hover:bg-zinc-50 dark:hover:bg-zinc-800',
                    'border-amber-300 dark:border-amber-800' => $value > 0 && $label !== __('Approved sellers'),
                    'border-zinc-200 dark:border-zinc-700' => $value === 0 || $label === __('Approved sellers'),
                ])>
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-semibold">{{ number_format($value) }}</p>
                </a>
            @endforeach
        </div>

        <x-bar-chart :title="__('Platform revenue per month after affiliate commissions (USD)')" :points="$chart" />
    </div>
</x-layouts.app>
