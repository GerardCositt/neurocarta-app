<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeRestaurantWithSubscription(?Subscription $subscription): Restaurant
    {
        $account = Account::create(['name' => 'Public menu account']);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Carta Pública Test',
            'subdomain'  => 'carta-publica-test',
        ]);

        if ($subscription) {
            $subscription->account_id = $account->id;
            $subscription->save();
        }

        return $restaurant;
    }

    public function test_active_trial_shows_public_menu(): void
    {
        $restaurant = $this->makeRestaurantWithSubscription(
            Subscription::make([
                'plan_code'             => 'trial',
                'status'                => 'trialing',
                'current_period_end_at' => now()->addDays(5),
            ])
        );

        $response = $this->get('/?restaurant='.$restaurant->id);

        $response->assertSuccessful();
        $response->assertDontSee('Esta carta digital no está disponible', false);
    }

    public function test_expired_trial_blocks_public_menu(): void
    {
        $restaurant = $this->makeRestaurantWithSubscription(
            Subscription::make([
                'plan_code'             => 'trial',
                'status'                => 'trialing',
                'current_period_end_at' => now()->subDay(),
            ])
        );

        $response = $this->get('/?restaurant='.$restaurant->id);

        $response->assertStatus(402);
        $response->assertSee('Esta carta digital no está disponible', false);
        $response->assertSee('Carta Pública Test', false);
    }

    public function test_no_subscription_blocks_public_menu(): void
    {
        $restaurant = $this->makeRestaurantWithSubscription(null);

        $response = $this->get('/?restaurant='.$restaurant->id);

        $response->assertStatus(402);
        $response->assertSee('Esta carta digital no está disponible', false);
    }

    public function test_demo_unlimited_bypasses_subscription_check(): void
    {
        $restaurant = $this->makeRestaurantWithSubscription(null);
        $restaurant->forceFill(['ai_demo_unlimited' => true])->save();

        $response = $this->get('/?restaurant='.$restaurant->id);

        $response->assertSuccessful();
        $response->assertDontSee('Esta carta digital no está disponible', false);
    }
}
