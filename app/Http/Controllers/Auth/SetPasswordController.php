<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Stripe\StripeClient;

class SetPasswordController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403, 'El enlace ha caducado o no es válido.');

        $token      = Password::createToken($user);
        $formAction = route('set-password.store');

        return view('auth.set-password', [
            'token'      => $token,
            'email'      => $user->email,
            'formAction' => $formAction,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $activatedUser = null;

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$activatedUser) {
                $user->forceFill([
                    'password'          => Hash::make($password),
                    'email_verified_at' => now(),
                ])->save();
                $activatedUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET || ! $activatedUser) {
            return back()->withErrors(['email' => __($status)]);
        }

        Auth::login($activatedUser);
        $request->session()->regenerate();

        $account      = $activatedUser->accounts()->first();
        $subscription = $account?->subscriptions()->latest()->first();

        if ($subscription && $subscription->status === 'inactive' && $subscription->plan_code !== 'trial') {
            $stripeUrl = $this->createStripeCheckoutUrl(
                $activatedUser,
                $account,
                $subscription,
                $subscription->plan_code,
                $subscription->billing_interval ?? 'monthly'
            );

            if ($stripeUrl) {
                return redirect($stripeUrl, 303);
            }
        }

        return redirect()->route('product');
    }

    private function createStripeCheckoutUrl($user, $account, $subscription, string $plan, string $interval): ?string
    {
        $stripeSecret = config('stripe.secret');
        $priceId      = config("stripe.prices.{$plan}.{$interval}");

        if (! $stripeSecret || ! $priceId) {
            Log::error("SetPassword checkout: missing Stripe config plan={$plan} interval={$interval}");
            return null;
        }

        try {
            $stripe = new StripeClient($stripeSecret);

            $stripeCustomerId = DB::transaction(function () use ($stripe, $subscription, $account, $user) {
                $locked = \App\Models\Subscription::where('id', $subscription->id)->lockForUpdate()->first();

                if ($locked->stripe_customer_id) {
                    try {
                        $stripe->customers->retrieve($locked->stripe_customer_id);
                        return $locked->stripe_customer_id;
                    } catch (\Stripe\Exception\InvalidRequestException $e) {
                        $locked->update(['stripe_customer_id' => null]);
                    }
                }

                $customer = $stripe->customers->create([
                    'email'             => $user->email,
                    'name'              => $account->name,
                    'preferred_locales' => ['es'],
                    'metadata'          => ['account_id' => (string) $account->id],
                ]);

                $locked->update(['stripe_customer_id' => $customer->id]);
                return $customer->id;
            });

            $session = $stripe->checkout->sessions->create([
                'customer'             => $stripeCustomerId,
                'mode'                 => 'subscription',
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price'    => $priceId,
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'account_id'       => (string) $account->id,
                    'subscription_id'  => (string) $subscription->id,
                    'plan_code'        => $plan,
                    'billing_interval' => $interval,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'account_id'      => (string) $account->id,
                        'subscription_id' => (string) $subscription->id,
                    ],
                ],
                'success_url'           => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'            => route('checkout.cancel'),
                'allow_promotion_codes' => true,
            ]);

            return $session->url;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('SetPassword Stripe checkout failed: ' . $e->getMessage());
            return null;
        }
    }
}
