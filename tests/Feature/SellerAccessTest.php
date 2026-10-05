<?php

use App\Enums\SellerStatus;
use App\Enums\UserRole;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\SellerApproved;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

test('registration assigns the buyer role', function () {
    Volt::test('auth.register')
        ->set('name', 'Budi')
        ->set('email', 'budi@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors();

    expect(User::firstWhere('email', 'budi@example.com')->hasRole(UserRole::Buyer))->toBeTrue();
});

test('guests are redirected from admin and seller panels', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with(['/admin', '/seller']);

test('unverified users cannot reach become-seller', function () {
    $user = User::factory()->unverified()->create()->assignRole(UserRole::Buyer);

    $this->actingAs($user)->get('/become-seller')->assertRedirect(route('verification.notice'));
});

test('buyers cannot access the admin panel', function () {
    $this->actingAs(buyer())->get('/admin')->assertForbidden();
    $this->actingAs(buyer())->get('/admin/sellers')->assertForbidden();
});

test('admins can access the admin panel', function () {
    $this->actingAs(admin())->get('/admin')->assertOk();
    $this->actingAs(admin())->get('/admin/sellers')->assertOk();

    $profile = SellerProfile::factory()->create();
    $this->actingAs(admin())->get(route('admin.sellers.show', $profile))->assertOk()->assertSee($profile->username);
});

test('non-sellers and pending applicants are sent to the application page', function () {
    $this->actingAs(buyer())->get('/seller')->assertRedirect(route('become-seller'));

    $pending = SellerProfile::factory()->create();
    $this->actingAs($pending->user)->get('/seller')->assertRedirect(route('become-seller'));
});

test('a buyer can apply to become a seller', function () {
    $user = buyer();

    $this->actingAs($user)->get('/become-seller')->assertOk();

    Volt::actingAs($user)->test('seller.apply')
        ->set('username', 'Studio_Bali')
        ->set('display_name', 'Studio Bali')
        ->set('country', 'ID')
        ->set('paypal_email', 'pay@example.com')
        ->call('submit')
        ->assertHasNoErrors();

    $profile = $user->fresh()->sellerProfile;
    expect($profile->status)->toBe(SellerStatus::Pending)
        ->and($profile->username)->toBe('studio_bali')
        ->and($user->fresh()->hasRole(UserRole::Seller))->toBeFalse();
});

test('application rejects reserved and taken usernames', function () {
    SellerProfile::factory()->create(['username' => 'taken']);

    foreach (['admin', 'taken', 'no spaces'] as $username) {
        Volt::actingAs(buyer())->test('seller.apply')
            ->set('username', $username)
            ->set('display_name', 'X')
            ->set('paypal_email', 'pay@example.com')
            ->call('submit')
            ->assertHasErrors('username');
    }
});

test('admin approval grants the seller role, panel access and sends an email', function () {
    Notification::fake();
    $profile = SellerProfile::factory()->create();

    $this->actingAs(admin())
        ->post(route('admin.sellers.approve', $profile))
        ->assertRedirect();

    $profile->refresh();
    expect($profile->status)->toBe(SellerStatus::Approved)
        ->and($profile->approved_at)->not->toBeNull()
        ->and($profile->user->hasRole(UserRole::Seller))->toBeTrue();

    Notification::assertSentTo($profile->user, SellerApproved::class);

    $this->actingAs($profile->user)->get('/seller')->assertOk();
});

test('buyers cannot approve or suspend sellers', function () {
    $profile = SellerProfile::factory()->create();

    $this->actingAs(buyer())->post(route('admin.sellers.approve', $profile))->assertForbidden();
    $this->actingAs(buyer())->post(route('admin.sellers.suspend', $profile))->assertForbidden();

    expect($profile->fresh()->status)->toBe(SellerStatus::Pending);
});

test('suspended sellers lose seller panel access', function () {
    $profile = SellerProfile::factory()->approved()->create();
    $profile->user->assignRole(UserRole::Seller);

    $this->actingAs(admin())->post(route('admin.sellers.suspend', $profile))->assertRedirect();

    expect($profile->fresh()->status)->toBe(SellerStatus::Suspended);
    $this->actingAs($profile->user->fresh())->get('/seller')->assertForbidden();
});

test('approved or suspended sellers cannot resubmit the application', function () {
    $profile = SellerProfile::factory()->suspended()->create();

    expect(fn () => app(\App\Actions\Seller\ApplyAsSeller::class)->handle($profile->user, [
        'username' => 'new', 'display_name' => 'New', 'country' => 'ID', 'paypal_email' => 'a@b.co',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($profile->fresh()->status)->toBe(SellerStatus::Suspended);
});
