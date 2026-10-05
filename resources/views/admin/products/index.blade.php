<x-layouts.app :title="__('Products')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>

        <x-flash-status />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex flex-wrap gap-1 text-sm">
                @foreach (\App\Enums\ProductStatus::cases() as $tab)
                    <a href="{{ route('admin.products.index', ['status' => $tab->value]) }}"
                       @class([
                           'rounded-lg px-3 py-1.5 font-medium',
                           'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $tab === $status,
                           'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $tab !== $status,
                       ])>{{ __($tab->label()) }}</a>
                @endforeach
            </nav>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="status" value="{{ $status->value }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search title') }}" aria-label="{{ __('Search products') }}"
                       class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm sm:w-64 dark:border-zinc-700 dark:bg-zinc-800">
            </form>
        </div>

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($products as $product)
                <a href="{{ route('admin.products.show', $product->id) }}" class="flex items-center gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                        @if ($product->cover)
                            <img src="{{ $product->cover->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $product->title }} @if ($product->featured_at) <span class="text-xs text-amber-600">★ {{ __('Featured') }}</span> @endif</p>
                        <p class="text-sm text-zinc-500">{{ '@'.$product->seller->sellerProfile?->username }} · {{ $product->category->name }}</p>
                    </div>
                    <p class="hidden text-sm text-zinc-500 sm:block">{{ ($product->submitted_at ?? $product->updated_at)->diffForHumans() }}</p>
                </a>
            @empty
                <p class="p-8 text-center text-zinc-500">{{ __('Nothing here.') }}</p>
            @endforelse
        </div>

        {{ $products->links() }}
    </div>
</x-layouts.app>
