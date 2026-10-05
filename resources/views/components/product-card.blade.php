@props(['product'])

<article {{ $attributes->class('group') }}>
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="aspect-[4/3] overflow-hidden rounded-xl bg-[#eaeaea]">
            @if ($product->cover)
                <img src="{{ $product->cover->thumbUrl() }}" alt="{{ $product->title }}" loading="lazy" decoding="async"
                     class="size-full object-cover transition duration-300 group-hover:scale-[1.03]">
            @endif
        </div>
        <h3 class="mt-2.5 line-clamp-1 text-[13px] font-medium group-hover:underline">{{ $product->title }}</h3>
    </a>
    <div class="mt-0.5 flex items-center justify-between gap-2 text-[12px]">
        @if ($product->relationLoaded('seller') && $product->seller->sellerProfile)
            <a href="{{ route('shop.show', $product->seller->sellerProfile->username) }}" class="truncate text-zinc-500 hover:underline">{{ $product->seller->sellerProfile->display_name }}</a>
        @else
            <span></span>
        @endif
        <span class="shrink-0 font-semibold">{{ $product->is_free ? __('Free') : \App\Support\Money::format($product->min_price_cents) }}</span>
    </div>
</article>
