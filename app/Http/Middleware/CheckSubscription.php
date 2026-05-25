<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Los admins siempre tienen acceso
        if ($user && $user->hasPanelAdminAccess()) {
            return $next($request);
        }

        // Obtener la cuenta resuelta por AdminRestaurant middleware
        $account = app()->bound('account') ? app('account') : null;

        if (! $account) {
            return redirect()->route('subscription.expired')
                ->with('error', 'Tu cuenta no está configurada correctamente. Contacta a soporte.');
        }

        $subscription = $account->subscriptions()->latest()->first();

        // Sin suscripción o con trial vencido / estado inactivo → pantalla de bloqueo
        // Excepción: si acaba de completar el checkout de Stripe (webhook puede tardar unos segundos)
        $checkoutTs = session('checkout_just_completed');
        $justPaid   = $checkoutTs && (now()->timestamp - $checkoutTs) < 300;

        if (! $subscription || ! $subscription->isActive()) {
            if ($justPaid) {
                return $next($request);
            }
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
