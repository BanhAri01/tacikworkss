<?php

use App\Actions\Cart\CurrentCart;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    #[On('cart-updated')]
    public function refresh(): void {}

    public function with(): array
    {
        return ['count' => app(CurrentCart::class)->count()];
    }
}; ?>

<span>
    @if ($count > 0)
        <span class="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-zinc-900 text-[10px] font-bold text-white dark:bg-white dark:text-zinc-900">{{ $count }}</span>
    @endif
</span>
