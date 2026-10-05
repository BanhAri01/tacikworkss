<?php

use App\Actions\Product\SaveProduct;
use App\Actions\Product\SubmitProduct;
use App\Models\Category;
use App\Models\License;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public const MAX_PREVIEWS = 12;

    public ?Product $product = null;

    public string $title = '';
    public ?int $category_id = null;
    public string $description = '';
    public string $tags = '';
    public array $file_formats = [];
    public array $compatible_software = [];
    public bool $is_free = false;
    /** license_id => "12.00" */
    public array $prices = [];

    public array $newPreviews = [];
    public array $removePreviewIds = [];
    public array $previewOrder = [];

    public $zip = null;
    public string $version = '1.0';
    public string $changelog = '';

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            Gate::authorize('update', $product);
            $this->product = $product;
            $this->fill($product->only(['title', 'category_id', 'description']));
            $this->is_free = (bool) $product->is_free;
            $this->file_formats = $product->file_formats ?? [];
            $this->compatible_software = $product->compatible_software ?? [];
            $this->tags = $product->tags->pluck('name')->implode(', ');
            $this->prices = $product->licensePrices->mapWithKeys(fn ($p) => [$p->license_id => Money::toDecimal($p->price_cents)])->all();
            $this->previewOrder = $product->previews->pluck('id')->all();
            $this->version = $product->version;
        } else {
            Gate::authorize('create', Product::class);
            $this->product = null;
        }
    }

    #[Computed]
    public function categories()
    {
        return Category::options();
    }

    #[Computed]
    public function licenses()
    {
        return License::orderBy('sort')->get();
    }

    #[Computed]
    public function existingPreviews()
    {
        if (! $this->product) {
            return collect();
        }

        $previews = $this->product->previews()->get()->keyBy('id');

        return collect($this->previewOrder)
            ->reject(fn ($id) => in_array($id, $this->removePreviewIds))
            ->map(fn ($id) => $previews->get($id))
            ->filter();
    }

    public function movePreview(int $id, int $direction): void
    {
        $order = array_values($this->previewOrder);
        $i = array_search($id, $order);
        $j = $i + $direction;

        if ($i === false || ! isset($order[$j])) {
            return;
        }

        [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
        $this->previewOrder = $order;
    }

    public function togglePreviewRemoval(int $id): void
    {
        $this->removePreviewIds = in_array($id, $this->removePreviewIds)
            ? array_values(array_diff($this->removePreviewIds, [$id]))
            : [...$this->removePreviewIds, $id];
    }

    public function removeNewPreview(int $index): void
    {
        unset($this->newPreviews[$index]);
        $this->newPreviews = array_values($this->newPreviews);
    }

    protected function rules(): array
    {
        $kept = $this->product ? count($this->previewOrder) - count($this->removePreviewIds) : 0;

        return [
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'description' => ['required', 'string', 'min:30', 'max:20000'],
            'tags' => ['nullable', 'string', 'max:300'],
            'file_formats' => ['array'],
            'file_formats.*' => [Rule::in(config('marketplace.file_formats'))],
            'compatible_software' => ['array'],
            'compatible_software.*' => [Rule::in(config('marketplace.compatible_software'))],
            'is_free' => ['boolean'],
            'prices' => ['array'],
            'prices.*' => ['nullable', 'regex:'.Money::DECIMAL_REGEX, function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && Money::toCents($value) < 100) {
                    $fail(__('Minimum price is $1.00.'));
                }
            }],
            'newPreviews' => ['array', 'max:'.max(0, self::MAX_PREVIEWS - $kept)],
            'newPreviews.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:min_width=600,min_height=400'],
            'zip' => ['nullable', 'file', 'mimes:zip', 'max:204800'],
            'version' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d+){0,2}$/'],
            'changelog' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'newPreviews.max' => __('A product can have at most :max previews.', ['max' => self::MAX_PREVIEWS]),
            'prices.*.regex' => __('Use a price like 12 or 12.50.'),
            'version.regex' => __('Use a version like 1.0 or 1.2.3.'),
        ];
    }

    public function save(SaveProduct $save): void
    {
        $this->product ? Gate::authorize('update', $this->product) : Gate::authorize('create', Product::class);
        $this->validate();

        $licenseIds = $this->licenses->pluck('id')->all();
        $prices = collect($this->prices)
            ->only($licenseIds)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => Money::toCents($v))
            ->all();

        $isNew = ! $this->product;

        $product = $save->handle(auth()->user(), $this->product, [
            'title' => $this->title,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'is_free' => $this->is_free,
            'file_formats' => $this->file_formats,
            'compatible_software' => $this->compatible_software,
            'tags' => array_slice(explode(',', $this->tags), 0, 15),
            'prices' => $prices,
            'version' => $this->version,
            'changelog' => $this->changelog ?: null,
            'remove_preview_ids' => $this->removePreviewIds,
            'preview_order' => array_values(array_diff($this->previewOrder, $this->removePreviewIds)),
        ], $this->newPreviews, $this->zip);

        session()->flash('status', __('Product saved.'));

        if ($isNew) {
            $this->redirectRoute('seller.products.edit', $product->id, navigate: true);

            return;
        }

        $this->product = $product->fresh();
        $this->reset('newPreviews', 'removePreviewIds', 'zip', 'changelog');
        $this->previewOrder = $this->product->previews->pluck('id')->all();
        unset($this->existingPreviews);
    }

    public function submitForReview(SubmitProduct $submit): void
    {
        Gate::authorize('submit', $this->product);

        $submit->handle($this->product);

        session()->flash('status', __('Submitted for review.'));
        $this->redirectRoute('seller.products.index', navigate: true);
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('seller.products.index') }}" class="text-sm text-zinc-500 hover:underline" wire:navigate>&larr; {{ __('My products') }}</a>
            <flux:heading size="xl" level="1" class="mt-1">{{ $product ? __('Edit product') : __('New product') }}</flux:heading>
        </div>
        @if ($product)
            <x-product-status-badge :status="$product->status" />
        @endif
    </div>

    <x-flash-status />

    @if ($product?->status === \App\Enums\ProductStatus::Rejected)
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ __('Rejected') }}: {{ $product->rejection_reason }}
        </div>
    @elseif ($product?->status === \App\Enums\ProductStatus::Approved)
        <div class="rounded-lg border border-zinc-200 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
            {{ __('This product is live. Changing its text, category or images sends it back to review; price and file updates go live immediately.') }}
        </div>
    @endif

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input wire:model="title" :label="__('Title')" required />

        <flux:select wire:model="category_id" :label="__('Category')" required>
            <option value="">{{ __('Choose…') }}</option>
            @foreach ($this->categories as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </flux:select>

        <flux:textarea wire:model="description" :label="__('Description')" rows="8" required />

        <flux:input wire:model="tags" :label="__('Tags')" :description="__('Comma separated, max 15. e.g. logo, minimal, branding')" />

        <fieldset>
            <legend class="mb-2 text-sm font-medium">{{ __('File formats') }}</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (config('marketplace.file_formats') as $format)
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-zinc-200 px-2.5 py-1 text-sm has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-900 has-[:checked]:text-white dark:border-zinc-700 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white dark:has-[:checked]:text-zinc-900">
                        <input type="checkbox" wire:model="file_formats" value="{{ $format }}" class="sr-only"> {{ $format }}
                    </label>
                @endforeach
            </div>
            @error('file_formats.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset>
            <legend class="mb-2 text-sm font-medium">{{ __('Compatible software') }}</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (config('marketplace.compatible_software') as $software)
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-zinc-200 px-2.5 py-1 text-sm has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-900 has-[:checked]:text-white dark:border-zinc-700 dark:has-[:checked]:border-white dark:has-[:checked]:bg-white dark:has-[:checked]:text-zinc-900">
                        <input type="checkbox" wire:model="compatible_software" value="{{ $software }}" class="sr-only"> {{ $software }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <legend class="px-1 text-sm font-medium">{{ __('Pricing (USD)') }}</legend>
            <flux:checkbox wire:model.live="is_free" :label="__('Free product (buyers must log in to download)')" />

            @unless ($is_free)
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ($this->licenses as $license)
                        <div>
                            <flux:input wire:model="prices.{{ $license->id }}" :label="$license->name" placeholder="—" inputmode="decimal" icon="currency-dollar" />
                            <p class="mt-1 text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit($license->description, 70) }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-zinc-500">{{ __('Leave empty to not offer a license. Platform fee: :own when buyers come from your shop link, :mp from the marketplace.', ['own' => Money::percent(\App\Models\Setting::int('fee_own_bps')), 'mp' => Money::percent(\App\Models\Setting::int('fee_marketplace_bps'))]) }}</p>
            @endunless
        </fieldset>

        <fieldset class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <legend class="px-1 text-sm font-medium">{{ __('Preview images') }}</legend>
            <p class="text-xs text-zinc-500">{{ __('JPG/PNG/WebP, min 600×400, max 10MB each, up to :max. The first image is the cover. Large previews get a watermark.', ['max' => 12]) }}</p>

            @if ($this->existingPreviews->isNotEmpty())
                <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($this->existingPreviews as $preview)
                        <li wire:key="preview-{{ $preview->id }}" class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="flex aspect-[4/3] items-center justify-center bg-zinc-100 text-xs text-zinc-500 dark:bg-zinc-800">
                                @if ($preview->isProcessed())
                                    <img src="{{ $preview->thumbUrl() }}" alt="" class="size-full object-cover">
                                @else
                                    {{ __('Processing…') }}
                                @endif
                            </div>
                            <div class="flex items-center justify-between px-2 py-1 text-xs">
                                <span class="flex gap-1">
                                    <button type="button" wire:click="movePreview({{ $preview->id }}, -1)" class="px-1" aria-label="{{ __('Move left') }}">&larr;</button>
                                    <button type="button" wire:click="movePreview({{ $preview->id }}, 1)" class="px-1" aria-label="{{ __('Move right') }}">&rarr;</button>
                                </span>
                                <button type="button" wire:click="togglePreviewRemoval({{ $preview->id }})" class="text-red-600">{{ __('Remove') }}</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($removePreviewIds)
                <p class="mt-2 text-xs text-amber-600">{{ trans_choice(':count image will be removed when you save.|:count images will be removed when you save.', count($removePreviewIds)) }}</p>
            @endif

            <input type="file" wire:model="newPreviews" multiple accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 dark:file:bg-zinc-800">
            <div wire:loading wire:target="newPreviews" class="mt-1 text-xs text-zinc-500">{{ __('Uploading…') }}</div>
            @error('newPreviews') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('newPreviews.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            @if ($newPreviews)
                <ul class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                    @foreach ($newPreviews as $i => $file)
                        <li wire:key="new-{{ $i }}" class="relative">
                            @if (method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                <img src="{{ $file->temporaryUrl() }}" alt="" class="aspect-square w-full rounded object-cover">
                            @endif
                            <button type="button" wire:click="removeNewPreview({{ $i }})" class="absolute right-1 top-1 rounded bg-black/60 px-1 text-xs text-white" aria-label="{{ __('Remove') }}">&times;</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </fieldset>

        <fieldset class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <legend class="px-1 text-sm font-medium">{{ __('Product file') }}</legend>

            @if ($product?->latestFile)
                <p class="text-sm">
                    {{ __('Current') }}: {{ $product->latestFile->original_name }}
                    ({{ \Illuminate\Support\Number::fileSize($product->latestFile->size) }}, v{{ $product->latestFile->version }})
                </p>
            @endif

            <input type="file" wire:model="zip" accept=".zip,application/zip" class="mt-3 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 dark:file:bg-zinc-800">
            <div wire:loading wire:target="zip" class="mt-1 text-xs text-zinc-500">{{ __('Uploading… large files can take a while.') }}</div>
            <p class="mt-1 text-xs text-zinc-500">{{ __('.zip only, max 200MB. Stored privately; only buyers can download it.') }}</p>
            @error('zip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="version" :label="__('Version')" />
                @if ($product?->latestFile)
                    <div class="sm:col-span-2">
                        <flux:input wire:model="changelog" :label="__('Changelog (for a new file)')" />
                    </div>
                @endif
            </div>
        </fieldset>

        @foreach (['previews', 'prices'] as $field)
            @error($field) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @endforeach

        <div class="flex flex-wrap gap-3">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,newPreviews,zip">{{ __('Save') }}</flux:button>
            @if ($product)
                @can('submit', $product)
                    <flux:button type="button" wire:click="submitForReview">{{ __('Submit for review') }}</flux:button>
                @endcan
            @endif
        </div>
    </form>
</div>
