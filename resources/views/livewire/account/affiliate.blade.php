<?php

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Affiliate'])] class extends Component {
    public string $paypal_email = '';

    public function mount(): void
    {
        $this->paypal_email = (string) (auth()->user()->affiliate_paypal_email ?? auth()->user()->email);
    }

    public function savePaypal(): void
    {
        $this->validate(['paypal_email' => ['required', 'email', 'max:255']]);
        auth()->user()->forceFill(['affiliate_paypal_email' => $this->paypal_email])->save();
        session()->flash('status', __('Payout email saved.'));
    }

    public function with(): array
    {
        $earned = OrderItem::query()
            ->where('affiliate_id', auth()->id())
            ->whereNull('refunded_at')
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid));

        return [
            'link' => url('/').'?aff='.auth()->user()->affiliateCode(),
            'sales' => (clone $earned)->count(),
            'unpaid' => (int) (clone $earned)->whereNull('affiliate_paid_at')->sum('affiliate_commission_cents'),
            'paid' => (int) (clone $earned)->whereNotNull('affiliate_paid_at')->sum('affiliate_commission_cents'),
            'rate' => Money::percent(Setting::int('affiliate_bps')),
            'minimum' => Money::format(Setting::int('affiliate_payout_min_cents')),
        ];
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Affiliate program') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Share any page with your link. You earn :rate of the item price on marketplace sales within 30 days of the click. Paid by us via PayPal once you reach :min.', ['rate' => $rate, 'min' => $minimum]) }}</flux:text>
    </div>

    <x-flash-status />

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" x-data="{ copied: false }">
        <p class="text-sm font-medium">{{ __('Your link') }}</p>
        <div class="mt-2 flex gap-2">
            <input readonly value="{{ $link }}" aria-label="{{ __('Affiliate link') }}" class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-1.5 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:button size="sm" x-on:click="navigator.clipboard.writeText(@js($link)); copied = true; setTimeout(() => copied = false, 1500)">
                <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span>
            </flux:button>
        </div>
        <p class="mt-2 text-xs text-zinc-500">{{ __('Tip: add ?aff=… to any product URL too.') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([[__('Referred sales'), number_format($sales)], [__('Unpaid earnings'), Money::format($unpaid)], [__('Paid out'), Money::format($paid)]] as [$label, $value])
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="text-sm text-zinc-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <form wire:submit="savePaypal" class="flex items-end gap-2">
        <flux:input wire:model="paypal_email" type="email" :label="__('PayPal email for affiliate payouts')" class="flex-1" />
        <flux:button type="submit">{{ __('Save') }}</flux:button>
    </form>
</div>
