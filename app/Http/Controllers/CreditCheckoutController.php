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
            return back()->withErrors(['checkout' => 'Tu cuenta no está configurada. Contacta a soporte.']);
        }

        $restaurant = $account->restaurants()->first();
        if (! $restaurant) {
            return back()->withErrors(['checkout' => 'No se encontró un restaurante asociado. Contacta a soporte.']);
        }

        $stripeSecret = config('stripe.secret');
        if (! $stripeSecret) {
            return back()->withErrors(['checkout' => 'El sistema de pagos no está disponible. Contacta a soporte.']);
        }

        $stripe = new StripeClient($stripeSecret);

        // Get or reuse existing Stripe customer from subscription.
        $subscription    = $account->subscriptions()->latest()->first();
        $stripeCustomerId = $subscription?->stripe_customer_id ?? null;

        try {
            if (! $stripeCustomerId) {
                $customer = $stripe->customers->create([
                    'email'             => $user->email,
                    'name'              => $account->name,
                    'preferred_locales' => ['es'],
                    'metadata'          => ['account_id' => (string) $account->id],
                ]);
                $stripeCustomerId = $customer->id;
            }

            $session = $stripe->checkout->sessions->create([
                'customer'    => $stripeCustomerId,
                'mode'        => 'payment',
                'line_items'  => [[
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
                'automatic_tax'              => ['enabled' => true],
                'billing_address_collection' => 'required',
                'tax_id_collection'          => ['enabled' => true],
                'customer_update'            => ['address' => 'auto'],
                'invoice_creation'           => ['enabled' => true],
                'success_url' => route('credits.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('settings.ai-billing'),
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('CreditCheckout: Stripe session creation failed — ' . $e->getMessage(), [
                'account_id'  => $account->id,
                'package_key' => $packageKey,
            ]);
            return back()->withErrors(['checkout' => 'No se pudo iniciar el pago. Inténtalo de nuevo o contacta a soporte.']);
        }

        return redirect($session->url, 303);
    }

    public function success(Request $request)
    {
        return view('checkout.credits-success');
    }
}
