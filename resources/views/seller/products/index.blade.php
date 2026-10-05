<x-layouts.app :title="__('My products')">
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading size="xl" level="1">{{ __('My products') }}</flux:heading>
            <flux:button :href="route('seller.products.create')" variant="primary" icon="plus" wire:navigate>{{ __('New product') }}</flux:button>
        </div>

        <x-flash-status />
        @error('previews') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('zip') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('prices') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($products as $product)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @if ($product->cover)
                            <img src="{{ $product->cover->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $product->title }}</p>
                        <p class="text-sm text-zinc-500">
                            {{ $product->category->name }} ·
                            {{ $product->is_free ? __('Free') : __('from :price', ['price' => \App\Support\Money::format($product->min_price_cents)]) }} ·
                            <x-product-status-badge :status="$product->status" />
                        </p>
                        @if ($product->status === \App\Enums\ProductStatus::Rejected && $product->rejection_reason)
                            <p class="mt-1 text-sm text-red-600">{{ __('Reason') }}: {{ $product->rejection_reason }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($product->status === \App\Enums\ProductStatus::Approved)
                            <flux:button size="sm" :href="route('products.show', $product)">{{ __('View') }}</flux:button>
                        @endif
                        <flux:button size="sm" :href="route('seller.products.edit', $product->id)" wire:navigate>{{ __('Edit') }}</flux:button>
                        @can('submit', $product)
                            <form method="POST" action="{{ route('seller.products.submit', $product->id) }}">
                                @csrf
                                <flux:button size="sm" type="submit" variant="primary">{{ __('Submit for review') }}</flux:button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="p-8 text-center text-zinc-500">{{ __('No products yet.') }}</p>
            @endforelse
        </div>

        {{ $products->links() }}
    </div>
</x-layouts.app>
