<?php

use App\Actions\Seller\ApplyAsSeller;
use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Support\Countries;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    /** Usernames that would clash with routes or impersonate the platform. */
    public const RESERVED = ['admin', 'seller', 'shop', 'support', 'help', 'api', 'designmarket', 'settings', 'login', 'register'];

    public string $username = '';
    public string $display_name = '';
    public string $country = 'ID';
    public string $paypal_email = '';
    public string $portfolio_url = '';
    public string $bio = '';

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isApprovedSeller()) {
            $this->redirectRoute('seller.dashboard', navigate: true);

            return;
        }

        if ($profile = $user->sellerProfile) {
            $this->fill($profile->only(['username', 'display_name', 'country', 'paypal_email']));
            $this->portfolio_url = (string) $profile->portfolio_url;
            $this->bio = (string) $profile->bio;
        } else {
            $this->display_name = $user->name;
            $this->paypal_email = $user->email;
        }
    }

    #[Computed]
    public function profile(): ?SellerProfile
    {
        return auth()->user()->sellerProfile;
    }

    #[Computed]
    public function countries(): array
    {
        return Countries::all();
    }

    public function submit(ApplyAsSeller $apply): void
    {
        $validated = $this->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_-]+$/i',
                Rule::notIn(self::RESERVED),
                Rule::unique('seller_profiles', 'username')->ignore($this->profile?->id),
            ],
            'display_name' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'size:2', Rule::in(array_keys($this->countries))],
            'paypal_email' => ['required', 'email', 'max:255'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ], [
            'username.regex' => __('Only letters, numbers, dashes and underscores.'),
        ]);

        $apply->handle(auth()->user(), [
            ...$validated,
            'portfolio_url' => $validated['portfolio_url'] ?: null,
            'bio' => $validated['bio'] ?: null,
        ]);

        unset($this->profile);

        session()->flash('status', __('Application submitted. We will email you once it is reviewed.'));
    }
}; ?>

<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Become a seller') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Sell fonts, templates, mockups and graphics. Non-exclusive: you can keep selling elsewhere.') }}</flux:text>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($this->profile?->status === SellerStatus::Suspended)
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ __('Your seller account is suspended. Please contact support.') }}
        </div>
    @else
        @if ($this->profile?->status === SellerStatus::Pending)
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                {{ __('Your application is under review. You can still update it below.') }}
            </div>
        @endif

        <form wire:submit="submit" class="flex flex-col gap-5">
            <flux:input wire:model="username" :label="__('Username')" :description="__('Your shop URL: :url', ['url' => url('/shop').'/username'])" required autocomplete="off" />
            <flux:input wire:model="display_name" :label="__('Display name')" required />

            <flux:select wire:model="country" :label="__('Country')" required>
                @foreach ($this->countries as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="paypal_email" type="email" :label="__('PayPal email (for payouts)')" required />
            <flux:input wire:model="portfolio_url" type="url" :label="__('Portfolio URL (Behance, Dribbble, website)')" placeholder="https://" />
            <flux:textarea wire:model="bio" :label="__('Short bio')" rows="4" />

            <div>
                <flux:button type="submit" variant="primary">
                    {{ $this->profile ? __('Update application') : __('Submit application') }}
                </flux:button>
            </div>
        </form>
    @endif
</div>
