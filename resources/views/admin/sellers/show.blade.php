<x-layouts.app :title="$profile->display_name">
    <div class="flex max-w-3xl flex-col gap-6">
        <a href="{{ route('admin.sellers.index', ['status' => $profile->status->value]) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('Sellers') }}</a>

        <x-flash-status />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ $profile->display_name }}</flux:heading>
                <p class="mt-1 text-zinc-500">{{ '@'.$profile->username }} · <x-seller-status-badge :status="$profile->status" /></p>
            </div>

            <div class="flex gap-2">
                @can('approve', $profile)
                    <form method="POST" action="{{ route('admin.sellers.approve', $profile) }}">
                        @csrf
                        <flux:button type="submit" variant="primary">{{ __('Approve') }}</flux:button>
                    </form>
                @endcan
                @can('suspend', $profile)
                    <form method="POST" action="{{ route('admin.sellers.suspend', $profile) }}" onsubmit="return confirm(@js(__('Suspend this seller?')))">
                        @csrf
                        <flux:button type="submit" variant="danger">{{ __('Suspend') }}</flux:button>
                    </form>
                @endcan
            </div>
        </div>

        <dl class="grid gap-x-6 gap-y-4 rounded-xl border border-zinc-200 p-6 text-sm sm:grid-cols-2 dark:border-zinc-700">
            <div><dt class="text-zinc-500">{{ __('Account') }}</dt><dd>{{ $profile->user->name }} &lt;{{ $profile->user->email }}&gt;</dd></div>
            <div><dt class="text-zinc-500">{{ __('Country') }}</dt><dd>{{ \App\Support\Countries::all()[$profile->country] ?? $profile->country }}</dd></div>
            <div><dt class="text-zinc-500">{{ __('PayPal email') }}</dt><dd>{{ $profile->paypal_email }}</dd></div>
            <div>
                <dt class="text-zinc-500">{{ __('Portfolio') }}</dt>
                <dd>
                    @if ($profile->portfolio_url)
                        <a href="{{ $profile->portfolio_url }}" target="_blank" rel="noopener noreferrer nofollow" class="break-all underline">{{ $profile->portfolio_url }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div><dt class="text-zinc-500">{{ __('Applied') }}</dt><dd>{{ $profile->created_at->toDayDateTimeString() }}</dd></div>
            <div><dt class="text-zinc-500">{{ __('Approved') }}</dt><dd>{{ $profile->approved_at?->toDayDateTimeString() ?? '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-zinc-500">{{ __('Bio') }}</dt><dd class="whitespace-pre-line">{{ $profile->bio ?: '—' }}</dd></div>
        </dl>
    </div>
</x-layouts.app>
