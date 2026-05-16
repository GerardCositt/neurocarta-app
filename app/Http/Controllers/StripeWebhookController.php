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
            'checkout.session.completed'    => $this->handleCheckoutCompleted($event->data->object),
            'invoice.payment_succeeded'     => $this->handlePaymentSucceeded($event->data->object),
            'invoice.payment_failed'        => $this->handlePaymentFailed($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            default                         => response('OK', 200),
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

    private function handlePaymentSucceeded(object $invoice): Response
    {
        // Only care about subscription invoices.
        $stripeSubscriptionId = $invoice->subscription ?? null;
        if (! $stripeSubscriptionId) {
            return response('OK', 200);
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (! $subscription) {
            Log::warning('StripeWebhook: invoice.payment_succeeded — no subscription found.', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return response('OK', 200);
        }

        $periodStart = isset($invoice->period_start) ? Carbon::createFromTimestamp($invoice->period_start) : null;
        $periodEnd   = isset($invoice->period_end)   ? Carbon::createFromTimestamp($invoice->period_end)   : null;

        // Idempotency: skip if period hasn't changed.
        if (
            $periodEnd !== null
            && $subscription->current_period_end_at !== null
            && $subscription->current_period_end_at->eq($periodEnd)
            && $subscription->status === 'active'
            && $subscription->grace_period_ends_at === null
        ) {
            return response('OK', 200);
        }

        $subscription->update([
            'status'                  => 'active',
            'current_period_start_at' => $periodStart,
            'current_period_end_at'   => $periodEnd,
            'grace_period_ends_at'    => null,
        ]);

        Log::info('StripeWebhook: invoice.payment_succeeded — subscription renewed.', [
            'subscription_id'        => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'period_end'             => $periodEnd?->toDateTimeString(),
        ]);

        // TODO: send PaymentSucceeded renewal email (Commit 7 — emails).

        return response('OK', 200);
    }

    private function handlePaymentFailed(object $invoice): Response
    {
        $stripeSubscriptionId = $invoice->subscription ?? null;
        if (! $stripeSubscriptionId) {
            return response('OK', 200);
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (! $subscription) {
            Log::warning('StripeWebhook: invoice.payment_failed — no subscription found.', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return response('OK', 200);
        }

        // Only extend grace period if not already in a future grace window.
        $gracePeriodEndsAt = $subscription->grace_period_ends_at;
        if ($gracePeriodEndsAt === null || $gracePeriodEndsAt->isPast()) {
            $gracePeriodEndsAt = now()->addDays(4);
        }

        $subscription->update([
            'status'               => 'past_due',
            'grace_period_ends_at' => $gracePeriodEndsAt,
        ]);

        Log::warning('StripeWebhook: invoice.payment_failed — subscription set to past_due.', [
            'subscription_id'        => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'grace_period_ends_at'   => $gracePeriodEndsAt->toDateTimeString(),
        ]);

        // TODO: send PaymentFailed email (Commit 7 — emails).

        return response('OK', 200);
    }

    private function handleSubscriptionDeleted(object $stripeSub): Response
    {
        $stripeSubscriptionId = $stripeSub->id ?? null;
        if (! $stripeSubscriptionId) {
            return response('OK', 200);
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (! $subscription) {
            Log::warning('StripeWebhook: customer.subscription.deleted — no subscription found.', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return response('OK', 200);
        }

        // Idempotency: already canceled.
        if ($subscription->status === 'canceled') {
            return response('OK', 200);
        }

        $canceledAt = isset($stripeSub->canceled_at)
            ? Carbon::createFromTimestamp($stripeSub->canceled_at)
            : now();

        $periodEnd = isset($stripeSub->current_period_end)
            ? Carbon::createFromTimestamp($stripeSub->current_period_end)
            : null;

        $payload = [
            'status'               => 'canceled',
            'canceled_at'          => $canceledAt,
            'grace_period_ends_at' => null,
        ];
        if ($periodEnd !== null) {
            $payload['current_period_end_at'] = $periodEnd;
        }

        $subscription->update($payload);

        Log::info('StripeWebhook: customer.subscription.deleted — subscription canceled.', [
            'subscription_id'        => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'canceled_at'            => $canceledAt->toDateTimeString(),
        ]);

        // TODO: send SubscriptionCanceled email (Commit 7 — emails).

        return response('OK', 200);
    }

    private function handleSubscriptionUpdated(object $stripeSub): Response
    {
        $stripeSubscriptionId = $stripeSub->id ?? null;
        if (! $stripeSubscriptionId) {
            return response('OK', 200);
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
        if (! $subscription) {
            // Not our subscription (e.g. created outside the app). Ignore silently.
            return response('OK', 200);
        }

        $updates = [];

        // Sync period dates if present.
        if (isset($stripeSub->current_period_start)) {
            $updates['current_period_start_at'] = Carbon::createFromTimestamp($stripeSub->current_period_start);
        }
        if (isset($stripeSub->current_period_end)) {
            $updates['current_period_end_at'] = Carbon::createFromTimestamp($stripeSub->current_period_end);
        }

        // Sync price ID if changed (plan upgrade/downgrade handled by Stripe).
        $newPriceId = $stripeSub->items->data[0]->price->id ?? null;
        if ($newPriceId && $newPriceId !== $subscription->stripe_price_id) {
            $updates['stripe_price_id'] = $newPriceId;
        }

        // Trial or past_due resolved — Stripe confirmed active payment.
        if (($stripeSub->status ?? null) === 'active' && in_array($subscription->status, ['trialing', 'past_due'], true)) {
            $updates['status']               = 'active';
            $updates['grace_period_ends_at'] = null;
        }

        // Scheduled cancellation: keep access until period end; record intent now.
        // customer.subscription.deleted fires at the actual end and sets status=canceled.
        if (($stripeSub->cancel_at_period_end ?? false) && $subscription->canceled_at === null) {
            $updates['canceled_at'] = now();
        }

        if (! empty($updates)) {
            $subscription->update($updates);

            Log::info('StripeWebhook: customer.subscription.updated — subscription synced.', [
                'subscription_id'        => $subscription->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'updates'                => array_keys($updates),
            ]);
        }

        return response('OK', 200);
    }
}
