@props(['status'])

<span {{ $attributes->class(['inline-flex rounded-full px-2 py-0.5 text-xs font-medium', $status->badgeClasses()]) }}>{{ __($status->label()) }}</span>
