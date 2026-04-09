<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleFromAuthenticatedUser
{
    /**
     * Aplica el idioma del panel según el usuario autenticado (tras StartSession).
     * No afecta a la carta pública: su contenido usa su propia resolución de locale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $allowed = config('app.admin_locales', ['es', 'en']);
            $locale = $user->locale ?? 'es';
            if (! in_array($locale, $allowed, true)) {
                $locale = 'es';
            }
            App::setLocale($locale);
        }

        return $next($request);
    }
}
