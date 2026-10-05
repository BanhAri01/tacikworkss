<x-layouts.app :title="__('Tags')">
    <div class="flex max-w-3xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Tags') }}</flux:heading>

        <x-flash-status />

        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('admin.tags.store') }}" class="flex flex-1 gap-2">
                @csrf
                <input name="name" placeholder="{{ __('New tag') }}" aria-label="{{ __('New tag') }}" required
                       class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:button size="sm" type="submit" variant="primary">{{ __('Add') }}</flux:button>
            </form>
            <form method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search') }}" aria-label="{{ __('Search tags') }}"
                       class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            </form>
        </div>
        @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @forelse ($tags as $tag)
                <li x-data="{ editing: false }" class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                    <span x-show="!editing">{{ $tag->name }} <span class="text-zinc-500">({{ $tag->products_count }})</span></span>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.tags.update', $tag) }}" class="flex flex-1 gap-2">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $tag->name }}" aria-label="{{ __('Tag name') }}" class="min-w-0 flex-1 rounded border border-zinc-300 px-2 py-1 dark:border-zinc-700 dark:bg-zinc-800">
                        <button type="submit" class="underline">{{ __('Save') }}</button>
                    </form>
                    <span class="flex gap-3">
                        <button type="button" @click="editing = !editing" class="underline" x-text="editing ? @js(__('Cancel')) : @js(__('Rename'))"></button>
                        <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm(@js(__('Delete this tag?')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600">{{ __('Delete') }}</button>
                        </form>
                    </span>
                </li>
            @empty
                <li class="p-6 text-center text-zinc-500">{{ __('No tags.') }}</li>
            @endforelse
        </ul>

        {{ $tags->links() }}
    </div>
</x-layouts.app>
