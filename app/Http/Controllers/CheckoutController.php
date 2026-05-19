<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function start(Request $request)
    {
        $plan = $request->input('plan');
        $interval = $request->input('interval', 'monthly');

        if (! in_array($plan, ['basico', 'pro', 'premium'], true)) {
            return redirect()->route('subscription.expired');
        }
        if (! in_array($interval, ['monthly', 'annual'], true)) {
            $interval = 'monthly';
        }

        return view('checkout.start', compact('plan', 'interval'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'plan'     => ['required', 'in:basico,pro,premium'],
            'interval' => ['required', 'in:monthly,annual'],
        ]);

        $plan     = $request->input('plan');
        $interval = $request->input('interval');

        $user    = $request->user();
        $account = $user->accounts()->first();

        if (! $account) {
            return redirect()->route('subscription.expired')
                ->withErrors(['checkout' => 'Tu cuenta no está configurada correctamente. Contacta a soporte.']);
        }

        $subscription = $account->subscriptions()->latest()->first();

        if (! $subscription) {
            return redirect()->route('subscription.expired')
                ->withErrors(['checkout' => 'No se encontró una suscripción asociada. Contacta a soporte.']);
        }

        // Already fully active — direct to portal so they can upgrade/manage,
        // not to dashboard with a "plan is active" dead end.
        if ($subscription->status === 'active') {
            if ($subscription->stripe_customer_id) {
                return redirect()->route('subscription.manage')
                    ->with('status', 'Tu suscripción ya está activa. Desde aquí puedes cambiar de plan.');
            }
            // Active but no Stripe customer (e.g. manually activated by admin) — just go home.
            return redirect()->route('product.index')
                ->with('status', 'Tu plan ya está activo.');
        }

        // Guard: Stripe must be configured.
        $stripeSecret = config('stripe.secret');
        if (! $stripeSecret) {
            Log::error('Checkout attempted but STRIPE_SECRET is not configured.');
            return redirect()->route('subscription.expired')
                ->withErrors(['checkout' => 'El sistema de pagos no está disponible. Contacta a soporte.']);
        }

        // Guard: price ID must be configured for the requested plan/interval.
        $priceId = config("stripe.prices.{$plan}.{$interval}");
        if (! $priceId) {
            Log::error("Checkout: no Stripe price ID for plan={$plan} interval={$interval}");
            return redirect()->route('subscription.expired')
                ->withErrors(['checkout' => 'El plan seleccionado no está disponible. Contacta a soporte.']);
        }

        $stripe = new StripeClient($stripeSecret);

        try {
            // Get or create Stripe Customer.
            // DB lock prevents duplicate customers on concurrent checkout attempts.
            $stripeCustomerId = DB::transaction(function () use ($stripe, $subscription, $account, $user) {
                $locked = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

                if ($locked->stripe_customer_id) {
                    return $locked->stripe_customer_id;
                }

                $customer = $stripe->customers->create([
                    'email'    => $user->email,
                    'name'     => $account->name,
                    'metadata' => ['account_id' => (string) $account->id],
                ]);

                $locked->update(['stripe_customer_id' => $customer->id]);

                return $customer->id;
            });

            // Preserve remaining trial days only if more than 5 minutes remain.
            // The 5-minute buffer prevents Stripe rejecting a trial_end in the past
            // due to the race between form submission and Stripe's validation.
            $trialEnd = null;
            if (
                $subscription->status === 'trialing'
                && $subscription->current_period_end_at !== null
                && $subscription->current_period_end_at->isAfter(now()->addMinutes(5))
            ) {
                $trialEnd = $subscription->current_period_end_at->timestamp;
            }

            $subscriptionData = [
                'metadata' => [
                    'account_id'      => (string) $account->id,
                    'subscription_id' => (string) $subscription->id,
                ],
            ];

            if ($trialEnd !== null) {
                $subscriptionData['trial_end'] = $trialEnd;
            }

            $session = $stripe->checkout->sessions->create([
                'customer'          => $stripeCustomerId,
                'mode'              => 'subscription',
                'line_items'        => [[
                    'price'    => $priceId,
                    'quantity' => 1,
                ]],
                'metadata'          => [
                    'account_id'       => (string) $account->id,
                    'subscription_id'  => (string) $subscription->id,
                    'plan_code'        => $plan,
                    'billing_interval' => $interval,
                ],
                'subscription_data' => $subscriptionData,
                'success_url'       => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'        => route('checkout.cancel'),
                'allow_promotion_codes' => true,
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Checkout Session creation failed: ' . $e->getMessage());
            return redirect()->route('subscription.expired')
                ->withErrors(['checkout' => 'No se pudo iniciar el pago. Inténtalo de nuevo o contacta a soporte.']);
        }

        return redirect($session->url, 303);
    }

    public function success(Request $request)
    {
        return view('checkout.success');
    }

    public function cancel(Request $request)
    {
        return redirect()->route('subscription.expired')
            ->with('status', 'Has cancelado el proceso de pago. Puedes intentarlo de nuevo cuando quieras.');
    }
}
