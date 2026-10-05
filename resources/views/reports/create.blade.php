<x-layouts.public :title="__('Report an item')" :noindex="true">
    <div class="mx-auto max-w-xl px-4 py-10 sm:px-6">
        <a href="{{ route('products.show', $product) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ $product->title }}</a>
        <h1 class="mt-2 text-2xl font-bold">{{ __('Report this item') }}</h1>

        <form method="POST" action="{{ route('reports.store', $product) }}" class="mt-6 flex flex-col gap-4 text-sm" x-data="{ reason: @js(old('reason', 'copyright')) }">
            @csrf
            <fieldset class="space-y-2">
                <legend class="mb-1 font-medium">{{ __('Reason') }}</legend>
                @foreach (\App\Models\Report::REASONS as $value => $label)
                    <label class="flex items-center gap-2"><input type="radio" name="reason" value="{{ $value }}" x-model="reason"> {{ __($label) }}</label>
                @endforeach
            </fieldset>

            <label x-show="reason === 'copyright'">
                <span class="mb-1 block font-medium">{{ __('Link to the original work') }}</span>
                <input type="url" name="original_url" value="{{ old('original_url') }}" placeholder="https://" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
            </label>

            <label>
                <span class="mb-1 block font-medium">{{ __('Details') }}</span>
                <textarea name="details" rows="5" required minlength="20" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">{{ old('details') }}</textarea>
            </label>

            @if ($errors->any())
                <ul class="text-red-600">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif

            <button type="submit" class="self-start rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white dark:bg-white dark:text-zinc-900">{{ __('Send report') }}</button>
        </form>
    </div>
</x-layouts.public>
