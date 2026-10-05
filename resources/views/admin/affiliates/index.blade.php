@php use App\Support\Money; @endphp

<x-layouts.app :title="__('Affiliate payouts')">
    <div class="flex max-w-3xl flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">{{ __('Affiliate payouts') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Send the amount from the platform PayPal account, then mark it paid. Minimum: :min.', ['min' => Money::format($minimum)]) }}</flux:text>
        </div>
        <x-flash-status />

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($balances as $row)
                @php($user = $users[$row->affiliate_id] ?? null)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-medium">{{ $user?->name }} <span class="text-zinc-500">&lt;{{ $user?->email }}&gt;</span></p>
                        <p class="text-zinc-500">{{ __('PayPal: :email', ['email' => $user?->affiliate_paypal_email ?? $user?->email]) }} · {{ trans_choice(':count sale|:count sales', $row->sales) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-semibold">{{ Money::format((int) $row->total) }}</span>
                        @if ((int) $row->total >= $minimum && $user)
                            <form method="POST" action="{{ route('admin.affiliates.paid', $user) }}" onsubmit="return confirm(@js(__('Mark as paid?')))">
                                @csrf
                                <flux:button size="sm" type="submit">{{ __('Mark paid') }}</flux:button>
                            </form>
                        @else
                            <span class="text-xs text-zinc-500">{{ __('below minimum') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-zinc-500">{{ __('No unpaid commissions.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
