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
        $restaurantId = session('admin_restaurant_id');

        if (! $restaurantId) {
            $restaurant = Restaurant::first();
            if ($restaurant) {
                session(['admin_restaurant_id' => $restaurant->id]);
                $restaurantId = $restaurant->id;
            }
        }

        $restaurant = $restaurantId ? Restaurant::find($restaurantId) : null;

        if (! $restaurant) {
            $restaurant = Restaurant::first();
            if ($restaurant) {
                session(['admin_restaurant_id' => $restaurant->id]);
            }
        }

        if ($restaurant) {
            app()->instance('restaurant', $restaurant);

            // Resolver cuenta a partir del restaurante seleccionado (multi-restaurante por cuenta).
            $account = app(PlanEntitlementService::class)->accountForRestaurant($restaurant);
            if ($account) {
                app()->instance('account', $account);
            }
        }

        return $next($request);
    }
}
