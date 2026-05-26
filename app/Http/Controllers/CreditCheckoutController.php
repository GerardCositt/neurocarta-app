<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class CreditCheckoutController extends Controller
{
    private const PACKAGES = [
        'starter' => ['credits' => 300,  'amount_cents' => 500,  'label' => 'Pack Starter — 300 créditos IA'],
        'pro'     => ['credits' => 1000, 'amount_cents' => 1500, 'label' => 'Pack Pro — 1.000 créditos IA'],
        'max'     => ['credits' => 3000, 'amount_cents' => 3900, 'label' => 'Pack Max — 3.000 créditos IA'],
    ];

    public function createFromGet(Request $request)
    {
        $request->merge(['package' => $request->query('package')]);
        return $this->create($request);
    }

    public function create(Request $request)
    {
        $request->validate([
            'package' => ['required', 'in:starter,pro,max'],
        ]);

        $packageKey = $request->input('package');
        $package    = self::PACKAGES[$packageKey];

        $user    = $request->user();
        $account = $user->accounts()->first();

        if (! $account) {
            return redirect(route('settings.ai-billing'))->with('credit_error', 'Tu cuenta no está configurada. Contacta a soporte.');
        }

        $restaurant = $account->restaurants()->first();
        if (! $restaurant) {
            return redirect(route('settings.ai-billing'))->with('credit_error', 'No se encontró un restaurante asociado. Contacta a soporte.');
        }

        $stripeSecret = config('stripe.secret');
        if (! $stripeSecret) {
            return redirect(route('settings.ai-billing'))->with('credit_error', 'El sistema de pagos no está disponible. Contacta a soporte.');
        }

        $stripe = new StripeClient($stripeSecret);

        // Get or reuse existing Stripe customer from subscription.
        $subscription    = $account->subscriptions()->latest()->first();
        $stripeCustomerId = $subscription?->stripe_customer_id ?? null;

        try {
            // Verify the stored customer is usable in this Stripe mode.
            // Deleted customers return { deleted: true } without throwing — check explicitly.
            if ($stripeCustomerId) {
                try {
                    $existing = $stripe->customers->retrieve($stripeCustomerId);
                    if (! empty($existing->deleted)) {
                        $stripeCustomerId = null;
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    // Customer not found or not accessible in this mode — create a fresh one.
                    $stripeCustomerId = null;
                }
            }

            if (! $stripeCustomerId) {
                $customer = $stripe->customers->create([
                    'email'             => $user->email,
                    'name'              => $account->name,
                    'preferred_locales' => ['es'],
                    'metadata'          => ['account_id' => (string) $account->id],
                ]);
                $stripeCustomerId = $customer->id;

                // Persist so future sessions reuse this live customer.
                $subscription?->update(['stripe_customer_id' => $stripeCustomerId]);
            }

            $session = $stripe->checkout->sessions->create([
                'customer'             => $stripeCustomerId,
                'mode'                 => 'payment',
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => 'eur',
                        'unit_amount'  => $package['amount_cents'],
                        'product_data' => [
                            'name'        => $package['label'],
                            'description' => 'Créditos IA para NeuroCarta.ai®',
                        ],
                    ],
                ]],
                'metadata' => [
                    'type'          => 'credit_purchase',
                    'account_id'    => (string) $account->id,
                    'restaurant_id' => (string) $restaurant->id,
                    'package_key'   => $packageKey,
                    'credits'       => (string) $package['credits'],
                ],
                'invoice_creation' => ['enabled' => true],
                'success_url'      => route('credits.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'       => route('settings.ai-billing'),
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('CreditCheckout: Stripe session creation failed — ' . $e->getMessage(), [
                'account_id'  => $account->id,
                'package_key' => $packageKey,
                'stripe_code' => $e->getStripeCode(),
            ]);
            return redirect(route('settings.ai-billing'))->with('credit_error', 'Error al crear sesión de pago: ' . $e->getMessage());
        }

        return redirect($session->url, 303);
    }

    public function success(Request $request)
    {
        return view('checkout.credits-success');
    }
}
