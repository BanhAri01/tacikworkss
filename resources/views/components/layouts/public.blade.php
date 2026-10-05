@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'ogType' => 'website', 'noindex' => false])

@php
    $metaTitle = $title ? $title.' · '.config('app.name') : config('app.name');
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags($description ?? __('Fonts, templates, mockups and graphics from independent designers.')), 160);
    $canonical ??= url()->current();
    $user = auth()->user();

    // Which category tab is active (sub-categories light up their parent).
    $currentCategory = request()->route('category');
    $currentSlug = is_object($currentCategory) ? $currentCategory->slug : $currentCategory;

    $navLink = 'transition hover:text-[#111111]';
    $sellUrl = $user?->isApprovedSeller() ? route('seller.dashboard') : route('become-seller');
    $sellLabel = $user?->isApprovedSeller() ? __('Studio') : __('Sell Works');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title, 'forceLight' => true])
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $canonical }}">
        @if ($noindex) <meta name="robots" content="noindex"> @endif
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $canonical }}">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif
        {{ $head ?? '' }}
        @livewireStyles
    </head>
    <body class="flex min-h-screen flex-col bg-[#fbfbfb] text-[#111111] antialiased">
        <header x-data="{ menu: false, search: false, account: false }" class="bg-[#fbfbfb]">
            <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 py-5 sm:px-8">
                {{-- Left --}}
                <div class="flex items-center">
                    <button type="button" @click="menu = !menu" :aria-expanded="menu" class="-ml-2 p-2 text-gray-500 hover:text-[#111111] md:hidden">
                        <span class="sr-only">{{ __('Menu') }}</span>
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h16.5M3.75 12h16.5m-16.5 4.5h16.5" /></svg>
                    </button>
                    <nav class="hidden items-center gap-6 text-[13px] font-medium text-gray-500 md:flex">
                        <a href="{{ route('search') }}" @class([$navLink, 'text-[#111111]' => request()->routeIs('search') && ! request('free')])>{{ __('Browse') }}</a>
                        <a href="{{ route('search', ['free' => 1]) }}" @class([$navLink, 'text-[#111111]' => request()->routeIs('search') && request('free')])>{{ __('Free') }}</a>
                        <a href="{{ route('verify.form') }}" class="{{ $navLink }}">{{ __('Verify License') }}</a>
                    </nav>
                </div>

                {{-- Center logo --}}
                <a href="{{ route('home') }}" class="justify-self-center">
                    <x-brand-logo />
                </a>

                {{-- Right --}}
                <nav class="flex items-center justify-end gap-4 text-[13px] font-medium text-gray-500 sm:gap-5">
                    <a href="{{ $sellUrl }}" class="hidden md:inline {{ $navLink }}">{{ $sellLabel }}</a>

                    <button type="button" @click="search = !search; $nextTick(() => $refs.q?.focus())" class="{{ $navLink }}" aria-label="{{ __('Search') }}">
                        <svg class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z" /></svg>
                    </button>

                    <a href="{{ route('cart') }}" class="relative {{ $navLink }}" aria-label="{{ __('Cart') }}">
                        <svg class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        <livewire:cart-count />
                    </a>

                    @guest
                        <a href="{{ route('login') }}" class="{{ $navLink }}" aria-label="{{ __('Log in') }}">
                            <svg class="size-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z" /></svg>
                        </a>
                    @else
                        <div class="relative" @click.outside="account = false">
                            <button type="button" @click="account = !account" :aria-expanded="account" class="flex size-7 items-center justify-center rounded-full bg-[#111111] text-[11px] font-semibold text-white">
                                {{ $user->initials() }}
                            </button>
                            <div x-show="account" x-cloak x-transition.origin.top.right
                                 class="absolute right-0 z-50 mt-3 w-56 border border-gray-200 bg-white py-1 text-[13px] text-gray-600 shadow-lg">
                                <div class="border-b border-gray-100 px-4 py-2.5">
                                    <p class="truncate font-medium text-[#111111]">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-gray-400">{{ $user->email }}</p>
                                </div>
                                <a href="{{ route('downloads.index') }}" class="block px-4 py-2 hover:bg-gray-50 hover:text-[#111111]">{{ __('My downloads') }}</a>
                                <a href="{{ route('favorites') }}" class="block px-4 py-2 hover:bg-gray-50 hover:text-[#111111]">{{ __('Favorites') }}</a>
                                @if ($user->isApprovedSeller())
                                    <a href="{{ route('seller.dashboard') }}" class="block px-4 py-2 hover:bg-gray-50 hover:text-[#111111]">{{ __('Studio') }}</a>
                                @endif
                                @if ($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-gray-50 hover:text-[#111111]">{{ __('Admin panel') }}</a>
                                @endif
                                <a href="{{ route('settings.profile') }}" class="block px-4 py-2 hover:bg-gray-50 hover:text-[#111111]">{{ __('Settings') }}</a>
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left hover:bg-gray-50 hover:text-[#111111]">{{ __('Log out') }}</button>
                                </form>
                            </div>
                        </div>
                    @endguest
                </nav>
            </div>

            {{-- Search panel --}}
            <div x-show="search" x-cloak x-transition.opacity class="px-4 pb-4 sm:px-8">
                <form action="{{ route('search') }}" method="GET" role="search" class="mx-auto flex max-w-xl items-center border-b border-[#111111]">
                    <input x-ref="q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search fonts, templates, mockups…') }}" aria-label="{{ __('Search') }}"
                           class="w-full bg-transparent px-1 py-2 text-sm placeholder:text-gray-400 focus:outline-none">
                    <button type="submit" class="px-1 text-[13px] font-medium">{{ __('Search') }}</button>
                </form>
            </div>

            {{-- Mobile menu --}}
            <nav x-show="menu" x-cloak x-transition class="border-y border-gray-200 px-4 py-2 text-[13px] text-gray-600 md:hidden">
                <a href="{{ route('search') }}" class="block py-2">{{ __('Browse') }}</a>
                <a href="{{ route('search', ['free' => 1]) }}" class="block py-2">{{ __('Free') }}</a>
                <a href="{{ $sellUrl }}" class="block py-2">{{ $sellLabel }}</a>
                <a href="{{ route('verify.form') }}" class="block py-2">{{ __('Verify License') }}</a>
            </nav>

            {{-- Category bar --}}
            <div class="overflow-x-auto px-4 py-4 sm:px-8">
                <div class="mx-auto flex w-max items-center gap-6 text-[13px]">
                    <a href="{{ route('search') }}" @class([
                        'whitespace-nowrap pb-0.5 transition',
                        'border-b border-[#111111] font-semibold text-[#111111]' => request()->routeIs('search', 'home') && ! request('free'),
                        'text-gray-400 hover:text-[#111111]' => ! (request()->routeIs('search', 'home') && ! request('free')),
                    ])>{{ __('All') }}</a>
                    @foreach ($navCategories as $category)
                        @php($active = $currentSlug && ($currentSlug === $category->slug || str_starts_with($currentSlug, $category->slug.'-')))
                        <a href="{{ route('categories.show', $category) }}" @class([
                            'whitespace-nowrap pb-0.5 transition',
                            'border-b border-[#111111] font-semibold text-[#111111]' => $active,
                            'text-gray-400 hover:text-[#111111]' => ! $active,
                        ])>{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-gray-200">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 text-[13px] sm:grid-cols-4 sm:px-8">
                <div class="sm:col-span-2">
                    <x-brand-logo />
                    <p class="mt-3 max-w-xs text-gray-500">{{ __('Fonts, templates, mockups and graphics from independent designers. Paid straight to the creator.') }}</p>
                </div>
                <div>
                    <h2 class="font-semibold">{{ __('Store') }}</h2>
                    <ul class="mt-3 space-y-2 text-gray-500">
                        <li><a href="{{ route('search') }}" class="hover:text-[#111111]">{{ __('Browse all') }}</a></li>
                        <li><a href="{{ route('search', ['free' => 1]) }}" class="hover:text-[#111111]">{{ __('Free items') }}</a></li>
                        <li><a href="{{ route('verify.form') }}" class="hover:text-[#111111]">{{ __('Verify a license') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="font-semibold">{{ __('Creators') }}</h2>
                    <ul class="mt-3 space-y-2 text-gray-500">
                        <li><a href="{{ route('become-seller') }}" class="hover:text-[#111111]">{{ __('Sell your works') }}</a></li>
                        <li><a href="{{ route('affiliate') }}" class="hover:text-[#111111]">{{ __('Affiliate program') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-200 py-5 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
