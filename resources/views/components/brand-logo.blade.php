@props(['size' => 'md'])

{{-- Uses public/assets/images/logo.png when present, otherwise a text wordmark. --}}
@if (file_exists(public_path('assets/images/logo.png')))
    <img src="{{ asset('assets/images/logo.png') }}" alt="{{ config('app.name') }}"
         {{ $attributes->class([$size === 'lg' ? 'h-16' : 'h-12 sm:h-14', 'w-auto object-contain brightness-0 contrast-200']) }}>
@else
    <span {{ $attributes->class([
        'inline-block whitespace-nowrap font-semibold uppercase tracking-[0.32em] text-[#111111]',
        $size === 'lg' ? 'text-xl' : 'text-[15px] sm:text-base',
    ]) }}>{{ config('app.name') }}</span>
@endif
