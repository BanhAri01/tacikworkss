@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\SellerStatus::Approved => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        \App\Enums\SellerStatus::Suspended => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        default => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    };
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', $classes]) }}>{{ $status->label() }}</span>
