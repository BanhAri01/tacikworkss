<div class="flex aspect-square size-8 items-center justify-center rounded-md bg-[#111111] text-sm font-bold text-white dark:bg-white dark:text-[#111111]">
    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(config('app.name'), 0, 1)) }}
</div>
<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate font-semibold uppercase leading-none tracking-[0.2em]">{{ config('app.name') }}</span>
</div>
