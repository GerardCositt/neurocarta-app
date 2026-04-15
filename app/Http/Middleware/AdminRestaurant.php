<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\PlanEntitlementService;
use Closure;
use Illuminate\Http\Request;

class AdminRestaurant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Obtener la cuenta del usuario autenticado
        $account = $user ? $user->accounts()->first() : null;

        $restaurantId = session('admin_restaurant_id');

        // Validar que el restaurante en sesión pertenece a la cuenta del usuario
        if ($restaurantId && $account) {
            $belongs = $account->restaurants()->where('id', $restaurantId)->exists();
            if (! $belongs) {
                $restaurantId = null;
                session()->forget('admin_restaurant_id');
            }
        }

        // Si no hay restaurante en sesión (o era inválido), cargar el primero de la cuenta
        if (! $restaurantId) {
            $restaurant = $account ? $account->restaurants()->first() : null;
            if ($restaurant) {
                session(['admin_restaurant_id' => $restaurant->id]);
                $restaurantId = $restaurant->id;
            }
        }

        $restaurant = $restaurantId ? Restaurant::find($restaurantId) : null;

        if ($restaurant) {
            app()->instance('restaurant', $restaurant);

            if ($account) {
                app()->instance('account', $account);
            } else {
                // Fallback: resolver cuenta a partir del restaurante
                $resolvedAccount = app(PlanEntitlementService::class)->accountForRestaurant($restaurant);
                if ($resolvedAccount) {
                    app()->instance('account', $resolvedAccount);
                }
            }
        }

        return $next($request);
    }
}
