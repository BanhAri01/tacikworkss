<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Coupons'])] class extends Component {
    public string $code = '';
    public string $type = 'percent';
    public string $value = '';
    public string $max_uses = '';
    public string $expires_at = '';

    public function create(): void
    {
        $this->code = strtoupper(trim($this->code));

        $this->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => $this->type === 'percent'
                ? ['required', 'integer', 'between:1,100']
                : ['required', 'regex:'.Money::DECIMAL_REGEX],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        Coupon::create([
            'seller_id' => auth()->id(),
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->type === 'percent' ? (int) $this->value : Money::toCents($this->value),
            'max_uses' => $this->max_uses !== '' ? (int) $this->max_uses : null,
            'expires_at' => $this->expires_at !== '' ? $this->expires_at.' 23:59:59' : null,
        ]);

        $this->reset();
    }

    public function toggle(int $id): void
    {
        $coupon = Coupon::where('seller_id', auth()->id())->findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    public function with(): array
    {
        return ['coupons' => Coupon::where('seller_id', auth()->id())->latest()->get()];
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Coupons') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Discounts apply only to your own items, and come out of your price.') }}</flux:text>
    </div>

    <form wire:submit="create" class="grid gap-3 rounded-xl border border-zinc-200 p-4 sm:grid-cols-2 dark:border-zinc-700">
        <flux:input wire:model="code" :label="__('Code')" placeholder="LAUNCH20" class="uppercase" />
        <flux:select wire:model.live="type" :label="__('Type')">
            <option value="percent">{{ __('Percent off') }}</option>
            <option value="fixed">{{ __('Fixed amount off (USD)') }}</option>
        </flux:select>
        <flux:input wire:model="value" :label="$type === 'percent' ? __('Percent (1–100)') : __('Amount (e.g. 5.00)')" />
        <flux:input wire:model="max_uses" type="number" min="1" :label="__('Max uses (optional)')" />
        <flux:input wire:model="expires_at" type="date" :label="__('Expires (optional)')" />
        <div class="flex items-end"><flux:button type="submit" variant="primary">{{ __('Create coupon') }}</flux:button></div>
    </form>

    <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
        @forelse ($coupons as $coupon)
            <div wire:key="c-{{ $coupon->id }}" class="flex items-center justify-between gap-3 p-4">
                <div>
                    <p class="font-mono font-semibold">{{ $coupon->code }}</p>
                    <p class="text-zinc-500">
                        {{ $coupon->type === CouponType::Percent ? $coupon->value.'%' : Money::format($coupon->value) }} {{ __('off') }}
                        · {{ __('used :n', ['n' => $coupon->used_count]) }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}
                        @if ($coupon->expires_at) · {{ __('until :date', ['date' => $coupon->expires_at->toFormattedDateString()]) }} @endif
                    </p>
                </div>
                <button type="button" wire:click="toggle({{ $coupon->id }})" @class(['rounded-lg px-3 py-1', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $coupon->isUsable(), 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800' => ! $coupon->isUsable()])>
                    {{ $coupon->is_active ? __('Active') : __('Paused') }}
                </button>
            </div>
        @empty
            <p class="p-6 text-center text-zinc-500">{{ __('No coupons yet.') }}</p>
        @endforelse
    </div>
</div>
