<x-layouts.app :title="__('Settings')">
    <div class="flex max-w-2xl flex-col gap-6">
        <flux:heading size="xl" level="1">{{ __('Settings') }}</flux:heading>
        <x-flash-status />

        <form method="POST" action="{{ route('admin.settings.update') }}" class="flex flex-col gap-6">
            @csrf @method('PUT')

            <fieldset class="grid gap-4 rounded-xl border border-zinc-200 p-4 sm:grid-cols-2 dark:border-zinc-700">
                <legend class="px-1 text-sm font-medium">{{ __('Platform fee (% of item price)') }}</legend>
                <flux:input name="fee_own" :label="__('Seller-brought traffic')" :value="old('fee_own', $values['fee_own'])" />
                <flux:input name="fee_marketplace" :label="__('Marketplace traffic')" :value="old('fee_marketplace', $values['fee_marketplace'])" />
            </fieldset>

            <fieldset class="grid gap-4 rounded-xl border border-zinc-200 p-4 sm:grid-cols-2 dark:border-zinc-700">
                <legend class="px-1 text-sm font-medium">{{ __('Buyer processing fee (covers PayPal)') }}</legend>
                <flux:input name="processing_fee" :label="__('Percent')" :value="old('processing_fee', $values['processing_fee'])" />
                <flux:input name="processing_fee_fixed" :label="__('Fixed per seller (USD)')" :value="old('processing_fee_fixed', $values['processing_fee_fixed'])" icon="currency-dollar" />
            </fieldset>

            <fieldset class="grid gap-4 rounded-xl border border-zinc-200 p-4 sm:grid-cols-2 dark:border-zinc-700">
                <legend class="px-1 text-sm font-medium">{{ __('Affiliates') }}</legend>
                <flux:input name="affiliate" :label="__('Commission (% of price, from our fee)')" :value="old('affiliate', $values['affiliate'])" />
                <flux:input name="affiliate_payout_min" :label="__('Payout minimum (USD)')" :value="old('affiliate_payout_min', $values['affiliate_payout_min'])" icon="currency-dollar" />
            </fieldset>

            <flux:select name="free_of_the_week_product_id" :label="__('Free of the week')">
                <option value="">{{ __('— None —') }}</option>
                @foreach ($freeProducts as $product)
                    <option value="{{ $product->id }}" @selected((int) old('free_of_the_week_product_id', $freeOfTheWeek) === $product->id)>{{ $product->title }}</option>
                @endforeach
            </flux:select>

            @if ($errors->any())
                <ul class="text-sm text-red-600">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif

            <div><flux:button type="submit" variant="primary">{{ __('Save settings') }}</flux:button></div>
        </form>
    </div>
</x-layouts.app>
