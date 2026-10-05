<x-layouts.app :title="__('Edit license')">
    <div class="flex max-w-xl flex-col gap-6">
        <a href="{{ route('admin.licenses.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('Licenses') }}</a>
        <flux:heading size="xl" level="1">{{ $license->name }}</flux:heading>

        <form method="POST" action="{{ route('admin.licenses.update', $license) }}" class="flex flex-col gap-4">
            @csrf @method('PUT')
            <flux:input name="name" :label="__('Name')" :value="old('name', $license->name)" required />
            <flux:textarea name="description" :label="__('Terms summary')" rows="5" required>{{ old('description', $license->description) }}</flux:textarea>
            <flux:input name="sort" type="number" min="0" :label="__('Sort order')" :value="old('sort', $license->sort)" />
            @error('slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <div><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div>
        </form>
    </div>
</x-layouts.app>
