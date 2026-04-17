<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DetectRestaurant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Demo / staging: permitir forzar restaurante por query (?restaurant=ID) incluso con subdominio.
        // Útil para demos multi-restaurante en un solo dominio (p. ej. v2-carta...) desde el panel admin.
        if (! app()->environment('production')) {
            $forcedId = $request->query('restaurant');
            if ($forcedId !== null && $forcedId !== '' && is_numeric($forcedId)) {
                $forced = Restaurant::find((int) $forcedId);
                if ($forced) {
                    app()->instance('restaurant', $forced);
                    $request->merge(['restaurant' => $forced]);

                    return $next($request);
                }
            }
        }

        // GitHub Codespaces: el host NO es carta.dominio.com (da 404 si mezclamos con lógica de subdominio).
        // Debe funcionar aunque APP_ENV no sea "local" en el Codespace.
        if (Str::endsWith($host, '.app.github.dev')) {
            $restaurant = $this->resolveRestaurantWithoutSubdomain($request);
            if (! $restaurant) {
                abort(404, 'Restaurante no encontrado. Ejecuta en el Codespace: php artisan db:seed --class=RestaurantSeeder');
            }
            app()->instance('restaurant', $restaurant);
            $request->merge(['restaurant' => $restaurant]);

            return $next($request);
        }

        // Acceso por IP literal (127.0.0.1, ::1): no hay subdominio. Si no salimos aquí,
        // explode('.') de "127.0.0.1" da 4 partes y se usa subdomain "127" → 404 en BD.
        // Tampoco exigimos APP_ENV=local (muchos entornos usan staging en dev).
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $restaurant = $this->resolveRestaurantWithoutSubdomain($request);
            if (! $restaurant && app()->runningUnitTests()) {
                $restaurant = new Restaurant([
                    'id' => 0,
                    'name' => 'Testing',
                    'subdomain' => 'testing',
                ]);
            }
            if (! $restaurant) {
                abort(404, 'Restaurante no encontrado.');
            }
            app()->instance('restaurant', $restaurant);
            $request->merge(['restaurant' => $restaurant]);

            return $next($request);
        }

        // En local, si accedemos por localhost no existe subdominio real.
        if ((app()->environment('local') || app()->runningUnitTests())
            && $host === 'localhost') {
            $restaurant = $this->resolveRestaurantWithoutSubdomain($request);
            if (! $restaurant && app()->runningUnitTests()) {
                $restaurant = new Restaurant([
                    'id' => 0,
                    'name' => 'Testing',
                    'subdomain' => 'testing',
                ]);
            }
            if (! $restaurant) {
                abort(404, 'Restaurante no encontrado.');
            }
            app()->instance('restaurant', $restaurant);
            $request->merge(['restaurant' => $restaurant]);
            return $next($request);
        }

        // Extract subdomain: e.g. "carta" from "carta.marisqueriabarjaen.com"
        $parts = explode('.', $host);
        $subdomain = count($parts) >= 3 ? $parts[0] : null;

        if (! $subdomain) {
            // En local (localhost/127.0.0.1) no hay subdominio: usamos un fallback para poder previsualizar la carta.
            if (app()->environment('local')) {
                $restaurant = $this->resolveRestaurantWithoutSubdomain($request);
                if (! $restaurant && app()->runningUnitTests()) {
                    $restaurant = new Restaurant([
                        'id' => 0,
                        'name' => 'Testing',
                        'subdomain' => 'testing',
                    ]);
                }
                if (! $restaurant) {
                    abort(404, 'Restaurante no encontrado.');
                }
                app()->instance('restaurant', $restaurant);
                $request->merge(['restaurant' => $restaurant]);
                return $next($request);
            }

            abort(404, 'Restaurante no encontrado.');
        }

        // Staging: permitir prefijo "v2-" para probar sin tocar producción.
        // Primero intentamos el subdominio completo (ej: "v2-carta") por si existe como restaurante real en demo.
        // Si no existe, hacemos fallback quitando el prefijo "v2-" (ej: "carta").
        $restaurant = Restaurant::where('subdomain', $subdomain)->first();

        if (! $restaurant && Str::startsWith($subdomain, 'v2-')) {
            $fallback = substr($subdomain, 3);
            $restaurant = Restaurant::where('subdomain', $fallback)->first();
        }

        if (! $restaurant) {
            abort(404, 'Restaurante no encontrado.');
        }

        app()->instance('restaurant', $restaurant);
        $request->merge(['restaurant' => $restaurant]);

        return $next($request);
    }

    /**
     * Sin subdominio (local, IP, Codespaces): ?restaurant=ID elige el local activo en el panel.
     * En producción no se usa (evita enumerar IDs).
     */
    private function resolveRestaurantWithoutSubdomain(Request $request): ?Restaurant
    {
        $id = $this->previewRestaurantId($request);
        if ($id !== null) {
            $r = Restaurant::find($id);
            if ($r) {
                return $r;
            }
        }

        // Mismo local que tienes seleccionado en el panel (preview por IP / localhost).
        // Sin esto, la carta usa Restaurant::first() y los cambios de logo/paleta parecen "no aplicarse".
        $sessionRid = session('admin_restaurant_id');
        if ($sessionRid !== null && $sessionRid !== '' && is_numeric($sessionRid)) {
            $r = Restaurant::find((int) $sessionRid);
            if ($r) {
                return $r;
            }
        }

        $cookieId = $request->cookie('preview_restaurant_id');
        if ($cookieId !== null && $cookieId !== '' && is_numeric($cookieId)) {
            $r = Restaurant::find((int) $cookieId);
            if ($r) {
                return $r;
            }
        }

        return null;
    }

    private function previewRestaurantId(Request $request): ?int
    {
        $host = $request->getHost();

        // En producción real el host es subdominio.dominio; aquí no hay riesgo de enumeración.
        // Si APP_ENV=production pero accedes por 127.0.0.1/localhost (dev con .env mal copiado),
        // antes se ignoraba ?restaurant= y siempre salía Restaurant::first() (p. ej. Bar Jaén III).
        $allowPreviewQuery = ! app()->environment('production')
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || $host === 'localhost'
            || Str::endsWith($host, '.localhost')
            || Str::endsWith($host, '.app.github.dev');

        if (! $allowPreviewQuery) {
            return null;
        }

        $id = $request->query('restaurant');
        if ($id === null || $id === '') {
            return null;
        }

        if (! is_numeric($id)) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
    }
}
