<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_trial_is_redirected_from_dashboard(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Test account']);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Test Restaurant',
            'subdomain'  => 'test-restaurant-expiry',
        ]);

        // Expired trial: end date in the past
        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/dashboard');

        $response->assertRedirect(route('subscription.expired'));
    }

    public function test_active_trial_can_access_dashboard(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Test account active']);
        $account->users()->attach($user);

        Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Active Restaurant',
            'subdomain'  => 'test-restaurant-active',
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertSuccessful();
    }

    public function test_no_subscription_is_redirected_from_dashboard(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'No sub account']);
        $account->users()->attach($user);

        Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'No Sub Restaurant',
            'subdomain'  => 'test-restaurant-nosub',
        ]);

        // No subscription at all

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertRedirect(route('subscription.expired'));
    }
}
