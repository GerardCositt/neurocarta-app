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

        $isAdmin = $user && $user->is_admin;

        // Validar que el restaurante en sesión pertenece a la cuenta del usuario (no aplica a admin)
        if ($restaurantId && $account && ! $isAdmin) {
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

            // Admin puede gestionar cualquier restaurante (p. ej. NeuroCarta Demo): cuenta = la del local
            if ($isAdmin) {
                $resolvedAccount = app(PlanEntitlementService::class)->accountForRestaurant($restaurant);
                if ($resolvedAccount) {
                    app()->instance('account', $resolvedAccount);
                }
            } elseif ($account) {
                app()->instance('account', $account);
            } else {
                $resolvedAccount = app(PlanEntitlementService::class)->accountForRestaurant($restaurant);
                if ($resolvedAccount) {
                    app()->instance('account', $resolvedAccount);
                }
            }
        }

        return $next($request);
    }
}
