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
        if ($restaurant->isPublicSalesDemo()) {
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
            // Bypass de race condition: el usuario acaba de pagar pero el webhook de Stripe
            // aún no ha procesado y activado la suscripción. La sesión de app.neurocarta.ai
            // no es accesible desde subdominos de restaurante, por eso usamos caché del servidor.
            // CheckoutController::success() escribe esta clave; se limpia cuando el webhook activa.
            $justPaidCacheKey = $account ? "checkout_just_paid_{$account->id}" : null;
            if ($justPaidCacheKey && cache()->has($justPaidCacheKey)) {
                return $next($request);
            }

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
