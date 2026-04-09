<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;

class EnsureOrdersEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $restaurantId = app()->bound('restaurant') ? app('restaurant')?->id : null;
        $mode = (string) Setting::get('orders_mode', 'list', $restaurantId);
        if ($mode !== 'order') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Modo pedido desactivado.'], 403);
            }
            abort(404);
        }
        return $next($request);
    }
}
