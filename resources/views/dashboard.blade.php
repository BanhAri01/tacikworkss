<x-layouts.app :title="__('My account')">
    <div class="flex max-w-3xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Hi, :name', ['name' => auth()->user()->name]) }}</flux:heading>

        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('My downloads') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Files, license certificates and purchase codes for everything you bought.') }}</flux:text>
            <flux:button class="mt-4" :href="route('downloads.index')" wire:navigate>{{ __('Open my downloads') }}</flux:button>
        </div>

        @php($profile = auth()->user()->sellerProfile)
        @if ($profile && ! $profile->isApproved())
            <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                {{ __('Seller application') }}: <x-seller-status-badge :status="$profile->status" />
            </div>
        @endif
    </div>
</x-layouts.app>
