<?php

use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public int $sellerId;

    public bool $following = false;

    public function mount(int $sellerId): void
    {
        $this->sellerId = $sellerId;
        $this->following = (bool) auth()->user()?->following()->whereKey($sellerId)->exists();
    }

    public function toggle(): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('login');

            return;
        }

        if ($user->id === $this->sellerId || ! User::whereKey($this->sellerId)->exists()) {
            return;
        }

        $this->following
            ? $user->following()->detach($this->sellerId)
            : $user->following()->syncWithoutDetaching([$this->sellerId]);

        $this->following = ! $this->following;
    }
}; ?>

<div>
    @if (auth()->id() !== $sellerId)
        <button type="button" wire:click="toggle" @class([
            'rounded-lg px-4 py-1.5 text-sm font-medium',
            'border border-zinc-300 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800' => $following,
            'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900' => ! $following,
        ])>
            {{ $following ? __('Following') : __('Follow') }}
        </button>
    @endif
</div>
