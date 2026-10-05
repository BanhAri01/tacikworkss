@php use App\Support\Money; $profile = $bundle->seller->sellerProfile; @endphp

<x-layouts.public :title="$bundle->title" :description="$bundle->description" :image="$bundle->products->first()?->cover?->url()">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <p class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __('Bundle') }}</p>
        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $bundle->title }}</h1>
        <p class="mt-1 text-sm text-zinc-500">
            {{ __('by') }} <a href="{{ route('shop.show', $profile->username) }}" class="underline">{{ $profile->display_name }}</a>
            · {{ trans_choice(':count item|:count items', $bundle->products->count()) }} · {{ __(':license license', ['license' => $bundle->license->name]) }}
        </p>

        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_320px]">
            <div>
                @if ($bundle->description)
                    <p class="mb-6 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $bundle->description }}</p>
                @endif
                <div class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-3">
                    @foreach ($bundle->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>

            <aside class="h-fit rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                <p class="text-3xl font-bold">{{ Money::format($bundle->price_cents) }}</p>
                @if ($separate > $bundle->price_cents)
                    <p class="mt-1 text-sm text-zinc-500">
                        <span class="line-through">{{ Money::format($separate) }}</span>
                        · {{ __('save :amount', ['amount' => Money::format($separate - $bundle->price_cents)]) }}
                    </p>
                @endif
                <p class="mt-3 text-xs text-zinc-500">{{ $bundle->license->description }}</p>

                @error('bundle') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($bundle->isAvailable() && $profile->canReceivePayments())
                    <form method="POST" action="{{ route('bundles.cart', $bundle) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">{{ __('Add bundle to cart') }}</button>
                    </form>
                @else
                    <p class="mt-4 rounded-lg bg-zinc-100 px-3 py-2 text-sm dark:bg-zinc-800">{{ __('Not available for purchase right now.') }}</p>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.public>
