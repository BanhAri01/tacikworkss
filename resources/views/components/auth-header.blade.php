@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1.5 text-center">
    <h1 class="text-xl font-semibold tracking-tight text-[#111111]">{{ $title }}</h1>
    <p class="text-sm text-gray-500">{{ $description }}</p>
</div>
