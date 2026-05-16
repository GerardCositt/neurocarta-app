<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $webhookSecret = config('stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::error('StripeWebhook: STRIPE_WEBHOOK_SECRET is not configured.');
            return response('Webhook secret not configured.', 500);
        }

        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::warning('StripeWebhook: invalid payload — ' . $e->getMessage());
            return response('Invalid payload.', 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('StripeWebhook: signature verification failed — ' . $e->getMessage());
            return response('Invalid signature.', 400);
        }

        return match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            default                      => response('OK', 200),
        };
    }

    private function handleCheckoutCompleted(object $session): Response
    {
        // Only handle subscription-mode sessions.
        if (($session->mode ?? null) !== 'subscription') {
            return response('OK', 200);
        }

        // Stripe subscription ID must be present.
        $stripeSubscriptionId = $session->subscription ?? null;
        if (! $stripeSubscriptionId) {
            Log::warning('StripeWebhook: checkout.session.completed has no subscription ID.', [
                'session_id' => $session->id ?? null,
            ]);
            return response('OK', 200);
        }

        // Read and validate metadata.
        $meta           = $session->metadata ?? null;
        $accountId      = $meta->account_id      ?? null;
        $subscriptionId = $meta->subscription_id ?? null;
        $planCode       = $meta->plan_code        ?? null;
        $billingInterval = $meta->billing_interval ?? null;

        $validPlans     = ['basico', 'pro', 'premium'];
        $validIntervals = ['monthly', 'annual'];

        if (
            ! $accountId
            || ! $subscriptionId
            || ! in_array($planCode, $validPlans, true)
            || ! in_array($billingInterval, $validIntervals, true)
        ) {
            Log::error('StripeWebhook: checkout.session.completed has invalid or missing metadata.', [
                'session_id'       => $session->id ?? null,
                'account_id'       => $accountId,
                'subscription_id'  => $subscriptionId,
                'plan_code'        => $planCode,
                'billing_interval' => $billingInterval,
            ]);
            // Return 200 to prevent endless Stripe retries — this is a data problem, not a transient one.
            return response('OK', 200);
        }

        // Find our Subscription record.
        $subscription = Subscription::find((int) $subscriptionId);

        if (! $subscription) {
            Log::error('StripeWebhook: Subscription not found.', [
                'subscription_id' => $subscriptionId,
            ]);
            return response('OK', 200);
        }

        // Security: confirm the subscription belongs to the declared account.
        if ((string) $subscription->account_id !== (string) $accountId) {
            Log::error('StripeWebhook: account_id mismatch.', [
                'metadata_account_id'      => $accountId,
                'subscription_account_id'  => $subscription->account_id,
            ]);
            return response('OK', 200);
        }

        // Idempotency: already processed if stripe_subscription_id is already set to this value.
        if ($subscription->stripe_subscription_id === $stripeSubscriptionId) {
            return response('OK', 200);
        }

        // Retrieve the Stripe Subscription to get authoritative period dates and price ID.
        $stripeSecret = config('stripe.secret');
        if (! $stripeSecret) {
            Log::error('StripeWebhook: STRIPE_SECRET is not configured — cannot retrieve Stripe Subscription.');
            return response('Stripe secret not configured.', 500);
        }

        try {
            $stripe            = new StripeClient($stripeSecret);
            $stripeSub         = $stripe->subscriptions->retrieve($stripeSubscriptionId);
            $stripePriceId     = $stripeSub->items->data[0]->price->id ?? null;
            $periodStart       = Carbon::createFromTimestamp($stripeSub->current_period_start);
            $periodEnd         = Carbon::createFromTimestamp($stripeSub->current_period_end);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('StripeWebhook: failed to retrieve Stripe Subscription — ' . $e->getMessage(), [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            // Return 500 so Stripe will retry — this is a transient error.
            return response('Failed to retrieve Stripe Subscription.', 500);
        }

        // Activate our subscription record.
        $subscription->update([
            'status'                  => 'active',
            'stripe_subscription_id'  => $stripeSubscriptionId,
            'stripe_price_id'         => $stripePriceId,
            'plan_code'               => $planCode,
            'billing_interval'        => $billingInterval,
            'current_period_start_at' => $periodStart,
            'current_period_end_at'   => $periodEnd,
            'grace_period_ends_at'    => null,
            'canceled_at'             => null,
        ]);

        Log::info('StripeWebhook: subscription activated.', [
            'subscription_id'        => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'plan_code'              => $planCode,
            'billing_interval'       => $billingInterval,
            'period_end'             => $periodEnd->toDateTimeString(),
        ]);

        // TODO: send PaymentSucceeded confirmation email (Commit 7 — emails).

        return response('OK', 200);
    }
}
