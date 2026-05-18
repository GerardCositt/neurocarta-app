<?php

namespace Tests\Feature;

use App\Mail\TrialEndingReminder;
use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTrialFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(string $planCode, string $status, \DateTimeInterface $endsAt): array
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Test Account ' . uniqid()]);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Test Restaurant',
            'subdomain'  => 'test-' . uniqid(),
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => $planCode,
            'status'                => $status,
            'current_period_end_at' => $endsAt,
        ]);

        return [$user, $restaurant];
    }

    public function test_registration_links_user_to_account_and_restaurant(): void
    {
        Mail::fake();

        $this->post('/register', [
            'email'           => 'newuser@example.com',
            'restaurant_name' => 'Restaurante Test',
            'phone'           => '+34600000001',
            'plan'            => 'trial',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user, 'El usuario no fue creado.');
        $this->assertTrue($user->accounts()->exists(), 'El usuario no está vinculado a ninguna cuenta.');

        $account = $user->accounts()->first();
        $this->assertTrue($account->restaurants()->exists(), 'La cuenta no tiene restaurante.');

        $this->assertDatabaseHas('subscriptions', [
            'account_id' => $account->id,
            'plan_code'  => 'trial',
            'status'     => 'trialing',
        ]);
    }

    public function test_active_trial_can_access_product_panel(): void
    {
        [$user, $restaurant] = $this->makeStack('trial', 'trialing', now()->addDays(5));

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/product')
            ->assertSuccessful();
    }

    public function test_active_trial_can_access_dashboard(): void
    {
        [$user, $restaurant] = $this->makeStack('trial', 'trialing', now()->addDays(5));

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/dashboard')
            ->assertRedirect(route('product'));
    }

    public function test_expired_trial_is_blocked_from_product_panel(): void
    {
        [$user, $restaurant] = $this->makeStack('trial', 'trialing', now()->subDay());

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/product')
            ->assertRedirect(route('subscription.expired'));
    }

    public function test_expired_trial_is_blocked_from_dashboard(): void
    {
        [$user, $restaurant] = $this->makeStack('trial', 'trialing', now()->subDay());

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/dashboard')
            ->assertRedirect(route('subscription.expired'));
    }

    public function test_active_basico_subscription_allows_dashboard(): void
    {
        [$user, $restaurant] = $this->makeStack('basico', 'active', now()->addMonth());

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/dashboard')
            ->assertRedirect(route('product'));
    }

    public function test_active_pro_subscription_allows_dashboard(): void
    {
        [$user, $restaurant] = $this->makeStack('pro', 'active', now()->addMonth());

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/dashboard')
            ->assertRedirect(route('product'));
    }

    public function test_trial_day5_warning_email_is_sent(): void
    {
        Mail::fake();

        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Day5 Account']);
        $account->users()->attach($user);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(2),
        ]);

        $this->artisan('trial:send-warnings')->assertExitCode(0);

        Mail::assertSent(TrialEndingReminder::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_trial_day7_warning_email_is_sent(): void
    {
        Mail::fake();

        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Day7 Account']);
        $account->users()->attach($user);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addHours(12),
        ]);

        $this->artisan('trial:send-warnings')->assertExitCode(0);

        Mail::assertSent(TrialEndingReminder::class, fn ($m) => $m->hasTo($user->email));
    }

    public function test_trial_warning_not_sent_twice(): void
    {
        Mail::fake();

        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'No Repeat Account']);
        $account->users()->attach($user);

        Subscription::create([
            'account_id'                 => $account->id,
            'plan_code'                  => 'trial',
            'status'                     => 'trialing',
            'current_period_end_at'      => now()->addDays(2),
            'trial_warning_day5_sent_at' => now(),
        ]);

        $this->artisan('trial:send-warnings')->assertExitCode(0);

        Mail::assertNotSent(TrialEndingReminder::class);
    }
}
