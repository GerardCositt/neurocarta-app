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
        if ($user && $user->is_admin) {
            return $next($request);
        }

        // Obtener la cuenta resuelta por AdminRestaurant middleware
        $account = app()->bound('account') ? app('account') : null;

        if (! $account) {
            return $next($request);
        }

        $subscription = $account->subscriptions()->latest()->first();

        // Sin suscripción o con trial vencido / estado inactivo → pantalla de bloqueo
        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
