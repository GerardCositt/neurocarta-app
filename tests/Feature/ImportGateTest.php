<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportGateTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPlan(string $planCode, string $status = 'active'): array
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Import Test ' . uniqid()]);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Test Restaurant',
            'subdomain'  => 'test-import-' . uniqid(),
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => $planCode,
            'status'                => $status,
            'current_period_end_at' => now()->addMonth(),
        ]);

        return [$user, $restaurant];
    }

    // ── CSV import ─────────────────────────────────────────────────────────────

    public function test_basico_cannot_access_csv_import_page(): void
    {
        [$user, $restaurant] = $this->userWithPlan('basico');

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-products')
            ->assertRedirect(route('dashboard'));
    }

    public function test_pro_can_access_csv_import_page(): void
    {
        [$user, $restaurant] = $this->userWithPlan('pro');

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-products')
            ->assertSuccessful();
    }

    public function test_premium_can_access_csv_import_page(): void
    {
        [$user, $restaurant] = $this->userWithPlan('premium');

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-products')
            ->assertSuccessful();
    }

    public function test_basico_cannot_download_csv_template(): void
    {
        [$user, $restaurant] = $this->userWithPlan('basico');

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-products/template')
            ->assertRedirect(route('dashboard'));
    }

    public function test_pro_can_download_csv_template(): void
    {
        [$user, $restaurant] = $this->userWithPlan('pro');

        $response = $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-products/template');

        $response->assertSuccessful();
        $this->assertStringContainsString(
            'attachment; filename="plantilla-productos.csv"',
            $response->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString('nombre', $response->getContent());
    }

    // ── AI import ─────────────────────────────────────────────────────────────

    public function test_basico_cannot_access_ai_import_page(): void
    {
        [$user, $restaurant] = $this->userWithPlan('basico');

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-ai')
            ->assertRedirect(route('dashboard'));
    }

    public function test_pro_can_access_ai_import_page(): void
    {
        [$user, $restaurant] = $this->userWithPlan('pro');

        $this->actingAs($user)
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/settings/import-ai')
            ->assertSuccessful();
    }

    // ── Trial gets full access ─────────────────────────────────────────────────

    public function test_active_trial_can_access_all_import_features(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Trial Import ' . uniqid()]);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Trial Restaurant',
            'subdomain'  => 'test-trial-import-' . uniqid(),
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(5),
        ]);

        $session = ['admin_restaurant_id' => $restaurant->id];

        $this->actingAs($user)
            ->withSession($session)
            ->get('/settings/import-products')
            ->assertSuccessful();

        $this->actingAs($user)
            ->withSession($session)
            ->get('/settings/import-ai')
            ->assertSuccessful();
    }
}
