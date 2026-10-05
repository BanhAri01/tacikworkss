<x-layouts.app :title="__('Categories')">
    <div class="flex max-w-3xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Categories') }}</flux:heading>

        <x-flash-status />
        @error('category') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <form method="POST" action="{{ route('admin.categories.store') }}" class="grid gap-3 rounded-xl border border-zinc-200 p-4 sm:grid-cols-3 sm:items-end dark:border-zinc-700">
            @csrf
            <flux:input name="name" :label="__('Name')" :value="old('name')" required />
            <flux:select name="parent_id" :label="__('Parent')">
                <option value="">{{ __('— Top level —') }}</option>
                @foreach ($options as $id => $label)
                    <option value="{{ $id }}" @selected(old('parent_id') == $id)>{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary">{{ __('Add category') }}</flux:button>
            @error('name') <p class="text-sm text-red-600 sm:col-span-3">{{ $message }}</p> @enderror
            @error('slug') <p class="text-sm text-red-600 sm:col-span-3">{{ $message }}</p> @enderror
        </form>

        <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($options as $id => $label)
                <li class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                    <span>{{ $label }} <span class="text-zinc-500">({{ $categories[$id]->products_count }})</span></span>
                    <span class="flex gap-3">
                        <a href="{{ route('admin.categories.edit', $id) }}" class="underline">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $id) }}" onsubmit="return confirm(@js(__('Delete this category?')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600">{{ __('Delete') }}</button>
                        </form>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.app>
