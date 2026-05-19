<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Billing Portal redirect flow and webhook plan sync.
 *
 * NOTE: Tests that actually hit the Stripe API (portal session creation)
 * are skipped here — run those manually in Stripe test mode using the
 * checklist in docs/LAUNCH-QA.md or the checklist at the bottom of this file.
 *
 * These tests cover guard logic (auth, missing customer, inactive sub)
 * and the webhook plan_code resolver — both fully testable without Stripe.
 */
class BillingPortalTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createUserWithSubscription(array $subAttributes = []): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $account = Account::create(['name' => 'Test Account']);
        $account->users()->attach($user);

        Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Test Restaurant',
            'subdomain'  => 'test-portal-' . uniqid(),
        ]);

        $sub = Subscription::create(array_merge([
            'account_id' => $account->id,
            'plan_code'  => 'basico',
            'status'     => 'active',
        ], $subAttributes));

        return [$user, $account, $sub];
    }

    // ─── Auth guards ─────────────────────────────────────────────────────────

    public function test_guest_cannot_access_portal(): void
    {
        $response = $this->post('/subscription/portal');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_manage_page(): void
    {
        $response = $this->get('/subscription/manage');
        $response->assertRedirect('/login');
    }

    // ─── Manage page ─────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_manage_page(): void
    {
        [$user] = $this->createUserWithSubscription([
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $response = $this->actingAs($user)->get('/subscription/manage');
        $response->assertOk();
        $response->assertSee('Gestionar suscripción');
    }

    // ─── Portal redirect guards ───────────────────────────────────────────────

    public function test_user_without_stripe_customer_is_redirected_to_expired(): void
    {
        // User has active sub but never completed Stripe checkout (no stripe_customer_id).
        [$user] = $this->createUserWithSubscription([
            'stripe_customer_id' => null,
        ]);

        $response = $this->actingAs($user)->post('/subscription/portal');
        $response->assertRedirect(route('subscription.expired'));
    }

    public function test_user_without_account_is_redirected_to_expired(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        // No account attached.

        $response = $this->actingAs($user)->post('/subscription/portal');
        $response->assertRedirect(route('subscription.expired'));
    }

    public function test_user_without_subscription_is_redirected_to_expired(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'No Sub Account']);
        $account->users()->attach($user);

        $response = $this->actingAs($user)->post('/subscription/portal');
        $response->assertRedirect(route('subscription.expired'));
    }

    public function test_portal_fails_gracefully_without_stripe_secret(): void
    {
        [$user] = $this->createUserWithSubscription([
            'stripe_customer_id' => 'cus_test_123',
        ]);

        config(['stripe.secret' => null]);

        $response = $this->actingAs($user)->post('/subscription/portal');
        // No Stripe secret → redirect home with error (not a 500 crash).
        $response->assertRedirect(route('product.index'));
    }

    // ─── CheckoutController active guard ─────────────────────────────────────

    public function test_active_subscriber_with_stripe_customer_is_redirected_to_manage(): void
    {
        [$user, , $sub] = $this->createUserWithSubscription([
            'status'             => 'active',
            'stripe_customer_id' => 'cus_test_456',
            'plan_code'          => 'basico',
            'billing_interval'   => 'monthly',
        ]);

        $response = $this->actingAs($user)->post('/checkout', [
            '_token'   => csrf_token(),
            'plan'     => 'pro',
            'interval' => 'monthly',
        ]);

        $response->assertRedirect(route('subscription.manage'));
    }

    public function test_active_subscriber_without_stripe_customer_is_redirected_to_home(): void
    {
        [$user] = $this->createUserWithSubscription([
            'status'             => 'active',
            'stripe_customer_id' => null,
        ]);

        $response = $this->actingAs($user)->post('/checkout', [
            '_token'   => csrf_token(),
            'plan'     => 'pro',
            'interval' => 'monthly',
        ]);

        $response->assertRedirect(route('product.index'));
    }

    // ─── Webhook plan resolver ────────────────────────────────────────────────

    /**
     * @dataProvider priceIdProvider
     */
    public function test_resolve_plan_from_price_id(string $plan, string $interval, string $envKey): void
    {
        $priceId = 'price_test_' . $plan . '_' . $interval;
        config(["stripe.prices.{$plan}.{$interval}" => $priceId]);

        $controller = new StripeWebhookController();
        $method     = new \ReflectionMethod($controller, 'resolvePlanFromPriceId');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $priceId);

        $this->assertNotNull($result, "Expected to resolve price ID for {$plan}/{$interval}");
        $this->assertSame($plan, $result['plan_code']);
        $this->assertSame($interval, $result['billing_interval']);
    }

    public static function priceIdProvider(): array
    {
        return [
            'basico monthly'  => ['basico',  'monthly', 'STRIPE_PRICE_BASICO_MONTHLY'],
            'basico annual'   => ['basico',  'annual',  'STRIPE_PRICE_BASICO_ANNUAL'],
            'pro monthly'     => ['pro',     'monthly', 'STRIPE_PRICE_PRO_MONTHLY'],
            'pro annual'      => ['pro',     'annual',  'STRIPE_PRICE_PRO_ANNUAL'],
            'premium monthly' => ['premium', 'monthly', 'STRIPE_PRICE_PREMIUM_MONTHLY'],
            'premium annual'  => ['premium', 'annual',  'STRIPE_PRICE_PREMIUM_ANNUAL'],
        ];
    }

    public function test_resolve_plan_returns_null_for_unknown_price(): void
    {
        $controller = new StripeWebhookController();
        $method     = new \ReflectionMethod($controller, 'resolvePlanFromPriceId');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'price_totally_unknown_xyz');

        $this->assertNull($result);
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════════
 * CHECKLIST — Stripe TEST MODE (manual, con claves test)
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * Prerrequisito: configurar en .env STRIPE_SECRET=sk_test_..., todos los
 * STRIPE_PRICE_* con price IDs de test, y STRIPE_WEBHOOK_SECRET.
 * Configurar Portal en Stripe Dashboard → Billing → Customer portal:
 *   - Habilitar "Update subscriptions" (upgrade + downgrade)
 *   - Upgrades: immediately / prorate (cobro inmediato de la diferencia)
 *   - Downgrades: at billing period end
 *   - Cancellations: at billing period end
 *   - Return URL: http://localhost/product (o la URL de staging)
 *
 * 1. [ ] Registro con plan Básico mensual → alta en Stripe test mode
 *        → subscription.status='active', plan_code='basico', billing_interval='monthly'
 *
 * 2. [ ] Con usuario Básico activo → sidebar muestra "Mejora tu plan"
 *        → click → /subscription/manage muestra "Plan actual: Básico mensual"
 *        → click "Abrir portal" → redirige a Stripe Customer Portal
 *
 * 3. [ ] En portal Stripe: upgrade Básico → Pro
 *        → Stripe cobra proration inmediata
 *        → webhook customer.subscription.updated llega
 *        → DB: plan_code='pro', billing_interval='monthly', stripe_price_id actualizado
 *        → sidebar muestra "Plan Pro" con "Gestionar plan"
 *
 * 4. [ ] En portal Stripe: downgrade Pro → Básico
 *        → Stripe programa cambio al final del período
 *        → webhook customer.subscription.updated llega con schedule
 *        → acceso Pro se mantiene hasta period_end
 *        → al renovar: plan_code='basico' en DB
 *
 * 5. [ ] En portal Stripe: cambio mensual → anual
 *        → proration aplicada correctamente
 *        → DB: billing_interval='annual' tras webhook
 *
 * 6. [ ] En portal Stripe: cancelación al final del período
 *        → DB: canceled_at registrado, status='canceled' al expirar
 *        → carta pública bloqueada al day+1
 *
 * 7. [ ] Usuario con plan activo intenta POST /checkout (URL directa)
 *        → redirige a /subscription/manage, no a dashboard ni 500
 *
 * 8. [ ] Usuario sin STRIPE_SECRET configurado → portal muestra error grácil
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 */
