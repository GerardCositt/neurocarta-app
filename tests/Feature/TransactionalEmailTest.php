<?php

namespace Tests\Feature;

use App\Mail\PlanChanged;
use App\Mail\WelcomeSetPassword;
use App\Models\Account;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransactionalEmailTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_testsecret1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config(['stripe.webhook_secret' => self::WEBHOOK_SECRET]);
        // Wire up test price IDs so plan resolution works in webhooks
        config([
            'stripe.prices.basico.monthly' => 'price_basico_monthly_test',
            'stripe.prices.pro.monthly'    => 'price_pro_monthly_test',
            'stripe.prices.pro.annual'     => 'price_pro_annual_test',
            'stripe.prices.premium.monthly' => 'price_premium_monthly_test',
        ]);
        Mail::fake();
    }

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

    // ── Registration ──────────────────────────────────────────────────────────

    public function test_welcome_email_is_sent_on_registration(): void
    {
        $this->post('/register', [
            'email'           => 'welcome@example.com',
            'restaurant_name' => 'Restaurante Welcome',
            'phone'           => '+34600000099',
            'plan'            => 'trial',
        ]);

        Mail::assertSent(WelcomeSetPassword::class, function ($mail) {
            return $mail->hasTo('welcome@example.com');
        });
    }

    public function test_welcome_email_not_sent_twice_for_same_registration(): void
    {
        $this->post('/register', [
            'email'           => 'once@example.com',
            'restaurant_name' => 'Restaurante Once',
            'phone'           => '+34600000001',
            'plan'            => 'trial',
        ]);

        Mail::assertSentCount(1);
        Mail::assertSent(WelcomeSetPassword::class);
    }

    // ── Plan changed ──────────────────────────────────────────────────────────

    public function test_plan_changed_email_sent_on_upgrade(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Upgrade Test']);
        $account->users()->attach($user);

        $subscription = Subscription::create([
            'account_id'             => $account->id,
            'plan_code'              => 'basico',
            'billing_interval'       => 'monthly',
            'status'                 => 'active',
            'stripe_subscription_id' => 'sub_upgrade_test',
            'stripe_price_id'        => 'price_basico_monthly_test',
            'current_period_end_at'  => now()->addMonth(),
        ]);

        $this->postWebhook([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => [
                'id'                   => 'sub_upgrade_test',
                'status'               => 'active',
                'cancel_at_period_end' => false,
                'current_period_start' => now()->timestamp,
                'current_period_end'   => now()->addMonth()->timestamp,
                'items'                => ['data' => [
                    ['price' => ['id' => 'price_pro_monthly_test']],
                ]],
            ]],
        ]);

        Mail::assertSent(PlanChanged::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_plan_changed_email_not_sent_when_plan_unchanged(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'No Change Test']);
        $account->users()->attach($user);

        Subscription::create([
            'account_id'             => $account->id,
            'plan_code'              => 'pro',
            'billing_interval'       => 'monthly',
            'status'                 => 'active',
            'stripe_subscription_id' => 'sub_nochange_test',
            'stripe_price_id'        => 'price_pro_monthly_test',
            'current_period_end_at'  => now()->addMonth(),
        ]);

        // Same price ID — no plan change
        $this->postWebhook([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => [
                'id'                   => 'sub_nochange_test',
                'status'               => 'active',
                'cancel_at_period_end' => false,
                'items'                => ['data' => [
                    ['price' => ['id' => 'price_pro_monthly_test']],
                ]],
            ]],
        ]);

        Mail::assertNotSent(PlanChanged::class);
    }

    public function test_plan_changed_email_sent_on_interval_switch(): void
    {
        $user    = User::factory()->create(['email_verified_at' => now()]);
        $account = Account::create(['name' => 'Interval Switch Test']);
        $account->users()->attach($user);

        Subscription::create([
            'account_id'             => $account->id,
            'plan_code'              => 'pro',
            'billing_interval'       => 'monthly',
            'status'                 => 'active',
            'stripe_subscription_id' => 'sub_interval_test',
            'stripe_price_id'        => 'price_pro_monthly_test',
            'current_period_end_at'  => now()->addMonth(),
        ]);

        // Switch to annual
        $this->postWebhook([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => [
                'id'                   => 'sub_interval_test',
                'status'               => 'active',
                'cancel_at_period_end' => false,
                'current_period_start' => now()->timestamp,
                'current_period_end'   => now()->addYear()->timestamp,
                'items'                => ['data' => [
                    ['price' => ['id' => 'price_pro_annual_test']],
                ]],
            ]],
        ]);

        Mail::assertSent(PlanChanged::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
