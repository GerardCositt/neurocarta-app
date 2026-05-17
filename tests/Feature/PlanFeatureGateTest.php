<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanFeatureGateTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPlan(string $planCode, string $status = 'active'): array
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Test']);
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
            'current_period_end_at' => now()->addMonth(),
        ]);

        return [$user, $restaurant];
    }

    public function test_basico_user_cannot_access_translations(): void
    {
        [$user, $restaurant] = $this->userWithPlan('basico');

        $response = $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/translations');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_pro_user_can_access_translations(): void
    {
        [$user, $restaurant] = $this->userWithPlan('pro');

        $response = $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/translations');

        $response->assertSuccessful();
    }

    public function test_basico_user_cannot_access_ai_billing(): void
    {
        [$user, $restaurant] = $this->userWithPlan('basico');

        $response = $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/ai-billing');

        $response->assertRedirect(route('dashboard'));
    }
}
