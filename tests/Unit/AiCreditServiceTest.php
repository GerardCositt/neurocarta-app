<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientAiCreditsException;
use App\Models\Account;
use App\Models\AiUsageLog;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AiCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    private function makeRestaurantWithCredits(int $credits): array
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'AI Test']);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'AI Restaurant',
            'subdomain'  => 'ai-test-' . uniqid(),
            'ai_credits' => $credits,
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'pro',
            'status'                => 'active',
            'current_period_end_at' => now()->addMonth(),
        ]);

        return [$user, $restaurant];
    }

    // ── Pure calculation tests (no DB or session needed) ──────────────────────

    public function test_cost_returns_correct_base_credit_cost(): void
    {
        $svc = $this->service();

        $this->assertSame(10, $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE));
        $this->assertSame(5,  $svc->cost(AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE));
        $this->assertSame(15, $svc->cost(AiCreditService::ACTION_IMPORT_MENU));
        $this->assertSame(3,  $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION));
        $this->assertSame(2,  $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT));
    }

    public function test_cost_multiplies_correctly_by_units(): void
    {
        $svc = $this->service();

        $this->assertSame(30,  $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION, 10));
        $this->assertSame(100, $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE, 10));
        $this->assertSame(3,   $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION, 0));  // min 1 unit
    }

    public function test_cost_returns_zero_for_unknown_action(): void
    {
        $this->assertSame(0, $this->service()->cost('nonexistent_action'));
    }

    public function test_euro_cents_for_credits(): void
    {
        $svc = $this->service();

        $this->assertSame(0,   $svc->euroCentsForCredits(0));
        $this->assertSame(50,  $svc->euroCentsForCredits(10));  // 10 × 5 cents
        $this->assertSame(500, $svc->euroCentsForCredits(100));
    }

    public function test_format_euros_from_credits(): void
    {
        $svc = $this->service();

        $this->assertSame('0,50 €', $svc->formatEurosFromCredits(10));
        $this->assertSame('1,50 €', $svc->formatEurosFromCredits(30));
        $this->assertSame('5,00 €', $svc->formatEurosFromCredits(100));
    }

    // ── Session-dependent tests ────────────────────────────────────────────────

    public function test_ensure_can_afford_throws_when_credits_are_insufficient(): void
    {
        config(['services.openai.key' => 'sk-platform-test']);

        [$user, $restaurant] = $this->makeRestaurantWithCredits(2);

        $this->actingAs($user);
        session(['admin_restaurant_id' => $restaurant->id]);

        $this->expectException(InsufficientAiCreditsException::class);

        // ACTION_GENERATE_PRODUCT_IMAGE costs 10 — only 2 credits available.
        $this->service()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE);
    }

    public function test_ensure_can_afford_passes_with_sufficient_credits(): void
    {
        config(['services.openai.key' => 'sk-platform-test']);

        [$user, $restaurant] = $this->makeRestaurantWithCredits(50);

        $this->actingAs($user);
        session(['admin_restaurant_id' => $restaurant->id]);

        // Should not throw.
        $this->service()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION);

        $this->assertTrue(true);
    }

    public function test_ensure_can_afford_skips_check_when_billing_mode_is_none(): void
    {
        config(['services.openai.key' => '']);  // No API key → billing mode 'none'

        [$user, $restaurant] = $this->makeRestaurantWithCredits(0);

        $this->actingAs($user);
        session(['admin_restaurant_id' => $restaurant->id]);

        // 0 credits + no key = no check → should not throw.
        $this->service()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE);

        $this->assertTrue(true);
    }

    public function test_spend_decrements_credits_and_writes_usage_log(): void
    {
        config(['services.openai.key' => 'sk-platform-test']);

        [$user, $restaurant] = $this->makeRestaurantWithCredits(50);

        $this->actingAs($user);
        session(['admin_restaurant_id' => $restaurant->id]);

        $this->service()->spend(
            AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION,
            1,
            ['product_name' => 'Pulpo a la gallega']
        );

        $restaurant->refresh();
        $this->assertSame(47, $restaurant->ai_credits);  // 50 − 3

        $log = AiUsageLog::withoutGlobalScopes()
            ->where('restaurant_id', $restaurant->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION, $log->action);
        $this->assertSame(3, $log->credits);
        $this->assertSame('completed', $log->status);
    }

    public function test_spend_does_not_decrement_when_billing_mode_is_none(): void
    {
        config(['services.openai.key' => '']);  // No API key

        [$user, $restaurant] = $this->makeRestaurantWithCredits(50);

        $this->actingAs($user);
        session(['admin_restaurant_id' => $restaurant->id]);

        $this->service()->spend(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION);

        $restaurant->refresh();
        $this->assertSame(50, $restaurant->ai_credits);  // Unchanged
    }

    public function test_spend_bulk_decrements_by_unit_count(): void
    {
        config(['services.openai.key' => 'sk-platform-test']);

        [$user, $restaurant] = $this->makeRestaurantWithCredits(100);

        $this->actingAs($user);
        session(['admin_restaurant_id' => $restaurant->id]);

        // Bulk generate 3 images = 3 × 10 = 30 credits
        $this->service()->spend(AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES, 3);

        $restaurant->refresh();
        $this->assertSame(70, $restaurant->ai_credits);
    }
}
