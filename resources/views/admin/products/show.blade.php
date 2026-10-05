<x-layouts.app :title="$product->title">
    <div class="flex max-w-4xl flex-col gap-6">
        <a href="{{ route('admin.products.index', ['status' => $product->status->value]) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('Products') }}</a>

        <x-flash-status />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ $product->title }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-500">
                    <a href="{{ route('admin.sellers.show', $product->seller->sellerProfile) }}" class="underline">{{ '@'.$product->seller->sellerProfile->username }}</a>
                    · {{ $product->category->name }} · v{{ $product->version }} · <x-product-status-badge :status="$product->status" />
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($product->latestFile)
                    <flux:button size="sm" :href="route('admin.products.file', $product->id)" icon="arrow-down-tray">{{ __('Download zip') }}</flux:button>
                @endif
                @can('feature', $product)
                    <form method="POST" action="{{ route('admin.products.feature', $product->id) }}">
                        @csrf
                        <flux:button size="sm" type="submit">{{ $product->featured_at ? __('Unfeature') : __('Feature') }}</flux:button>
                    </form>
                    @if ($product->is_free)
                        <form method="POST" action="{{ route('admin.products.free-of-the-week', $product->id) }}">
                            @csrf
                            <flux:button size="sm" type="submit">{{ __('Make free of the week') }}</flux:button>
                        </form>
                    @endif
                    <flux:button size="sm" :href="route('products.show', $product)">{{ __('View live') }}</flux:button>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($product->previews as $preview)
                <a href="{{ $preview->url() }}" target="_blank" class="block aspect-[4/3] overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    @if ($preview->isProcessed())
                        <img src="{{ $preview->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                    @else
                        <span class="flex size-full items-center justify-center text-xs text-zinc-500">{{ __('Processing…') }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <dl class="grid gap-4 rounded-xl border border-zinc-200 p-6 text-sm sm:grid-cols-2 dark:border-zinc-700">
            <div>
                <dt class="text-zinc-500">{{ __('Pricing') }}</dt>
                <dd>
                    @if ($product->is_free)
                        {{ __('Free') }}
                    @else
                        @foreach ($product->licensePrices as $price)
                            <div>{{ $price->license->name }}: {{ \App\Support\Money::format($price->price_cents) }}</div>
                        @endforeach
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-zinc-500">{{ __('File') }}</dt>
                <dd>{{ $product->latestFile ? $product->latestFile->original_name.' ('.\Illuminate\Support\Number::fileSize($product->latestFile->size).')' : '—' }}</dd>
            </div>
            <div><dt class="text-zinc-500">{{ __('Formats') }}</dt><dd>{{ implode(', ', $product->file_formats ?? []) ?: '—' }}</dd></div>
            <div><dt class="text-zinc-500">{{ __('Software') }}</dt><dd>{{ implode(', ', $product->compatible_software ?? []) ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-zinc-500">{{ __('Tags') }}</dt><dd>{{ $product->tags->pluck('name')->implode(', ') ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-zinc-500">{{ __('Description') }}</dt><dd class="whitespace-pre-line">{{ $product->description }}</dd></div>
        </dl>

        @can('moderate', $product)
            <div class="grid gap-4 rounded-xl border border-zinc-200 p-6 sm:grid-cols-2 dark:border-zinc-700">
                <form method="POST" action="{{ route('admin.products.approve', $product->id) }}">
                    @csrf
                    <flux:button type="submit" variant="primary" class="w-full">{{ __('Approve & publish') }}</flux:button>
                </form>
                <form method="POST" action="{{ route('admin.products.reject', $product->id) }}" class="flex flex-col gap-2">
                    @csrf
                    <flux:textarea name="reason" :label="__('Rejection reason (sent to seller)')" rows="3" required>{{ old('reason') }}</flux:textarea>
                    @error('reason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <flux:button type="submit" variant="danger">{{ __('Reject') }}</flux:button>
                </form>
            </div>
        @endcan
    </div>
</x-layouts.app>
