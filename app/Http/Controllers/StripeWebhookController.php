<?php

namespace App\Http\Controllers;

use App\Mail\PaymentFailed;
use App\Mail\PaymentSucceeded;
use App\Mail\PlanChanged;
use App\Mail\SubscriptionActivated;
use App\Mail\SubscriptionCanceled;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $mode = $session->mode ?? null;

        // Credit purchase (one-time payment).
        if ($mode === 'payment' && ($session->metadata->type ?? null) === 'credit_purchase') {
            return $this->handleCreditPurchaseCompleted($session);
        }

        // Only handle subscription-mode sessions beyond this point.
        if ($mode !== 'subscription') {
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

        // Welcome bonus: 500 credits for Pro/Premium annual subscriptions (one-time, on first activation).
        if ($billingInterval === 'annual' && in_array($planCode, ['pro', 'premium'], true)) {
            $account = $subscription->account()->with('restaurants')->first();
            if ($account) {
                $account->restaurants->each(fn ($r) => $r->increment('ai_credits', 500));
                Log::info('StripeWebhook: annual welcome bonus applied.', [
                    'subscription_id' => $subscription->id,
                    'plan_code'       => $planCode,
                    'bonus_credits'   => 500,
                ]);
            }
        }

        $this->mailAccountUsers($subscription, fn ($user) => new SubscriptionActivated($user, $subscription));

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

        // Skip renewal email for the first invoice — activation email already sent via checkout.session.completed.
        if (($invoice->billing_reason ?? null) !== 'subscription_create') {
            $this->mailAccountUsers($subscription, fn ($user) => new PaymentSucceeded($user, $subscription));
        }

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

        $this->mailAccountUsers($subscription, fn ($user) => new PaymentFailed($user, $subscription));

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

        $this->mailAccountUsers($subscription, fn ($user) => new SubscriptionCanceled($user, $subscription));

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

        $oldPlanCode        = $subscription->plan_code;
        $oldBillingInterval = $subscription->billing_interval;

        $updates = [];

        // Sync period dates if present.
        if (isset($stripeSub->current_period_start)) {
            $updates['current_period_start_at'] = Carbon::createFromTimestamp($stripeSub->current_period_start);
        }
        if (isset($stripeSub->current_period_end)) {
            $updates['current_period_end_at'] = Carbon::createFromTimestamp($stripeSub->current_period_end);
        }

        // Sync price ID if changed — and resolve plan_code + billing_interval from the new price.
        // This covers upgrades/downgrades made through the Billing Portal or direct API calls.
        $newPriceId = $stripeSub->items->data[0]->price->id ?? null;
        if ($newPriceId && $newPriceId !== $subscription->stripe_price_id) {
            $updates['stripe_price_id'] = $newPriceId;

            $resolved = $this->resolvePlanFromPriceId($newPriceId);
            if ($resolved) {
                $updates['plan_code']        = $resolved['plan_code'];
                $updates['billing_interval'] = $resolved['billing_interval'];

                Log::info('StripeWebhook: plan change detected via Portal/API.', [
                    'subscription_id'   => $subscription->id,
                    'old_plan'          => $subscription->plan_code,
                    'new_plan'          => $resolved['plan_code'],
                    'old_interval'      => $subscription->billing_interval,
                    'new_interval'      => $resolved['billing_interval'],
                ]);
            } else {
                // Price ID not in our config — log for investigation but don't crash.
                Log::warning('StripeWebhook: unrecognized Stripe price ID on subscription update.', [
                    'subscription_id' => $subscription->id,
                    'stripe_price_id' => $newPriceId,
                ]);
            }
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

            // Send email when the plan or interval actually changed.
            if (
                isset($updates['plan_code'])
                && ($updates['plan_code'] !== $oldPlanCode || ($updates['billing_interval'] ?? $oldBillingInterval) !== $oldBillingInterval)
            ) {
                $this->mailAccountUsers($subscription, fn ($user) => new PlanChanged($user, $subscription, $oldPlanCode, $oldBillingInterval));
            }
        }

        return response('OK', 200);
    }

    /**
     * Reverse-lookup plan_code and billing_interval from a Stripe price ID.
     * Uses the same config('stripe.prices') map used at checkout time.
     *
     * Returns ['plan_code' => '...', 'billing_interval' => '...'] or null if not found.
     */
    private function resolvePlanFromPriceId(string $priceId): ?array
    {
        foreach (config('stripe.prices', []) as $planCode => $intervals) {
            foreach ($intervals as $interval => $configuredPriceId) {
                if ($configuredPriceId && $configuredPriceId === $priceId) {
                    return ['plan_code' => $planCode, 'billing_interval' => $interval];
                }
            }
        }

        return null;
    }

    private function handleCreditPurchaseCompleted(object $session): Response
    {
        $meta         = $session->metadata ?? null;
        $restaurantId = $meta->restaurant_id ?? null;
        $credits      = (int) ($meta->credits ?? 0);
        $packageKey   = $meta->package_key ?? '?';

        if (! $restaurantId || $credits <= 0) {
            Log::error('StripeWebhook: credit_purchase missing metadata.', [
                'session_id'    => $session->id ?? null,
                'restaurant_id' => $restaurantId,
                'credits'       => $credits,
            ]);
            return response('OK', 200);
        }

        $restaurant = Restaurant::find((int) $restaurantId);
        if (! $restaurant) {
            Log::error('StripeWebhook: credit_purchase — restaurant not found.', [
                'restaurant_id' => $restaurantId,
            ]);
            return response('OK', 200);
        }

        $restaurant->increment('ai_credits', $credits);

        Log::info('StripeWebhook: credit_purchase completed.', [
            'restaurant_id' => $restaurantId,
            'credits_added' => $credits,
            'package_key'   => $packageKey,
            'session_id'    => $session->id ?? null,
        ]);

        return response('OK', 200);
    }

    private function mailAccountUsers(Subscription $subscription, \Closure $makeMailFn): void
    {
        try {
            $users = $subscription->account?->users ?? collect();
            if ($users->isEmpty()) {
                return;
            }
            foreach ($users as $user) {
                Mail::to($user->email)->send($makeMailFn($user));
            }
        } catch (\Throwable $e) {
            Log::error('StripeWebhook: email send failed — ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
            ]);
        }
    }
}
