<?php

use App\Models\Collection;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/** Heart (favorite) + "save to collection" for one product. */
new class extends Component {
    #[Locked]
    public int $productId;

    public string $newCollection = '';

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    #[Computed]
    public function favorited(): bool
    {
        return (bool) auth()->user()?->favorites()->whereKey($this->productId)->exists();
    }

    #[Computed]
    public function collections()
    {
        return auth()->user()?->collections()->withExists(['products as has_product' => fn ($q) => $q->whereKey($this->productId)])->orderBy('name')->get() ?? collect();
    }

    public function toggleFavorite(): void
    {
        if (! $this->ensureUser()) {
            return;
        }

        $this->favorited
            ? auth()->user()->favorites()->detach($this->productId)
            : auth()->user()->favorites()->syncWithoutDetaching([Product::findOrFail($this->productId)->id]);

        unset($this->favorited);
    }

    public function toggleCollection(int $collectionId): void
    {
        if (! $this->ensureUser()) {
            return;
        }

        $collection = auth()->user()->collections()->findOrFail($collectionId);
        $collection->products()->toggle([$this->productId]);
        unset($this->collections);
    }

    public function createCollection(): void
    {
        if (! $this->ensureUser()) {
            return;
        }

        $this->validate(['newCollection' => ['required', 'string', 'max:80']]);

        $collection = auth()->user()->collections()->create(['name' => $this->newCollection]);
        $collection->products()->attach($this->productId);

        $this->reset('newCollection');
        unset($this->collections);
    }

    private function ensureUser(): bool
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return false;
        }

        return true;
    }
}; ?>

<div class="flex gap-2" x-data="{ open: false }">
    <button type="button" wire:click="toggleFavorite" class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800" aria-pressed="{{ $this->favorited ? 'true' : 'false' }}">
        <span @class(['text-red-500' => $this->favorited]) aria-hidden="true">{{ $this->favorited ? '♥' : '♡' }}</span>
        {{ $this->favorited ? __('Favorited') : __('Favorite') }}
    </button>

    <div class="relative flex-1" @click.outside="open = false">
        <button type="button" @click="open = !open" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('Save to…') }}</button>
        <div x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-64 rounded-lg border border-zinc-200 bg-white p-2 text-sm shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            @auth
                @foreach ($this->collections as $collection)
                    <label wire:key="col-{{ $collection->id }}" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <input type="checkbox" @checked($collection->has_product) wire:click="toggleCollection({{ $collection->id }})"> {{ $collection->name }}
                    </label>
                @endforeach
                <form wire:submit="createCollection" class="mt-2 flex gap-1 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    <input wire:model="newCollection" placeholder="{{ __('New collection') }}" aria-label="{{ __('New collection') }}" class="min-w-0 flex-1 rounded border border-zinc-300 px-2 py-1 dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="submit" class="rounded bg-zinc-900 px-2 text-white dark:bg-white dark:text-zinc-900">+</button>
                </form>
                @error('newCollection') <p class="mt-1 text-red-600">{{ $message }}</p> @enderror
            @else
                <a href="{{ route('login') }}" class="block px-2 py-1 underline">{{ __('Log in to save items') }}</a>
            @endauth
        </div>
    </div>
</div>
