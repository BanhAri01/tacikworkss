<?php

use App\Actions\Seller\StoreShopImage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Shop profile'])] class extends Component {
    use WithFileUploads;

    public string $display_name = '';
    public string $bio = '';
    public string $paypal_email = '';
    public $avatar = null;
    public $banner = null;

    public function mount(): void
    {
        $profile = auth()->user()->sellerProfile;
        $this->display_name = $profile->display_name;
        $this->bio = (string) $profile->bio;
        $this->paypal_email = $profile->paypal_email;
    }

    public function save(StoreShopImage $images): void
    {
        $this->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'paypal_email' => ['required', 'email', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $profile = auth()->user()->sellerProfile;
        $data = ['display_name' => $this->display_name, 'bio' => $this->bio ?: null, 'paypal_email' => $this->paypal_email];

        if ($this->avatar) {
            $data['avatar'] = $images->handle($this->avatar, $profile->avatar, 400, 400);
        }
        if ($this->banner) {
            $data['banner'] = $images->handle($this->banner, $profile->banner, 1600, 400);
        }

        $profile->update($data);
        $this->reset('avatar', 'banner');
        session()->flash('status', __('Profile saved.'));
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <flux:heading size="xl" level="1">{{ __('Shop profile') }}</flux:heading>
    <x-flash-status />

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:input wire:model="display_name" :label="__('Shop name')" required />
        <flux:textarea wire:model="bio" :label="__('About')" rows="4" />
        <flux:input wire:model="paypal_email" type="email" :label="__('PayPal email')" :description="__('Used when connecting PayPal. Payments go to the connected PayPal account.')" required />

        <div>
            <p class="mb-1 text-sm font-medium">{{ __('Avatar (square, min 400px)') }}</p>
            <input type="file" wire:model="avatar" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 dark:file:bg-zinc-800">
            @error('avatar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <p class="mb-1 text-sm font-medium">{{ __('Banner (wide, 1600×400)') }}</p>
            <input type="file" wire:model="banner" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 dark:file:bg-zinc-800">
            @error('banner') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div><flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('Save') }}</flux:button></div>
    </form>
</div>
