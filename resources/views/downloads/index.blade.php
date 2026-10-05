<x-layouts.app :title="__('My downloads')">
    <div class="flex max-w-4xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('My downloads') }}</flux:heading>

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($items as $item)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                        @if ($item->product?->cover)
                            <img src="{{ $item->product->cover->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="truncate font-medium">{{ $item->product_title }}</p>
                        <p class="text-zinc-500">
                            {{ $item->license_name }} · {{ $item->order->paid_at?->toFormattedDateString() }}
                            @if ($item->product?->latestFile) · v{{ $item->product->latestFile->version }} @endif
                        </p>
                        <p class="mt-1 font-mono text-xs text-zinc-500">{{ __('Purchase code') }}: {{ $item->purchase_code }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($item->product?->latestFile)
                            <flux:button size="sm" variant="primary" icon="arrow-down-tray" :href="route('downloads.file', $item)">{{ __('Download') }}</flux:button>
                        @endif
                        <flux:button size="sm" :href="route('downloads.license', $item)">{{ __('License PDF') }}</flux:button>
                    </div>
                </div>
            @empty
                <p class="p-8 text-center text-zinc-500">{{ __('Nothing here yet.') }} <a href="{{ route('search') }}" class="underline">{{ __('Browse designs') }}</a></p>
            @endforelse
        </div>

        {{ $items->links() }}
    </div>
</x-layouts.app>
