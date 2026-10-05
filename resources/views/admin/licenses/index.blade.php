<x-layouts.app :title="__('Licenses')">
    <div class="flex max-w-3xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Licenses') }}</flux:heading>

        <x-flash-status />
        @error('license') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($licenses as $license)
                <li class="flex items-start justify-between gap-4 p-4 text-sm">
                    <div>
                        <p class="font-medium">{{ $license->name }}</p>
                        <p class="mt-1 text-zinc-500">{{ $license->description }}</p>
                    </div>
                    <span class="flex shrink-0 gap-3">
                        <a href="{{ route('admin.licenses.edit', $license) }}" class="underline">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('admin.licenses.destroy', $license) }}" onsubmit="return confirm(@js(__('Delete this license?')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600">{{ __('Delete') }}</button>
                        </form>
                    </span>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('admin.licenses.store') }}" class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            @csrf
            <flux:heading size="lg">{{ __('Add license') }}</flux:heading>
            <flux:input name="name" :label="__('Name')" :value="old('name')" required />
            <flux:textarea name="description" :label="__('Terms summary')" rows="3" required>{{ old('description') }}</flux:textarea>
            @error('slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <div><flux:button type="submit" variant="primary">{{ __('Add') }}</flux:button></div>
        </form>
    </div>
</x-layouts.app>
