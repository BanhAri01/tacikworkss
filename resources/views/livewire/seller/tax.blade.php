<?php

use App\Support\Countries;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Tax information'])] class extends Component {
    public string $entity_type = 'individual';
    public string $legal_name = '';
    public string $country = 'ID';
    public string $tax_id = '';
    public string $address = '';
    public string $city = '';
    public string $postal_code = '';
    public bool $is_us_person = false;
    public string $treaty_country = '';
    public string $treaty_article = '';
    public string $treaty_rate = '';
    public bool $certify = false;
    public string $signature = '';
    public ?string $signed_at = null;

    public function mount(): void
    {
        $profile = auth()->user()->sellerProfile;
        $this->fill(array_intersect_key($profile->tax_info ?? [], get_object_vars($this)));
        $this->country = $profile->tax_info['country'] ?? $profile->country;
        $this->treaty_country = $profile->tax_info['treaty_country'] ?? $this->country;
        $this->certify = false;
    }

    public function save(): void
    {
        $countries = array_keys(Countries::all());

        $data = $this->validate([
            'entity_type' => ['required', Rule::in(['individual', 'business'])],
            'legal_name' => ['required', 'string', 'max:150'],
            'country' => ['required', Rule::in($countries)],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_us_person' => ['boolean'],
            'treaty_country' => ['nullable', Rule::in($countries)],
            'treaty_article' => ['nullable', 'string', 'max:50'],
            'treaty_rate' => ['nullable', 'string', 'max:10'],
            'certify' => ['accepted'],
            'signature' => ['required', 'string', 'max:150'],
        ], [
            'certify.accepted' => __('Please confirm the information is correct.'),
        ]);

        unset($data['certify']);
        $data['signed_at'] = now()->toIso8601String();

        auth()->user()->sellerProfile->update(['tax_info' => $data]);

        $this->signed_at = $data['signed_at'];
        $this->certify = false;
        session()->flash('status', __('Tax information saved.'));
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Tax information') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Used for your sales records and, for non-US sellers, foreign-status certification (similar to W-8BEN). Only admins can see it.') }}</flux:text>
    </div>

    <x-flash-status />
    @if ($signed_at)
        <p class="text-sm text-zinc-500">{{ __('Last signed: :date', ['date' => \Illuminate\Support\Carbon::parse($signed_at)->toDayDateTimeString()]) }}</p>
    @endif

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:select wire:model.live="entity_type" :label="__('Seller type')">
            <option value="individual">{{ __('Individual') }}</option>
            <option value="business">{{ __('Business') }}</option>
        </flux:select>
        <flux:input wire:model="legal_name" :label="$entity_type === 'business' ? __('Registered business name') : __('Full legal name')" required />
        <flux:select wire:model="country" :label="__('Country of tax residence')">
            @foreach (\App\Support\Countries::all() as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
            @endforeach
        </flux:select>
        <flux:input wire:model="tax_id" :label="__('Tax ID (e.g. NPWP)')" />
        <flux:input wire:model="address" :label="__('Address')" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="city" :label="__('City')" required />
            <flux:input wire:model="postal_code" :label="__('Postal code')" />
        </div>

        <fieldset class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <legend class="px-1 text-sm font-medium">{{ __('Foreign status') }}</legend>
            <flux:checkbox wire:model.live="is_us_person" :label="__('I am a US citizen or US tax resident')" />
            @unless ($is_us_person)
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <flux:select wire:model="treaty_country" :label="__('Treaty country')">
                        <option value="">—</option>
                        @foreach (\App\Support\Countries::all() as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="treaty_article" :label="__('Treaty article')" placeholder="12" />
                    <flux:input wire:model="treaty_rate" :label="__('Withholding rate')" placeholder="10%" />
                </div>
            @endunless
        </fieldset>

        <flux:checkbox wire:model="certify" :label="__('Under penalties of perjury, I certify that the information above is true, correct and complete.')" />
        @error('certify') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        <flux:input wire:model="signature" :label="__('Signature (type your full name)')" required />

        <div><flux:button type="submit" variant="primary">{{ __('Save & sign') }}</flux:button></div>
    </form>
</div>
