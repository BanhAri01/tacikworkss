<x-layouts.public :title="__('Verify a license')" :noindex="true">
    <div class="mx-auto max-w-xl px-4 py-12 sm:px-6">
        <h1 class="text-2xl font-bold">{{ __('Verify a license') }}</h1>
        <p class="mt-2 text-sm text-zinc-500">{{ __('Enter the purchase code from a license certificate to confirm it is genuine.') }}</p>

        <form action="{{ route('verify.form') }}" method="GET" class="mt-6 flex gap-2">
            <input name="code" value="{{ $code }}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" aria-label="{{ __('Purchase code') }}" required
                   class="min-w-0 flex-1 rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">{{ __('Check') }}</button>
        </form>

        @if ($code !== null)
            @if (! $result)
                <p class="mt-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">✕ {{ __('No license found for this code.') }}</p>
            @else
                <div @class([
                    'mt-6 rounded-xl border p-4 text-sm',
                    'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950' => $result['valid'],
                    'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950' => ! $result['valid'],
                ])>
                    <p class="font-semibold">{{ $result['valid'] ? '✓ '.__('Valid license') : '⚠ '.__('License revoked (refunded)') }}</p>
                    <dl class="mt-3 grid grid-cols-[120px_1fr] gap-y-1">
                        <dt class="text-zinc-500">{{ __('Item') }}</dt>
                        <dd>@if ($result['item_url'])<a href="{{ $result['item_url'] }}" class="underline">{{ $result['item'] }}</a>@else{{ $result['item'] }}@endif</dd>
                        <dt class="text-zinc-500">{{ __('License') }}</dt><dd>{{ $result['license'] }}</dd>
                        <dt class="text-zinc-500">{{ __('Author') }}</dt><dd>{{ $result['author'] }}</dd>
                        <dt class="text-zinc-500">{{ __('Licensee') }}</dt><dd>{{ $result['licensee'] }}</dd>
                        <dt class="text-zinc-500">{{ __('Purchased') }}</dt><dd>{{ \Illuminate\Support\Carbon::parse($result['purchased_at'])->toFormattedDateString() }}</dd>
                    </dl>
                </div>
            @endif
        @endif

        <p class="mt-8 text-xs text-zinc-500">{{ __('Developers: GET :url returns JSON.', ['url' => url('/api/v1/licenses/{purchase_code}')]) }}</p>
    </div>
</x-layouts.public>
