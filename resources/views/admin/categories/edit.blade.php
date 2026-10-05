<x-layouts.app :title="__('Edit category')">
    <div class="flex max-w-xl flex-col gap-6">
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('Categories') }}</a>
        <flux:heading size="xl" level="1">{{ $category->name }}</flux:heading>

        <form method="POST" action="{{ route('admin.categories.update', $category->id) }}" class="flex flex-col gap-4">
            @csrf @method('PUT')
            <flux:input name="name" :label="__('Name')" :value="old('name', $category->name)" required />
            <flux:input name="slug" :label="__('Slug')" :value="old('slug', $category->slug)" />
            <flux:select name="parent_id" :label="__('Parent')">
                <option value="">{{ __('— Top level —') }}</option>
                @foreach ($options as $id => $label)
                    <option value="{{ $id }}" @selected(old('parent_id', $category->parent_id) == $id)>{{ $label }}</option>
                @endforeach
            </flux:select>
            <flux:input name="sort" type="number" min="0" :label="__('Sort order')" :value="old('sort', $category->sort)" />
            <flux:textarea name="description" :label="__('Description (shown on category page)')" rows="3">{{ old('description', $category->description) }}</flux:textarea>
            <div><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div>
        </form>
    </div>
</x-layouts.app>
