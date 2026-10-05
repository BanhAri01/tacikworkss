<x-layouts.public :title="$collection->name" :noindex="! $collection->is_public">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <h1 class="text-2xl font-bold">{{ $collection->name }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('A collection by :name', ['name' => $collection->user->name]) }} @unless ($collection->is_public) · {{ __('Private') }} @endunless</p>

        <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($products as $product)
                <x-product-card :product="$product" />
            @empty
                <p class="col-span-full text-zinc-500">{{ __('Empty collection.') }}</p>
            @endforelse
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    </div>
</x-layouts.public>
