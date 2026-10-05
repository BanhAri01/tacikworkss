<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('home') }}" class="mr-5 flex items-center space-x-2">
                <x-app-logo class="size-8" href="#"></x-app-logo>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Platform" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('My account') }}</flux:navlist.item>
                    <flux:navlist.item icon="arrow-down-tray" :href="route('downloads.index')" :current="request()->routeIs('downloads.*')" wire:navigate>{{ __('My downloads') }}</flux:navlist.item>
                    <flux:navlist.item icon="heart" :href="route('favorites')" :current="request()->routeIs('favorites')" wire:navigate>{{ __('Favorites') }}</flux:navlist.item>
                    <flux:navlist.item icon="rectangle-stack" :href="route('collections.index')" :current="request()->routeIs('collections.index')" wire:navigate>{{ __('Collections') }}</flux:navlist.item>
                    <flux:navlist.item icon="link" :href="route('affiliate')" :current="request()->routeIs('affiliate')" wire:navigate>{{ __('Affiliate') }}</flux:navlist.item>
                    @unless (auth()->user()->isApprovedSeller())
                        <flux:navlist.item icon="sparkles" :href="route('become-seller')" :current="request()->routeIs('become-seller')" wire:navigate>{{ __('Become a seller') }}</flux:navlist.item>
                    @endunless
                </flux:navlist.group>

                @if (auth()->user()->isApprovedSeller())
                    <flux:navlist.group :heading="__('Seller')" class="grid">
                        <flux:navlist.item icon="building-storefront" :href="route('seller.dashboard')" :current="request()->routeIs('seller.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                        <flux:navlist.item icon="squares-2x2" :href="route('seller.products.index')" :current="request()->routeIs('seller.products.*')" wire:navigate>{{ __('Products') }}</flux:navlist.item>
                        <flux:navlist.item icon="banknotes" :href="route('seller.sales.index')" :current="request()->routeIs('seller.sales.*')" wire:navigate>{{ __('Sales') }}</flux:navlist.item>
                        <flux:navlist.item icon="ticket" :href="route('seller.coupons')" :current="request()->routeIs('seller.coupons')" wire:navigate>{{ __('Coupons') }}</flux:navlist.item>
                        <flux:navlist.item icon="gift" :href="route('seller.bundles')" :current="request()->routeIs('seller.bundles')" wire:navigate>{{ __('Bundles') }}</flux:navlist.item>
                        <flux:navlist.item icon="star" :href="route('seller.reviews')" :current="request()->routeIs('seller.reviews')" wire:navigate>{{ __('Reviews') }}</flux:navlist.item>
                        <flux:navlist.item icon="identification" :href="route('seller.profile')" :current="request()->routeIs('seller.profile')" wire:navigate>{{ __('Shop profile') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-check" :href="route('seller.tax')" :current="request()->routeIs('seller.tax')" wire:navigate>{{ __('Tax info') }}</flux:navlist.item>
                    </flux:navlist.group>
                @endif

                @if (auth()->user()->isAdmin())
                    <flux:navlist.group :heading="__('Admin')" class="grid">
                        <flux:navlist.item icon="chart-bar" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Overview') }}</flux:navlist.item>
                        <flux:navlist.item icon="users" :href="route('admin.sellers.index')" :current="request()->routeIs('admin.sellers.*')" wire:navigate>{{ __('Sellers') }}</flux:navlist.item>
                        <flux:navlist.item icon="inbox-stack" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>{{ __('Products') }}</flux:navlist.item>
                        <flux:navlist.item icon="folder" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>{{ __('Categories') }}</flux:navlist.item>
                        <flux:navlist.item icon="tag" :href="route('admin.tags.index')" :current="request()->routeIs('admin.tags.*')" wire:navigate>{{ __('Tags') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('admin.licenses.index')" :current="request()->routeIs('admin.licenses.*')" wire:navigate>{{ __('Licenses') }}</flux:navlist.item>
                        <flux:navlist.item icon="shopping-bag" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>{{ __('Orders') }}</flux:navlist.item>
                        <flux:navlist.item icon="user-group" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
                        <flux:navlist.item icon="flag" :href="route('admin.reports.index')" :current="request()->routeIs('admin.reports.*')" wire:navigate>{{ __('Reports') }}</flux:navlist.item>
                        <flux:navlist.item icon="link" :href="route('admin.affiliates.index')" :current="request()->routeIs('admin.affiliates.*')" wire:navigate>{{ __('Affiliate payouts') }}</flux:navlist.item>
                        <flux:navlist.item icon="cog-6-tooth" :href="route('admin.settings.edit')" :current="request()->routeIs('admin.settings.*')" wire:navigate>{{ __('Settings') }}</flux:navlist.item>
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="arrow-top-right-on-square" :href="route('home')">
                    {{ __('Back to marketplace') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @livewireScripts
        @fluxScripts
    </body>
</html>
