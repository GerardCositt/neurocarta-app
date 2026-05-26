<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectNonAdminFromPanel
{
    public function handle(Request $request, Closure $next)
    {
        // Skip Filament auth pages to prevent redirect loops
        if ($request->is('admin/login*') || $request->is('admin/logout*') || $request->is('admin/password-reset*')) {
            return $next($request);
        }

        if (Auth::check() && ! Auth::user()->hasPanelAdminAccess()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.admin.auth.login')
                ->with('status', 'No tienes acceso al panel. Inicia sesión con tu cuenta de administrador.');
        }

        return $next($request);
    }
}
