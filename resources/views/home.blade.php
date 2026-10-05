<x-layouts.public>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => route('home'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <section class="mx-auto max-w-3xl px-4 pb-6 pt-10 text-center sm:px-8 sm:pt-14">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ __('Design assets from independent creators') }}</h1>
        <p class="mx-auto mt-4 max-w-xl text-[15px] text-gray-500">{{ __('Fonts, templates, mockups and graphics with clear licenses. Buy once, use in your projects.') }}</p>
        <form action="{{ route('search') }}" method="GET" role="search" class="mx-auto mt-8 flex max-w-lg items-center border-b border-[#111111]">
            <input type="search" name="q" placeholder="{{ __('What are you looking for?') }}" aria-label="{{ __('Search') }}"
                   class="min-w-0 flex-1 bg-transparent px-1 py-3 text-sm placeholder:text-gray-400 focus:outline-none">
            <button type="submit" class="px-1 text-[13px] font-semibold">{{ __('Search') }} →</button>
        </form>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-12 sm:px-6">
        @if ($freeOfTheWeek)
            <section class="grid items-center gap-6 rounded-2xl border border-zinc-200 p-4 sm:grid-cols-2 sm:p-6 dark:border-zinc-800">
                <a href="{{ route('products.show', $freeOfTheWeek) }}" class="block aspect-[4/3] overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    @if ($freeOfTheWeek->cover)
                        <img src="{{ $freeOfTheWeek->cover->url() }}" alt="{{ $freeOfTheWeek->title }}" class="size-full object-cover">
                    @endif
                </a>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-green-600">{{ __('Free of the week') }}</p>
                    <h2 class="mt-2 text-2xl font-bold">{{ $freeOfTheWeek->title }}</h2>
                    <p class="mt-1 text-zinc-500">{{ __('by :seller', ['seller' => $freeOfTheWeek->seller->sellerProfile->display_name]) }}</p>
                    <a href="{{ route('products.show', $freeOfTheWeek) }}" class="mt-4 inline-block rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">{{ __('Get it free') }}</a>
                </div>
            </section>
        @endif

        @foreach ([
            [__('Featured'), $featured, route('search')],
            [__('New arrivals'), $newest, route('search', ['sort' => 'newest'])],
            [__('Popular'), $popular, route('search', ['sort' => 'best-selling'])],
        ] as [$heading, $items, $more])
            @if ($items->isNotEmpty())
                <section>
                    <div class="mb-4 flex items-end justify-between">
                        <h2 class="text-xl font-bold">{{ $heading }}</h2>
                        <a href="{{ $more }}" class="text-sm text-zinc-500 hover:underline">{{ __('See all') }} →</a>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($items as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        <section class="rounded-xl border border-zinc-200 p-6 sm:flex sm:items-center sm:justify-between dark:border-zinc-800">
            <div>
                <h2 class="text-lg font-semibold">{{ __('Sell your designs') }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Payments go straight to your PayPal. Low fee when you bring the buyer, your own storefront, non-exclusive.') }}</p>
            </div>
            <a href="{{ route('become-seller') }}" class="mt-4 inline-block rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-100 sm:mt-0 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('Become a seller') }}</a>
        </section>
    </div>
</x-layouts.public>
