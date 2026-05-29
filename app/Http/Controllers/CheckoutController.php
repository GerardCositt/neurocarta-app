<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionActivated;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        // Already active with a real Stripe subscription → send to portal to manage/upgrade.
        // Active but no stripe_subscription_id (manually set) → allow checkout to proceed.
        if ($subscription->status === 'active' && $subscription->stripe_subscription_id) {
            return redirect()->route('subscription.manage')
                ->with('status', 'Tu suscripción ya está activa. Desde aquí puedes cambiar de plan.');
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
                    // Verify customer is usable in this Stripe mode.
                    // Deleted customers return { deleted: true } without throwing — check explicitly.
                    try {
                        $existing = $stripe->customers->retrieve($locked->stripe_customer_id);
                        if (empty($existing->deleted)) {
                            return $locked->stripe_customer_id;
                        }
                        $locked->update(['stripe_customer_id' => null]);
                    } catch (\Stripe\Exception\ApiErrorException $e) {
                        // Customer not found or not accessible in this mode — fall through to create.
                        $locked->update(['stripe_customer_id' => null]);
                    }
                }

                $customer = $stripe->customers->create([
                    'email'              => $user->email,
                    'name'               => $account->name,
                    'preferred_locales'  => ['es'],
                    'metadata'           => ['account_id' => (string) $account->id],
                ]);

                $locked->update(['stripe_customer_id' => $customer->id]);

                return $customer->id;
            });

            // Guard against duplicate subscriptions: if this customer already has an
            // active or trialing Stripe subscription, sync our DB and redirect to portal.
            $existingStripeSubscriptions = $stripe->subscriptions->all([
                'customer' => $stripeCustomerId,
                'status'   => 'active',
                'limit'    => 1,
            ]);
            if (count($existingStripeSubscriptions->data) > 0) {
                $existingSub = $existingStripeSubscriptions->data[0];
                $subscription->update([
                    'stripe_subscription_id' => $existingSub->id,
                    'status'                 => 'active',
                ]);
                Log::info('Checkout blocked: customer already has active Stripe subscription.', [
                    'stripe_subscription_id' => $existingSub->id,
                    'account_id'             => $account->id,
                ]);
                return redirect()->route('subscription.manage')
                    ->with('status', 'Tu suscripción ya está activa. Desde aquí puedes gestionar tu plan.');
            }

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
                'customer'             => $stripeCustomerId,
                'mode'                 => 'subscription',
                'payment_method_types' => ['card'],
                'line_items'           => [[
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
        session(['checkout_just_completed' => now()->timestamp]);

        // Activación directa: no esperamos al webhook de Stripe.
        // Stripe pasa el session_id en la URL; lo usamos para activar la suscripción
        // y enviar el email de confirmación ahora mismo.
        // Si falla (Stripe API error, sesión ya procesada, etc.) el webhook lo reintenta.
        $sessionId = $request->query('session_id');
        $activated = false;

        if ($sessionId && config('stripe.secret')) {
            $activated = $this->activateFromCheckoutSession($sessionId);
        }

        // Cache fallback cross-subdominio: si la activación directa no pudo completarse,
        // damos una ventana de 15 min para que el webhook llegue mientras la carta sigue accesible.
        if (! $activated) {
            $user = $request->user();
            if ($user) {
                $account = $user->accounts()->first();
                if ($account) {
                    cache()->put("checkout_just_paid_{$account->id}", true, now()->addMinutes(15));
                }
            }
        }

        return view('checkout.success');
    }

    /**
     * Activa la suscripción directamente desde el session_id de Stripe que viene en la success_url.
     * Duplica el resultado final de handleCheckoutCompleted() del webhook, pero de forma síncrona
     * y desde el navegador del usuario — sin depender de la llegada del webhook.
     * El webhook sigue siendo idempotente: si stripe_subscription_id ya está guardado, no hace nada.
     */
    private function activateFromCheckoutSession(string $sessionId): bool
    {
        try {
            $stripe  = new StripeClient(config('stripe.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['subscription'],
            ]);

            if (($session->mode ?? null) !== 'subscription') {
                return false;
            }

            $meta           = $session->metadata ?? null;
            $subscriptionId = $meta->subscription_id ?? null;
            $planCode       = $meta->plan_code        ?? null;
            $billingInterval = $meta->billing_interval ?? null;

            if (! $subscriptionId) {
                Log::warning('CheckoutSuccess: session_id sin subscription_id en metadata.', [
                    'session_id' => $sessionId,
                ]);
                return false;
            }

            $subscription = Subscription::find((int) $subscriptionId);
            if (! $subscription) {
                Log::error('CheckoutSuccess: subscription no encontrada.', [
                    'subscription_id' => $subscriptionId,
                ]);
                return false;
            }

            // Ya activa: nada que hacer (puede que el webhook llegara antes).
            if ($subscription->status === 'active') {
                return true;
            }

            $stripeSub = $session->subscription;
            if (! $stripeSub) {
                Log::warning('CheckoutSuccess: session sin subscription expandida.', [
                    'session_id' => $sessionId,
                ]);
                return false;
            }

            // Stripe puede devolver el objeto expandido o solo el ID.
            if (is_string($stripeSub)) {
                $stripeSub = $stripe->subscriptions->retrieve($stripeSub);
            }

            $stripeSubscriptionId = $stripeSub->id;

            // Idempotencia: el webhook ya lo procesó antes que nosotros.
            if ($subscription->stripe_subscription_id === $stripeSubscriptionId) {
                return true;
            }

            $periodStart   = isset($stripeSub->current_period_start)
                ? Carbon::createFromTimestamp($stripeSub->current_period_start) : null;
            $periodEnd     = isset($stripeSub->current_period_end)
                ? Carbon::createFromTimestamp($stripeSub->current_period_end) : null;
            $stripePriceId = $stripeSub->items->data[0]->price->id ?? null;

            $subscription->update([
                'status'                  => 'active',
                'stripe_subscription_id'  => $stripeSubscriptionId,
                'stripe_price_id'         => $stripePriceId,
                'plan_code'               => $planCode       ?? $subscription->plan_code,
                'billing_interval'        => $billingInterval ?? $subscription->billing_interval,
                'current_period_start_at' => $periodStart,
                'current_period_end_at'   => $periodEnd,
                'grace_period_ends_at'    => null,
                'canceled_at'             => null,
            ]);

            // Limpiar el cache fallback: la suscripción ya está activa en BD.
            cache()->forget("checkout_just_paid_{$subscription->account_id}");

            Log::info('CheckoutSuccess: suscripción activada directamente desde session_id.', [
                'subscription_id'        => $subscription->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'plan_code'              => $planCode,
            ]);

            // Email de confirmación: se envía aquí (no en el webhook) para que llegue de inmediato.
            // El webhook es idempotente — al ver stripe_subscription_id ya guardado, no reenvía.
            try {
                $users = $subscription->account?->users ?? collect();
                foreach ($users as $user) {
                    Mail::to($user->email)->send(new SubscriptionActivated($user, $subscription));
                }
            } catch (\Throwable $e) {
                Log::error('CheckoutSuccess: fallo al enviar email de activación — ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                ]);
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('CheckoutSuccess: activación directa fallida — ' . $e->getMessage(), [
                'session_id' => $sessionId,
            ]);
            return false;
        }
    }

    public function cancel(Request $request)
    {
        return redirect()->route('subscription.expired')
            ->with('status', 'Has cancelado el proceso de pago. Puedes intentarlo de nuevo cuando quieras.');
    }
}
