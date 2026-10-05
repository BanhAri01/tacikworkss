@php
    use App\Support\Money;
    $images = $product->previews->filter->isProcessed()->values();
    $prices = $licensePrices->pluck('price_cents');
    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->title,
        'description' => \Illuminate\Support\Str::limit($product->description, 500),
        'image' => $images->map->url()->all(),
        'sku' => (string) $product->id,
        'category' => $product->category->name,
        'brand' => ['@type' => 'Brand', 'name' => $profile->display_name],
        'offers' => [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'USD',
            'lowPrice' => Money::toDecimal($product->is_free ? 0 : (int) $prices->min()),
            'highPrice' => Money::toDecimal($product->is_free ? 0 : (int) $prices->max()),
            'offerCount' => $product->is_free ? 1 : $prices->count(),
            'availability' => 'https://schema.org/InStock',
            'url' => route('products.show', $product),
        ],
        'aggregateRating' => $product->reviews_count > 0 ? [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($product->rating_avg, 1),
            'reviewCount' => $product->reviews_count,
        ] : null,
    ]);
@endphp

<x-layouts.public :title="$product->title" :description="$product->description" :image="$images->first()?->url()" og-type="product" :noindex="! $product->isPublished()">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <x-flash-status class="mb-4" />
        @unless ($product->isPublished())
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                {{ __('Preview — this item is not public (status: :status).', ['status' => __($product->status->label())]) }}
            </div>
        @endunless

        <nav class="mb-4 text-sm text-zinc-500" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('search') }}" class="hover:underline">{{ __('All') }}</a>
            @if ($product->category->parent)
                › <a href="{{ route('categories.show', $product->category->parent) }}" class="hover:underline">{{ $product->category->parent->name }}</a>
            @endif
            › <a href="{{ route('categories.show', $product->category) }}" class="hover:underline">{{ $product->category->name }}</a>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold sm:text-3xl lg:hidden">{{ $product->title }}</h1>

                <div x-data="{ active: 0 }" class="mt-4 lg:mt-0">
                    <div class="aspect-[4/3] overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        @foreach ($images as $i => $image)
                            <img x-show="active === {{ $i }}" @if ($i > 0) x-cloak loading="lazy" @endif src="{{ $image->url() }}" alt="{{ $product->title }} — {{ __('preview :n', ['n' => $i + 1]) }}" class="size-full object-contain">
                        @endforeach
                    </div>
                    @if ($images->count() > 1)
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($images as $i => $image)
                                <button type="button" @click="active = {{ $i }}" :class="active === {{ $i }} ? 'ring-2 ring-zinc-900 dark:ring-white' : 'opacity-70'" class="h-16 w-20 shrink-0 overflow-hidden rounded-lg" aria-label="{{ __('Show preview :n', ['n' => $i + 1]) }}">
                                    <img src="{{ $image->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <section class="mt-8">
                    <h2 class="text-lg font-semibold">{{ __('Description') }}</h2>
                    <div class="mt-2 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $product->description }}</div>
                </section>

                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-zinc-500">{{ __('File formats') }}</dt><dd>{{ implode(', ', $product->file_formats ?? []) ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Works with') }}</dt><dd>{{ implode(', ', $product->compatible_software ?? []) ?: '—' }}</dd></div>
                    <div>
                        <dt class="text-zinc-500">{{ __('Version') }}</dt>
                        <dd>{{ $product->version }}@if ($product->latestFile) · {{ \Illuminate\Support\Number::fileSize($product->latestFile->size) }}@endif</dd>
                    </div>
                </dl>

                @if ($product->tags->isNotEmpty())
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($product->tags as $tag)
                            <a href="{{ route('search', ['q' => $tag->name]) }}" class="rounded-full bg-zinc-100 px-3 py-1 text-xs hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-10">
                    <livewire:product-reviews :product-id="$product->id" />
                </div>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <h1 class="hidden text-2xl font-bold lg:block">{{ $product->title }}</h1>

                <livewire:add-to-cart :product="$product" />
                <livewire:save-buttons :product-id="$product->id" />

                @foreach ($bundles as $bundle)
                    <a href="{{ route('bundles.show', $bundle) }}" class="block rounded-xl border border-dashed border-zinc-300 p-3 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                        {{ __('Also in bundle') }}: <span class="font-medium">{{ $bundle->title }}</span> — {{ \App\Support\Money::format($bundle->price_cents) }}
                    </a>
                @endforeach

                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-zinc-200 font-semibold dark:bg-zinc-700">
                            @if ($profile->avatarUrl())
                                <img src="{{ $profile->avatarUrl() }}" alt="" class="size-full object-cover">
                            @else
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profile->display_name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('shop.show', $profile->username) }}" class="block truncate font-medium hover:underline">{{ $profile->display_name }}</a>
                            <p class="text-sm text-zinc-500">{{ '@'.$profile->username }}</p>
                        </div>
                        <livewire:follow-button :seller-id="$product->seller_id" />
                    </div>
                </div>

                <p class="flex justify-between text-xs text-zinc-500">
                    <span>{{ __('Sales: :count', ['count' => number_format($product->sales_count)]) }}</span>
                    <a href="{{ route('reports.create', $product) }}" class="underline" rel="nofollow">{{ __('Report this item') }}</a>
                </p>
            </aside>
        </div>

        @foreach ([[__('More from :seller', ['seller' => $profile->display_name]), $moreFromSeller], [__('Related items'), $related]] as [$heading, $items])
            @if ($items->isNotEmpty())
                <section class="mt-14">
                    <h2 class="mb-4 text-xl font-bold">{{ $heading }}</h2>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-4">
                        @foreach ($items as $item)
                            <x-product-card :product="$item" />
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</x-layouts.public>
