<?php

use App\Actions\Catalog\BrowseProducts;
use App\Models\Category;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.public')] class extends Component {
    use WithPagination;

    public ?Category $category = null;

    #[Url(except: '')]
    public string $q = '';
    #[Url(except: '')]
    public string $min = '';
    #[Url(except: '')]
    public string $max = '';
    #[Url(except: '')]
    public string $format = '';
    #[Url(except: '')]
    public string $software = '';
    #[Url(except: false)]
    public bool $free = false;
    #[Url(except: 'newest')]
    public string $sort = 'newest';

    public function mount(?Category $category = null): void
    {
        $this->category = $category?->exists ? $category : null;
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('min', 'max', 'format', 'software', 'free', 'sort');
        $this->resetPage();
    }

    private function cents(string $value): ?int
    {
        return preg_match(Money::DECIMAL_REGEX, trim($value)) ? Money::toCents($value) : null;
    }

    public function with(): array
    {
        $browse = app(BrowseProducts::class);
        $title = $this->category?->name
            ?? ($this->q !== '' ? __('Results for “:q”', ['q' => $this->q]) : __('Browse all designs'));

        return [
            'products' => $browse->handle([
                'q' => mb_substr($this->q, 0, 100),
                'category' => $this->category,
                'min' => $this->cents($this->min),
                'max' => $this->cents($this->max),
                'format' => in_array($this->format, config('marketplace.file_formats'), true) ? $this->format : null,
                'software' => in_array($this->software, config('marketplace.compatible_software'), true) ? $this->software : null,
                'free' => $this->free,
                'sort' => $this->sort,
            ]),
            'subcategories' => $this->category?->children ?? Category::whereNull('parent_id')->orderBy('sort')->get(),
            'heading' => $title,
        ];
    }

    public function rendering($view): void
    {
        $view->layoutData([
            'title' => $this->category?->name ?? ($this->q !== '' ? __('Search: :q', ['q' => $this->q]) : __('Browse')),
            'description' => $this->category?->description,
            'canonical' => $this->category ? route('categories.show', $this->category) : route('search'),
            // Filtered/search result pages shouldn't compete with category pages in search engines.
            'noindex' => $this->q !== '' || $this->min !== '' || $this->max !== '' || $this->format !== '' || $this->software !== '' || $this->free,
        ]);
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    @if ($category?->parent)
        <nav class="mb-2 text-sm text-zinc-500" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('categories.show', $category->parent) }}" class="hover:underline">{{ $category->parent->name }}</a> ›
        </nav>
    @endif
    <h1 class="text-2xl font-bold">{{ $heading }}</h1>
    @if ($category?->description)
        <p class="mt-2 max-w-2xl text-zinc-500">{{ $category->description }}</p>
    @endif

    @if ($subcategories->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($subcategories as $sub)
                <a href="{{ route('categories.show', $sub) }}" class="rounded-full border border-zinc-200 px-3 py-1 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ $sub->name }}</a>
            @endforeach
        </div>
    @endif

    <div x-data="{ filters: false }" class="mt-6 lg:grid lg:grid-cols-[220px_1fr] lg:gap-8">
        <button type="button" @click="filters = !filters" class="mb-4 rounded-lg border border-zinc-300 px-3 py-1.5 text-sm lg:hidden dark:border-zinc-700">{{ __('Filters') }}</button>

        <aside :class="filters ? 'block' : 'hidden'" class="mb-6 space-y-5 text-sm lg:mb-0 lg:block">
            @if (! $category)
                <div>
                    <label for="f-q" class="mb-1 block font-medium">{{ __('Keyword') }}</label>
                    <input id="f-q" type="search" wire:model.live.debounce.400ms="q" class="w-full rounded-lg border border-zinc-300 px-3 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                </div>
            @endif
            <fieldset>
                <legend class="mb-1 font-medium">{{ __('Price (USD)') }}</legend>
                <div class="flex items-center gap-2">
                    <input type="text" inputmode="decimal" wire:model.live.debounce.500ms="min" placeholder="{{ __('Min') }}" aria-label="{{ __('Minimum price') }}" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <span>–</span>
                    <input type="text" inputmode="decimal" wire:model.live.debounce.500ms="max" placeholder="{{ __('Max') }}" aria-label="{{ __('Maximum price') }}" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                </div>
                <label class="mt-2 flex items-center gap-2"><input type="checkbox" wire:model.live="free"> {{ __('Free only') }}</label>
            </fieldset>
            <div>
                <label for="f-format" class="mb-1 block font-medium">{{ __('Format') }}</label>
                <select id="f-format" wire:model.live="format" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">{{ __('Any') }}</option>
                    @foreach (config('marketplace.file_formats') as $f)
                        <option value="{{ $f }}">{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-software" class="mb-1 block font-medium">{{ __('Software') }}</label>
                <select id="f-software" wire:model.live="software" class="w-full rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">{{ __('Any') }}</option>
                    @foreach (config('marketplace.compatible_software') as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="clearFilters" class="text-zinc-500 underline">{{ __('Clear filters') }}</button>
        </aside>

        <section>
            <div class="mb-4 flex items-center justify-between gap-3 text-sm">
                <p class="text-zinc-500">{{ trans_choice(':count item|:count items', $products->total()) }}</p>
                <label class="flex items-center gap-2">
                    <span class="sr-only sm:not-sr-only">{{ __('Sort') }}</span>
                    <select wire:model.live="sort" class="rounded-lg border border-zinc-300 px-2 py-1.5 dark:border-zinc-700 dark:bg-zinc-800">
                        @foreach (\App\Actions\Catalog\BrowseProducts::SORTS as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div wire:loading.class="opacity-60" class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <x-product-card :product="$product" wire:key="p-{{ $product->id }}" />
                @empty
                    <p class="col-span-full py-12 text-center text-zinc-500">{{ __('No items match your filters.') }}</p>
                @endforelse
            </div>

            <div class="mt-8">{{ $products->links() }}</div>
        </section>
    </div>
</div>
