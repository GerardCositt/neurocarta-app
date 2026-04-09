<?php

namespace App\Http\Livewire;

use App\Models\Restaurant;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Component;

class NavigationMenu extends Component
{
    public ?string $adminLogoPath = null;

    public string $qrMenuUrl = '';
    public string $qrFilename = 'qr-carta.png';

    public function mount(): void
    {
        // Preferimos sesión (selector del admin). Si por cualquier motivo no persiste,
        // caemos al cookie que ya setea el selector para evitar que el QR apunte al restaurante por defecto.
        $restaurantId = session('admin_restaurant_id');
        if (! $restaurantId) {
            $cookieId = (int) request()->cookie('preview_restaurant_id');
            if ($cookieId > 0) {
                $restaurantId = $cookieId;
                session(['admin_restaurant_id' => $restaurantId]);
            }
        }

        $this->adminLogoPath = Setting::get('admin_logo_path', null, $restaurantId);

        // Solo el restaurante del selector del admin (no app('restaurant') del middleware, que puede ser otro).
        $restaurant = $restaurantId ? Restaurant::find($restaurantId) : null;
        if (! $restaurant) {
            $restaurant = Restaurant::first();
        }

        $this->qrFilename = $restaurant
            ? 'qr-' . Str::slug($restaurant->name) . '.png'
            : 'qr-carta.png';

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $appRunsOnLoopbackOrIp = $appHost === '127.0.0.1'
            || $appHost === 'localhost'
            || $appHost === '[::1]'
            || filter_var($appHost, FILTER_VALIDATE_IP) !== false;

        // Imprescindible: mirar el host de ESTA petición (panel abierto en 127.0.0.1), no solo APP_URL.
        // Muchos .env llevan APP_URL=https://dominio.real… con APP_ENV=production; si no, "Ver carta"
        // pasaba a https://subdominio.dominio (sin ?restaurant=) y la carta local no coincidía.
        $adminHost                = request()->getHost();
        $adminIsLocalhostOrIp      = filter_var($adminHost, FILTER_VALIDATE_IP) !== false
            || $adminHost === 'localhost'
            || Str::endsWith($adminHost, '.localhost');

        if ($adminIsLocalhostOrIp) {
            $this->qrMenuUrl = $this->publicMenuUrlWithRestaurantQuery($restaurant);

            return;
        }

        // En el admin, si hay restaurante seleccionado, el QR debe apuntar SIEMPRE a ese restaurante.
        // Usamos ?restaurant= para que en dominios compartidos (demo/staging) no acabe abriendo el restaurante por defecto.
        if ($restaurantId && $restaurant) {
            $this->qrMenuUrl = $this->publicMenuUrlWithRestaurantQuery($restaurant);

            return;
        }

        // Panel servido en dominio real: enlace público por subdominio (sin query).
        $useSubdomainPublicUrl = $restaurant
            && ! empty($restaurant->subdomain)
            && app()->environment('production')
            && ! $appRunsOnLoopbackOrIp;

        if ($useSubdomainPublicUrl) {
            $baseDomain        = config('app.base_domain', 'marisqueriabarjaen.com');
            $this->qrMenuUrl = 'https://' . $restaurant->subdomain . '.' . $baseDomain;

            return;
        }

        $this->qrMenuUrl = $this->publicMenuUrlWithRestaurantQuery($restaurant);
    }

    /**
     * Misma app que el admin: raíz de la petición actual + ?restaurant= (no url('/') con APP_URL distinto al Host).
     */
    private function publicMenuUrlWithRestaurantQuery(?Restaurant $restaurant): string
    {
        $root = rtrim(request()->root(), '/');
        if (! $restaurant) {
            return $root.'/';
        }

        $q = http_build_query(['restaurant' => $restaurant->id]);

        return $root.'/?'.$q;
    }

    public function render()
    {
        return view('navigation-menu', [
            'adminLogoPath' => $this->adminLogoPath,
            'qrMenuUrl' => $this->qrMenuUrl,
            'qrFilename' => $this->qrFilename,
        ]);
    }
}

