<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Favorites'])] class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'products' => auth()->user()->favorites()
                ->published()
                ->with(['cover', 'seller.sellerProfile:id,user_id,username,display_name'])
                ->latest('favorites.created_at')
                ->paginate(24),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('Favorites') }}</flux:heading>

    <div class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-3 xl:grid-cols-4">
        @forelse ($products as $product)
            <x-product-card :product="$product" wire:key="f-{{ $product->id }}" />
        @empty
            <p class="col-span-full text-zinc-500">{{ __('Tap ♡ on any item to save it here.') }}</p>
        @endforelse
    </div>

    {{ $products->links() }}
</div>
