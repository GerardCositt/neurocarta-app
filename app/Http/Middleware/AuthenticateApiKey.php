<?php

namespace App\Http\Middleware;

use App\Models\RestaurantApiKey;
use App\Services\PlanEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if (! $raw) {
            return response()->json(['error' => 'API key requerida. Usa el header Authorization: Bearer <key>'], 401);
        }

        $apiKey = RestaurantApiKey::findByRawKey($raw);

        if (! $apiKey) {
            return response()->json(['error' => 'API key inválida o revocada'], 401);
        }

        $restaurant = $apiKey->restaurant;
        $account    = $restaurant->account;
        $sub        = $account?->subscriptions()->latest()->first();
        $plan       = $sub?->plan_code ?? '';

        $plansWithApi = ['pro', 'premium', 'trial'];
        if (! in_array($plan, $plansWithApi, true)) {
            return response()->json(['error' => 'El acceso a la API requiere plan Pro o Premium'], 403);
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('api_restaurant', $restaurant);

        return $next($request);
    }
}
