<?php

use App\Actions\Cart\AddToCart;
use App\Actions\Checkout\ClaimFreeProduct;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public int $productId;

    public ?int $licenseId = null;
    public bool $added = false;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;
        $this->licenseId = $product->licensePrices->sortBy('price_cents')->first()?->license_id;
    }

    #[Computed]
    public function product(): Product
    {
        return Product::with(['licensePrices.license', 'seller.sellerProfile'])->findOrFail($this->productId);
    }

    #[Computed]
    public function owned(): ?OrderItem
    {
        return auth()->check()
            ? OrderItem::where('product_id', $this->productId)
                ->whereHas('order', fn ($q) => $q->where('buyer_id', auth()->id())->where('status', OrderStatus::Paid))
                ->latest('id')->first()
            : null;
    }

    public function add(bool $checkout = false): void
    {
        $this->validate(['licenseId' => ['required', 'integer']], ['licenseId.required' => __('Choose a license.')]);

        app(AddToCart::class)->handle($this->product, $this->licenseId);
        $this->added = true;
        $this->dispatch('cart-updated');

        if ($checkout) {
            $this->redirectRoute('cart', navigate: false);
        }
    }

    public function claim(): void
    {
        $claim = app(ClaimFreeProduct::class);

        if (! auth()->check()) {
            session()->put('url.intended', route('products.show', $this->product));
            $this->redirectRoute('login');

            return;
        }

        if (! auth()->user()->hasVerifiedEmail()) {
            $this->redirectRoute('verification.notice');

            return;
        }

        $claim->handle(auth()->user(), $this->product);
        $this->redirectRoute('downloads.index');
    }
}; ?>

<div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
    @php($product = $this->product)

    @if ($this->owned)
        <p class="mb-3 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-800 dark:bg-green-950 dark:text-green-200">
            {{ __('You own this (:license).', ['license' => $this->owned->license_name]) }}
            <a href="{{ route('downloads.index') }}" class="font-medium underline">{{ __('Download') }}</a>
        </p>
    @endif

    @if ($product->is_free)
        <p class="text-2xl font-bold">{{ __('Free') }}</p>
        <p class="mt-1 text-sm text-zinc-500">{{ __('Log in to download. Includes the :license license.', ['license' => \App\Models\License::orderBy('sort')->value('name')]) }}</p>
        <button type="button" wire:click="claim" class="mt-4 w-full rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
            {{ __('Download for free') }}
        </button>
    @elseif ($product->licensePrices->isEmpty())
        <p class="text-sm text-zinc-500">{{ __('Not for sale yet.') }}</p>
    @else
        <fieldset class="space-y-2">
            <legend class="sr-only">{{ __('License') }}</legend>
            @foreach ($product->licensePrices->sortBy('license.sort') as $price)
                <label wire:key="lic-{{ $price->license_id }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-3 has-[:checked]:border-zinc-900 dark:border-zinc-700 dark:has-[:checked]:border-white">
                    <input type="radio" wire:model.live="licenseId" value="{{ $price->license_id }}" class="mt-1">
                    <span class="flex-1">
                        <span class="flex justify-between gap-2 font-medium">
                            <span>{{ $price->license->name }}</span>
                            <span>{{ Money::format($price->price_cents) }}</span>
                        </span>
                        <span class="mt-0.5 block text-xs text-zinc-500">{{ $price->license->description }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>

        @error('license') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('licenseId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

        @if ($product->seller->sellerProfile->canReceivePayments())
            <div class="mt-4 grid gap-2">
                <button type="button" wire:click="add(true)" class="rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">{{ __('Buy now') }}</button>
                <button type="button" wire:click="add" class="rounded-lg border border-zinc-300 px-4 py-3 text-sm font-semibold hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    <span wire:loading.remove wire:target="add">{{ $added ? __('Added ✓ — view cart') : __('Add to cart') }}</span>
                    <span wire:loading wire:target="add">{{ __('Adding…') }}</span>
                </button>
                @if ($added)
                    <a href="{{ route('cart') }}" class="text-center text-sm underline">{{ __('Go to cart') }}</a>
                @endif
            </div>
            <p class="mt-3 text-xs text-zinc-500">{{ __('Paid via PayPal directly to the designer. A small processing fee is added at checkout.') }}</p>
        @else
            <p class="mt-4 rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ __('This seller isn\'t accepting payments yet.') }}</p>
        @endif
    @endif
</div>
