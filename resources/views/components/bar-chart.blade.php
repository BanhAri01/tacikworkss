@props([
    'title',
    // list of ['label' => string, 'value' => int]
    'points' => [],
    'money' => true,
])

@php
    $fmt = fn (int $v) => $money ? \App\Support\Money::format($v) : number_format($v);
    $max = max(1, ...array_map(fn ($p) => $p['value'], $points ?: [['value' => 0]]));
    // Round the axis top up to a "nice" value so gridlines land on readable numbers.
    $magnitude = 10 ** max(0, strlen((string) $max) - 1);
    $top = (int) (ceil($max / $magnitude) * $magnitude);
    $ticks = [$top, intdiv($top, 2), 0];
    $last = array_key_last($points);
@endphp

<figure {{ $attributes->class('viz-root rounded-xl border border-zinc-200 p-4 dark:border-zinc-700') }}>
    <style>
        .viz-root { --series-1: #2a78d6; --grid: #e4e4e7; }
        .dark .viz-root { --series-1: #3987e5; --grid: #3f3f46; }
    </style>

    <figcaption class="text-sm font-medium">{{ $title }}</figcaption>

    <div class="mt-4 flex gap-2" x-data="{ active: null }">
        {{-- Y axis --}}
        <div class="flex h-40 flex-col justify-between text-right text-[11px] text-zinc-500" aria-hidden="true">
            @foreach ($ticks as $tick)
                <span class="-translate-y-1/2 first:translate-y-0 last:translate-y-0">{{ $fmt($tick) }}</span>
            @endforeach
        </div>

        <div class="min-w-0 flex-1">
            <div class="relative h-40">
                {{-- Recessive gridlines --}}
                @foreach ($ticks as $i => $tick)
                    <div class="absolute inset-x-0 border-t" style="top: {{ $i * 50 }}%; border-color: var(--grid)"></div>
                @endforeach

                <div class="absolute inset-0 flex items-end gap-[2px]" role="img" aria-label="{{ $title }}">
                    @foreach ($points as $i => $point)
                        @php($h = $top > 0 ? round($point['value'] / $top * 100, 2) : 0)
                        {{-- Full-height column is the hit target; the bar inside is the mark. --}}
                        <div class="relative flex h-full flex-1 cursor-default items-end justify-center"
                             @mouseenter="active = {{ $i }}" @mouseleave="active = null" @focus="active = {{ $i }}" @blur="active = null" tabindex="0">
                            <div class="w-full max-w-8 rounded-t-[4px] transition-opacity"
                                 :class="active !== null && active !== {{ $i }} ? 'opacity-50' : ''"
                                 style="height: {{ $point['value'] > 0 ? max($h, 1) : 0 }}%; background: var(--series-1)"></div>

                            @if ($i === $last && $point['value'] > 0)
                                <span class="pointer-events-none absolute -translate-y-1 whitespace-nowrap text-[11px] font-medium text-zinc-700 dark:text-zinc-200" style="bottom: {{ $h }}%">{{ $fmt($point['value']) }}</span>
                            @endif

                            <div x-show="active === {{ $i }}" x-cloak
                                 class="pointer-events-none absolute bottom-full z-10 mb-1 whitespace-nowrap rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <span class="text-zinc-500">{{ $point['label'] }}</span>
                                <span class="ml-1 font-semibold text-zinc-900 dark:text-white">{{ $fmt($point['value']) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-1 flex gap-[2px] text-[11px] text-zinc-500" aria-hidden="true">
                @foreach ($points as $i => $point)
                    <span class="flex-1 truncate text-center">{{ $i % 2 === 0 || count($points) <= 6 ? $point['label'] : '' }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <details class="mt-3 text-sm">
        <summary class="cursor-pointer text-xs text-zinc-500">{{ __('Show as table') }}</summary>
        <table class="mt-2 w-full">
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($points as $point)
                    <tr><td class="py-1">{{ $point['label'] }}</td><td class="py-1 text-right">{{ $fmt($point['value']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
