<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Collections'])] class extends Component {
    public string $name = '';

    public function create(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:80']]);
        auth()->user()->collections()->create(['name' => $this->name]);
        $this->reset('name');
    }

    public function togglePublic(int $id): void
    {
        $collection = auth()->user()->collections()->findOrFail($id);
        $collection->update(['is_public' => ! $collection->is_public]);
    }

    public function delete(int $id): void
    {
        auth()->user()->collections()->findOrFail($id)->delete();
    }

    public function with(): array
    {
        return ['collections' => auth()->user()->collections()->withCount('products')->latest()->get()];
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('Collections') }}</flux:heading>

    <form wire:submit="create" class="flex gap-2">
        <flux:input wire:model="name" :placeholder="__('New collection name')" class="flex-1" />
        <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
    </form>

    <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
        @forelse ($collections as $collection)
            <div wire:key="cl-{{ $collection->id }}" class="flex flex-wrap items-center justify-between gap-3 p-4">
                <a href="{{ route('collections.show', $collection) }}" class="font-medium underline">{{ $collection->name }}</a>
                <span class="text-zinc-500">{{ trans_choice(':count item|:count items', $collection->products_count) }}</span>
                <span class="flex gap-3">
                    <button type="button" wire:click="togglePublic({{ $collection->id }})" class="underline">{{ $collection->is_public ? __('Public') : __('Private') }}</button>
                    <button type="button" wire:click="delete({{ $collection->id }})" wire:confirm="{{ __('Delete this collection?') }}" class="text-red-600">{{ __('Delete') }}</button>
                </span>
            </div>
        @empty
            <p class="p-6 text-center text-zinc-500">{{ __('Group items into collections from any product page.') }}</p>
        @endforelse
    </div>
</div>
