<?php

use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Reviews'])] class extends Component {
    use WithPagination;

    /** review id => draft reply */
    public array $replies = [];

    public function reply(int $reviewId): void
    {
        $this->validate(["replies.{$reviewId}" => ['required', 'string', 'max:2000']], [], ["replies.{$reviewId}" => 'reply']);

        $review = Review::whereHas('product', fn ($q) => $q->withTrashed()->where('seller_id', auth()->id()))->findOrFail($reviewId);
        $review->update(['seller_reply' => $this->replies[$reviewId], 'replied_at' => now()]);

        unset($this->replies[$reviewId]);
    }

    public function with(): array
    {
        return [
            'reviews' => Review::query()
                ->whereHas('product', fn ($q) => $q->withTrashed()->where('seller_id', auth()->id()))
                ->with(['user:id,name', 'product' => fn ($q) => $q->withTrashed()->select('id', 'title', 'slug')])
                ->latest()
                ->paginate(20),
        ];
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('Reviews') }}</flux:heading>

    <div class="space-y-4">
        @forelse ($reviews as $review)
            <article wire:key="sr-{{ $review->id }}" class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                <p class="text-zinc-500">{{ $review->product->title }} · {{ $review->created_at->toFormattedDateString() }}</p>
                <p class="mt-1"><span class="text-amber-500">{{ str_repeat('★', $review->rating) }}</span> <span class="font-medium">{{ $review->user->name }}</span></p>
                @if ($review->body)
                    <p class="mt-1 whitespace-pre-line">{{ $review->body }}</p>
                @endif

                @if ($review->seller_reply)
                    <p class="mt-3 border-l-2 border-zinc-300 pl-3 text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">{{ $review->seller_reply }}</p>
                @else
                    <form wire:submit="reply({{ $review->id }})" class="mt-3 flex flex-col gap-2">
                        <textarea wire:model="replies.{{ $review->id }}" rows="2" placeholder="{{ __('Reply publicly…') }}" aria-label="{{ __('Reply') }}"
                                  class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"></textarea>
                        @error("replies.{$review->id}") <p class="text-red-600">{{ $message }}</p> @enderror
                        <div><flux:button size="sm" type="submit">{{ __('Reply') }}</flux:button></div>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-zinc-500">{{ __('No reviews yet.') }}</p>
        @endforelse
    </div>

    {{ $reviews->links() }}
</div>
