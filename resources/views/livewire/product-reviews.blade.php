<?php

use App\Actions\Community\SubmitReview;
use App\Models\Product;
use App\Models\Review;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Locked]
    public int $productId;

    public int $rating = 5;
    public string $body = '';

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    #[Computed]
    public function product(): Product
    {
        return Product::withTrashed()->findOrFail($this->productId);
    }

    #[Computed]
    public function canReview(): bool
    {
        return auth()->check() && SubmitReview::reviewableItem(auth()->user(), $this->product) !== null;
    }

    public function submit(): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:3000'],
        ]);

        app(SubmitReview::class)->handle(auth()->user(), $this->product, $this->rating, $this->body ?: null);

        $this->reset('body');
        $this->rating = 5;
        unset($this->product, $this->canReview);
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'reviews' => Review::where('product_id', $this->productId)->with('user:id,name')->latest()->paginate(10),
        ];
    }
}; ?>

<section id="reviews">
    <div class="flex items-baseline gap-3">
        <h2 class="text-lg font-semibold">{{ __('Reviews') }}</h2>
        @if ($this->product->reviews_count > 0)
            <p class="text-sm text-zinc-500">
                <span class="text-amber-500" aria-hidden="true">★</span>
                {{ number_format($this->product->rating_avg, 1) }} · {{ trans_choice(':count review|:count reviews', $this->product->reviews_count) }}
            </p>
        @endif
    </div>

    @if ($this->canReview)
        <form wire:submit="submit" class="mt-4 space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
            <fieldset>
                <legend class="text-sm font-medium">{{ __('Your rating') }}</legend>
                <div class="mt-1 flex gap-1 text-2xl">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="rating" value="{{ $i }}" class="sr-only">
                            <span @class(['text-amber-500' => $rating >= $i, 'text-zinc-300 dark:text-zinc-600' => $rating < $i]) aria-label="{{ $i }}">★</span>
                        </label>
                    @endfor
                </div>
            </fieldset>
            <textarea wire:model="body" rows="3" placeholder="{{ __('What did you make with it? (optional)') }}" aria-label="{{ __('Review') }}"
                      class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"></textarea>
            @error('rating') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('body') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">{{ __('Post review') }}</button>
        </form>
    @endif

    <div class="mt-4 space-y-4">
        @forelse ($reviews as $review)
            <article wire:key="r-{{ $review->id }}" class="border-b border-zinc-200 pb-4 text-sm dark:border-zinc-800">
                <p>
                    <span class="text-amber-500" aria-label="{{ __(':n out of 5', ['n' => $review->rating]) }}">{{ str_repeat('★', $review->rating) }}<span class="text-zinc-300 dark:text-zinc-600">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                    <span class="ml-2 font-medium">{{ $review->user->name }}</span>
                    <span class="ml-1 text-xs text-zinc-500">· {{ __('Verified buyer') }} · {{ $review->created_at->toFormattedDateString() }}</span>
                </p>
                @if ($review->body)
                    <p class="mt-1 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $review->body }}</p>
                @endif
                @if ($review->seller_reply)
                    <div class="mt-2 border-l-2 border-zinc-300 pl-3 text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                        <p class="text-xs font-medium">{{ __('Seller reply') }}</p>
                        <p class="whitespace-pre-line">{{ $review->seller_reply }}</p>
                    </div>
                @endif
            </article>
        @empty
            <p class="text-sm text-zinc-500">{{ __('No reviews yet.') }}</p>
        @endforelse

        {{ $reviews->links() }}
    </div>
</section>
