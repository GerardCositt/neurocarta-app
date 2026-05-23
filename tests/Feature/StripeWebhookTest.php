<?php

namespace Tests\Feature;

use App\Mail\PaymentFailed;
use App\Mail\PaymentSucceeded;
use App\Mail\SubscriptionCanceled;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_testsecret1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config(['stripe.webhook_secret' => self::WEBHOOK_SECRET]);
        Mail::fake();
    }

    /**
     * POST to /stripe/webhook with a raw body signed with the test secret.
     */
    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body      = json_encode($payload);
        $timestamp = time();
        $sig       = hash_hmac('sha256', $timestamp . '.' . $body, self::WEBHOOK_SECRET);
        $header    = "t={$timestamp},v1={$sig}";

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $header,
        ], $body);
    }

    private function makeAccount(): array
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Webhook Test']);
        $account->users()->attach($user);

        $subscription = Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'pro',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(7),
        ]);

        return [$user, $account, $subscription];
    }

    // ── Guard tests ───────────────────────────────────────────────────────────

    public function test_returns_500_when_webhook_secret_is_not_configured(): void
    {
        config(['stripe.webhook_secret' => null]);

        $response = $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 'invalid',
        ], '{}');

        $response->assertStatus(500);
    }

    public function test_returns_400_on_invalid_signature(): void
    {
        $response = $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=1234,v1=badsignature',
        ], json_encode(['type' => 'some.event']));

        $response->assertStatus(400);
    }

    public function test_returns_200_for_unknown_event_type(): void
    {
        $response = $this->postWebhook([
            'type' => 'completely.unknown.event',
            'data' => ['object' => []],
        ]);

        $response->assertStatus(200);
    }

    // ── invoice.payment_succeeded ─────────────────────────────────────────────

    public function test_payment_succeeded_renews_subscription_and_sends_email(): void
    {
        [, , $subscription] = $this->makeAccount();

        $subscription->update([
            'stripe_subscription_id' => 'sub_test_abc',
            'status'                 => 'active',
        ]);

        $newPeriodEnd = now()->addMonth()->timestamp;

        $response = $this->postWebhook([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => [
                'subscription'   => 'sub_test_abc',
                'billing_reason' => 'subscription_cycle',
                'period_start'   => now()->timestamp,
                'period_end'     => $newPeriodEnd,
            ]],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->grace_period_ends_at);

        Mail::assertSent(PaymentSucceeded::class);
    }

    public function test_payment_succeeded_skips_email_on_subscription_create_reason(): void
    {
        [, , $subscription] = $this->makeAccount();
        $subscription->update(['stripe_subscription_id' => 'sub_new_abc']);

        $response = $this->postWebhook([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => [
                'subscription'   => 'sub_new_abc',
                'billing_reason' => 'subscription_create',
                'period_start'   => now()->timestamp,
                'period_end'     => now()->addMonth()->timestamp,
            ]],
        ]);

        $response->assertStatus(200);
        Mail::assertNotSent(PaymentSucceeded::class);
    }

    public function test_payment_succeeded_returns_200_when_no_subscription_found(): void
    {
        $response = $this->postWebhook([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => [
                'subscription' => 'sub_nonexistent',
            ]],
        ]);

        $response->assertStatus(200);
    }

    // ── invoice.payment_failed ────────────────────────────────────────────────

    public function test_payment_failed_sets_past_due_with_grace_period(): void
    {
        [, , $subscription] = $this->makeAccount();
        $subscription->update([
            'stripe_subscription_id' => 'sub_fail_abc',
            'status'                 => 'active',
        ]);

        $response = $this->postWebhook([
            'type' => 'invoice.payment_failed',
            'data' => ['object' => [
                'subscription' => 'sub_fail_abc',
            ]],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertSame('past_due', $subscription->status);
        $this->assertNotNull($subscription->grace_period_ends_at);
        $this->assertTrue($subscription->grace_period_ends_at->isFuture());

        Mail::assertSent(PaymentFailed::class);
    }

    public function test_payment_failed_does_not_shorten_existing_future_grace_period(): void
    {
        [, , $subscription] = $this->makeAccount();
        $existingGrace = now()->addDays(10)->startOfSecond();
        $subscription->update([
            'stripe_subscription_id' => 'sub_grace_abc',
            'status'                 => 'past_due',
            'grace_period_ends_at'   => $existingGrace,
        ]);

        $this->postWebhook([
            'type' => 'invoice.payment_failed',
            'data' => ['object' => ['subscription' => 'sub_grace_abc']],
        ]);

        $subscription->refresh();
        $this->assertEqualsWithDelta(
            $existingGrace->timestamp,
            $subscription->grace_period_ends_at->timestamp,
            1
        );
    }

    // ── customer.subscription.deleted ────────────────────────────────────────

    public function test_subscription_deleted_cancels_subscription_and_sends_email(): void
    {
        [, , $subscription] = $this->makeAccount();
        $subscription->update([
            'stripe_subscription_id' => 'sub_del_abc',
            'status'                 => 'active',
        ]);

        $canceledAt = now()->subHour()->timestamp;

        $response = $this->postWebhook([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => [
                'id'                  => 'sub_del_abc',
                'canceled_at'         => $canceledAt,
                'current_period_end'  => now()->addDays(10)->timestamp,
            ]],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertSame('canceled', $subscription->status);
        $this->assertNotNull($subscription->canceled_at);

        Mail::assertSent(SubscriptionCanceled::class);
    }

    public function test_subscription_deleted_is_idempotent(): void
    {
        [, , $subscription] = $this->makeAccount();
        $subscription->update([
            'stripe_subscription_id' => 'sub_idem_abc',
            'status'                 => 'canceled',
        ]);

        $response = $this->postWebhook([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_idem_abc']],
        ]);

        $response->assertStatus(200);
        // No second cancellation email.
        Mail::assertNotSent(SubscriptionCanceled::class);
    }

    // ── customer.subscription.updated ─────────────────────────────────────────

    public function test_subscription_updated_syncs_period_dates(): void
    {
        [, , $subscription] = $this->makeAccount();
        $subscription->update(['stripe_subscription_id' => 'sub_upd_abc']);

        $newEnd = now()->addDays(30)->timestamp;

        $response = $this->postWebhook([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => [
                'id'                   => 'sub_upd_abc',
                'status'               => 'active',
                'cancel_at_period_end' => false,
                'current_period_start' => now()->timestamp,
                'current_period_end'   => $newEnd,
                'items'                => ['data' => []],
            ]],
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertEqualsWithDelta($newEnd, $subscription->current_period_end_at->timestamp, 2);
    }

    public function test_subscription_updated_activates_past_due_when_stripe_confirms_active(): void
    {
        [, , $subscription] = $this->makeAccount();
        $subscription->update([
            'stripe_subscription_id' => 'sub_reac_abc',
            'status'                 => 'past_due',
            'grace_period_ends_at'   => now()->addDays(2),
        ]);

        $this->postWebhook([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => [
                'id'                   => 'sub_reac_abc',
                'status'               => 'active',
                'cancel_at_period_end' => false,
                'items'                => ['data' => []],
            ]],
        ]);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->grace_period_ends_at);
    }

    // ── checkout.session.completed — validation paths (no Stripe API call) ───

    public function test_checkout_session_non_subscription_mode_is_ignored(): void
    {
        $response = $this->postWebhook([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'mode'         => 'payment',
                'subscription' => null,
                'metadata'     => [],
            ]],
        ]);

        $response->assertStatus(200);
    }

    public function test_checkout_session_with_missing_metadata_returns_200(): void
    {
        $response = $this->postWebhook([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id'           => 'cs_test_abc',
                'mode'         => 'subscription',
                'subscription' => 'sub_xyz',
                'metadata'     => [],
            ]],
        ]);

        $response->assertStatus(200);
    }

    public function test_checkout_session_with_account_id_mismatch_returns_200(): void
    {
        [, $account, $subscription] = $this->makeAccount();

        $wrongAccountId = $account->id + 9999;

        $response = $this->postWebhook([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id'           => 'cs_test_mismatch',
                'mode'         => 'subscription',
                'subscription' => 'sub_new_xyz',
                'metadata'     => [
                    'account_id'       => (string) $wrongAccountId,
                    'subscription_id'  => (string) $subscription->id,
                    'plan_code'        => 'pro',
                    'billing_interval' => 'monthly',
                ],
            ]],
        ]);

        $response->assertStatus(200);

        // Subscription must NOT be activated.
        $subscription->refresh();
        $this->assertNotSame('active', $subscription->status);
    }
}
