<x-layouts.public :title="$profile->display_name" :description="$profile->bio ?: __(':name on :app', ['name' => $profile->display_name, 'app' => config('app.name')])" og-type="profile" :canonical="route('shop.show', $profile->username)">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $profile->display_name,
            'url' => route('shop.show', $profile->username),
            'description' => $profile->bio,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <div class="h-32 bg-gradient-to-r from-zinc-200 to-zinc-100 bg-cover bg-center sm:h-48 dark:from-zinc-800 dark:to-zinc-900"
         @if ($profile->bannerUrl()) style="background-image: url('{{ $profile->bannerUrl() }}')" @endif></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="-mt-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-end gap-4">
                <div class="flex size-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-zinc-300 text-2xl font-bold sm:size-24 dark:border-zinc-900 dark:bg-zinc-700">
                    @if ($profile->avatarUrl())
                        <img src="{{ $profile->avatarUrl() }}" alt="" class="size-full object-cover">
                    @else
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profile->display_name, 0, 1)) }}
                    @endif
                </div>
                <div class="pb-1">
                    <h1 class="text-2xl font-bold">{{ $profile->display_name }}</h1>
                    <p class="text-sm text-zinc-500">
                        {{ '@'.$profile->username }} · {{ \App\Support\Countries::all()[$profile->country] ?? $profile->country }}
                        · {{ trans_choice(':count follower|:count followers', $followers) }}
                    </p>
                </div>
            </div>
            <livewire:follow-button :seller-id="$profile->user_id" />
        </div>

        @if ($profile->bio)
            <p class="mt-4 max-w-2xl whitespace-pre-line text-zinc-600 dark:text-zinc-400">{{ $profile->bio }}</p>
        @endif

        @if ($bundles->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-lg font-semibold">{{ __('Bundles') }}</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bundles as $bundle)
                        <a href="{{ route('bundles.show', $bundle) }}" class="flex gap-3 rounded-xl border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                            <div class="flex -space-x-4">
                                @foreach ($bundle->products->take(3) as $p)
                                    <div class="size-12 overflow-hidden rounded-lg border-2 border-white bg-zinc-100 dark:border-zinc-900 dark:bg-zinc-800">
                                        @if ($p->cover) <img src="{{ $p->cover->thumbUrl() }}" alt="" class="size-full object-cover"> @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="min-w-0 text-sm">
                                <p class="truncate font-medium">{{ $bundle->title }}</p>
                                <p class="text-zinc-500">{{ trans_choice(':count item|:count items', $bundle->products->count()) }} · {{ \App\Support\Money::format($bundle->price_cents) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-8 flex items-center justify-between gap-3 text-sm">
            <p class="text-zinc-500">{{ trans_choice(':count item|:count items', $products->total()) }}</p>
            <form method="GET">
                <label class="flex items-center gap-2">
                    <span class="sr-only">{{ __('Sort') }}</span>
                    <select name="sort" onchange="this.form.submit()" class="rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @foreach (\App\Actions\Catalog\BrowseProducts::SORTS as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-6 pb-12 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($products as $product)
                <x-product-card :product="$product" />
            @empty
                <p class="col-span-full py-12 text-center text-zinc-500">{{ __('No items yet.') }}</p>
            @endforelse
        </div>

        <div class="pb-12">{{ $products->links() }}</div>
    </div>
</x-layouts.public>
