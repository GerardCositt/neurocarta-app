<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class BillingPortalController extends Controller
{
    /**
     * Create a Stripe Billing Portal session and redirect the user there.
     *
     * The Portal lets active subscribers upgrade, downgrade, cancel,
     * update payment method and view invoices — all without custom billing logic.
     *
     * Proration policy (configured in Stripe Dashboard → Billing → Customer portal):
     *   - Upgrades: immediate with proration (charge difference now).
     *   - Downgrades: at period end (no immediate credit).
     *   - Cancellations: at period end (access kept until paid period ends).
     */
    public function redirect(Request $request)
    {
        $user    = $request->user();
        $account = $user->accounts()->first();

        if (! $account) {
            return redirect()->route('subscription.expired')
                ->withErrors(['portal' => 'Cuenta no encontrada. Contacta a soporte.']);
        }

        $subscription = $account->subscriptions()->latest()->first();

        if (! $subscription) {
            return redirect()->route('subscription.expired')
                ->withErrors(['portal' => 'No tienes una suscripción asociada.']);
        }

        // No Stripe customer yet means the user never completed a Stripe checkout.
        // Send them to the expired/checkout page to pay first.
        if (! $subscription->stripe_customer_id) {
            return redirect()->route('subscription.expired');
        }

        $stripeSecret = config('stripe.secret');
        if (! $stripeSecret) {
            Log::error('BillingPortal: STRIPE_SECRET is not configured.');
            return redirect()->route('product')
                ->withErrors(['portal' => 'El sistema de pagos no está disponible. Contacta a soporte.']);
        }

        try {
            $stripe = new StripeClient($stripeSecret);

            $session = $stripe->billingPortal->sessions->create([
                'customer'   => $subscription->stripe_customer_id,
                'return_url' => route('subscription.manage'),
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('BillingPortal: failed to create session — ' . $e->getMessage(), [
                'account_id'          => $account->id,
                'stripe_customer_id'  => $subscription->stripe_customer_id,
            ]);
            return redirect()->route('product')
                ->withErrors(['portal' => 'No se pudo abrir el portal de facturación. Inténtalo de nuevo.']);
        }

        return redirect($session->url, 303);
    }
}
