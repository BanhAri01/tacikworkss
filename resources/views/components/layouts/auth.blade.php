@php
    // Real product previews for the side panel; never let this break the login page.
    $mosaic = rescue(fn () => \Illuminate\Support\Facades\Cache::remember('auth.mosaic', 600, fn () => \App\Models\ProductPreview::query()
        ->whereNotNull('thumb_path')
        ->whereHas('product', fn ($q) => $q->published())
        ->inRandomOrder()
        ->limit(12)
        ->pluck('thumb_path')
        ->map(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
        ->all()), [], report: false);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['forceLight' => true])
    </head>
    <body class="min-h-screen bg-[#fbfbfb] text-[#111111] antialiased">
        <div class="grid min-h-svh lg:grid-cols-2">
            {{-- Form side --}}
            <div class="flex flex-col px-4 py-6 sm:px-8">
                <div class="flex items-center justify-between text-[13px] text-gray-500">
                    <a href="{{ route('home') }}" class="transition hover:text-[#111111]">&larr; {{ __('Back to store') }}</a>
                    @if (request()->routeIs('login'))
                        <a href="{{ route('register') }}" class="transition hover:text-[#111111]">{{ __('Create account') }}</a>
                    @elseif (request()->routeIs('register'))
                        <a href="{{ route('login') }}" class="transition hover:text-[#111111]">{{ __('Log in') }}</a>
                    @endif
                </div>

                <div class="flex flex-1 items-center justify-center py-10">
                    <div class="w-full max-w-sm">
                        <a href="{{ route('home') }}" class="mb-8 flex justify-center">
                            <x-brand-logo size="lg" />
                        </a>

                        <div class="border border-gray-200 bg-white p-6 shadow-[0_1px_2px_rgba(0,0,0,0.04)] sm:p-8">
                            <div class="flex flex-col gap-6">
                                {{ $slot }}
                            </div>
                        </div>

                        <p class="mt-6 text-center text-xs text-gray-400">
                            {{ __('By continuing you agree to the :app license terms.', ['app' => config('app.name')]) }}
                        </p>
                    </div>
                </div>

                <p class="text-center text-xs text-gray-400">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>

            {{-- Showcase side --}}
            <aside class="relative hidden overflow-hidden bg-[#111111] lg:block" aria-hidden="true">
                @if ($mosaic)
                    <div class="absolute -inset-16 grid rotate-[-8deg] grid-cols-3 gap-3 opacity-60">
                        @foreach (array_merge($mosaic, $mosaic) as $src)
                            <div class="aspect-[4/3] overflow-hidden bg-white/5">
                                <img src="{{ $src }}" alt="" class="size-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-[#111111] via-[#111111]/70 to-[#111111]/20"></div>

                <div class="absolute inset-x-0 bottom-0 p-12 text-white">
                    <p class="text-[13px] font-medium uppercase tracking-[0.3em] text-white/60">{{ config('app.name') }}</p>
                    <h2 class="mt-3 max-w-md text-3xl font-semibold leading-tight">{{ __('Design assets from independent creators.') }}</h2>
                    <p class="mt-3 max-w-md text-sm text-white/70">{{ __('Fonts, templates, mockups and graphics with clear licenses — and every payment goes straight to the designer.') }}</p>
                </div>
            </aside>
        </div>

        @fluxScripts
    </body>
</html>
