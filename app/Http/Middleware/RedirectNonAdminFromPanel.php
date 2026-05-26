<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectNonAdminFromPanel
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->hasPanelAdminAccess()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login')
                ->with('status', 'Tu sesión ha expirado o no tienes acceso al panel. Inicia sesión de nuevo.');
        }

        return $next($request);
    }
}
