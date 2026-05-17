<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPublicMenuSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $restaurant = app()->bound('restaurant') ? app('restaurant') : null;

        if (! $restaurant) {
            return $next($request);
        }

        // Carta de ventas / ferias: el restaurante demo no debe depender de Stripe.
        if ($restaurant->ai_demo_unlimited) {
            return $next($request);
        }

        $account      = $restaurant->account;
        $subscription = $account
            ? $account->subscriptions()
                ->orderByDesc('id')
                ->get()
                ->first(static fn ($s) => $s->isActive())
            : null;

        if (! $subscription) {
            if ($request->expectsJson()) {
                return response()->json(
                    ['message' => 'This restaurant\'s subscription is inactive. Orders are not accepted.'],
                    402,
                    ['Cache-Control' => 'no-store, no-cache']
                );
            }

            return response(
                view('subscription.public-expired', compact('restaurant')),
                402,
                ['Cache-Control' => 'no-store, no-cache']
            );
        }

        return $next($request);
    }
}
