<?php

use App\Models\Bundle;
use App\Models\License;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Bundles'])] class extends Component {
    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public ?int $license_id = null;
    public string $price = '';
    public array $product_ids = [];
    public bool $is_active = true;

    #[Computed]
    public function products()
    {
        return auth()->user()->products()->published()->where('is_free', false)->orderBy('title')->get(['id', 'title', 'min_price_cents', 'seller_id', 'status']);
    }

    #[Computed]
    public function licenses()
    {
        return License::orderBy('sort')->get();
    }

    public function edit(int $id): void
    {
        $bundle = Bundle::where('seller_id', auth()->id())->with('products:id')->findOrFail($id);

        $this->editingId = $bundle->id;
        $this->title = $bundle->title;
        $this->description = (string) $bundle->description;
        $this->license_id = $bundle->license_id;
        $this->price = Money::toDecimal($bundle->price_cents);
        $this->product_ids = $bundle->products->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->is_active = $bundle->is_active;
    }

    public function cancel(): void
    {
        $this->reset();
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'license_id' => ['required', Rule::exists('licenses', 'id')],
            'price' => ['required', 'regex:'.Money::DECIMAL_REGEX],
            'product_ids' => ['array', 'min:2', 'max:30'],
            'product_ids.*' => [Rule::in($this->products->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'is_active' => ['boolean'],
        ], ['product_ids.min' => __('Pick at least 2 of your published items.')]);

        $cents = Money::toCents($this->price);
        if ($cents < 100) {
            $this->addError('price', __('Minimum price is $1.00.'));

            return;
        }

        $bundle = $this->editingId
            ? Bundle::where('seller_id', auth()->id())->findOrFail($this->editingId)
            : new Bundle(['seller_id' => auth()->id()]);

        $titleChanged = ! $bundle->exists || $bundle->title !== $this->title;

        $bundle->fill([
            'title' => $this->title,
            'slug' => $titleChanged ? Bundle::uniqueSlug($this->title, $bundle->id) : $bundle->slug,
            'description' => $this->description ?: null,
            'license_id' => $this->license_id,
            'price_cents' => $cents,
            'is_active' => $this->is_active,
        ])->save();

        $bundle->products()->sync(array_map('intval', $this->product_ids));

        $this->reset();
        session()->flash('status', __('Bundle saved.'));
    }

    public function delete(int $id): void
    {
        Bundle::where('seller_id', auth()->id())->findOrFail($id)->delete();
    }

    public function with(): array
    {
        return ['bundles' => Bundle::where('seller_id', auth()->id())->withCount('products')->latest()->get()];
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Bundles') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Sell several of your items together at one price. Buyers get each item with the bundle\'s license.') }}</flux:text>
    </div>

    <x-flash-status />

    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading size="lg">{{ $editingId ? __('Edit bundle') : __('New bundle') }}</flux:heading>
        <flux:input wire:model="title" :label="__('Title')" />
        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="license_id" :label="__('License')">
                <option value="">{{ __('Choose…') }}</option>
                @foreach ($this->licenses as $license)
                    <option value="{{ $license->id }}">{{ $license->name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="price" :label="__('Bundle price (USD)')" inputmode="decimal" icon="currency-dollar" />
        </div>

        <fieldset>
            <legend class="mb-2 text-sm font-medium">{{ __('Items') }}</legend>
            <div class="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-2 text-sm dark:border-zinc-700">
                @forelse ($this->products as $product)
                    <label class="flex items-center justify-between gap-2 rounded px-2 py-1 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <span><input type="checkbox" wire:model="product_ids" value="{{ $product->id }}" class="mr-2">{{ $product->title }}</span>
                        <span class="text-zinc-500">{{ __('from :p', ['p' => Money::format($product->min_price_cents)]) }}</span>
                    </label>
                @empty
                    <p class="p-2 text-zinc-500">{{ __('You need at least 2 published paid items.') }}</p>
                @endforelse
            </div>
            @error('product_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <flux:checkbox wire:model="is_active" :label="__('Active (visible and buyable)')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save bundle') }}</flux:button>
            @if ($editingId)
                <flux:button type="button" wire:click="cancel">{{ __('Cancel') }}</flux:button>
            @endif
        </div>
    </form>

    <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
        @forelse ($bundles as $bundle)
            <div wire:key="b-{{ $bundle->id }}" class="flex flex-wrap items-center justify-between gap-3 p-4">
                <div>
                    <a href="{{ route('bundles.show', $bundle) }}" class="font-medium underline">{{ $bundle->title }}</a>
                    <p class="text-zinc-500">{{ Money::format($bundle->price_cents) }} · {{ trans_choice(':count item|:count items', $bundle->products_count) }} · {{ $bundle->is_active ? __('Active') : __('Paused') }}</p>
                </div>
                <span class="flex gap-3">
                    <button type="button" wire:click="edit({{ $bundle->id }})" class="underline">{{ __('Edit') }}</button>
                    <button type="button" wire:click="delete({{ $bundle->id }})" wire:confirm="{{ __('Delete this bundle?') }}" class="text-red-600">{{ __('Delete') }}</button>
                </span>
            </div>
        @empty
            <p class="p-6 text-center text-zinc-500">{{ __('No bundles yet.') }}</p>
        @endforelse
    </div>
</div>
