<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Pairing;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Translation;
use App\Services\DeepLService;
use App\Services\MenuBrandPaletteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Category|null $category
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $restaurant = app('restaurant');

        // IP / localhost / Codespaces: URL canónica con ?restaurant= para que coincida con el panel y sea compartible.
        if (! app()->runningUnitTests()
            && $request->isMethod('GET')
            && ! $request->filled('restaurant')
            && $this->localPreviewHostAllowsRestaurantQuery($request)) {
            return redirect()->route('menu', ['restaurant' => $restaurant->id]);
        }

        // ── Locale ──────────────────────────────────────────────
        $locale = $this->resolveLocale($request);
        session(['locale' => $locale]);
        App::setLocale($this->appLocaleForUi($locale));

        $ordersMode = (string) Setting::get('orders_mode', 'list', $restaurant->id);
        if (! in_array($ordersMode, ['order', 'list'], true)) {
            $ordersMode = 'list';
        }

        $advices    = Advice::query()
            ->visibleNow()
            ->where('restaurant_id', $restaurant->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $showAlerts = $advices->count() > 0 ? 1 : 0;

        $categories = Category::visible()
            ->where('restaurant_id', $restaurant->id)
            ->with(['translations', 'products' => function ($query) use ($restaurant) {
                $query->visible()
                    ->where('restaurant_id', $restaurant->id)
                    ->orderForMenu()
                    ->with(['visibleAllergens.translations', 'pairing.translations', 'translations']);
            }])
            ->get();

        $offers = Product::visible()
            ->where('restaurant_id', $restaurant->id)
            ->withActiveOffer()
            ->with(['visibleAllergens.translations', 'pairing.translations', 'translations'])
            ->orderForMenu()
            ->get();

        $productsData = $categories->flatMap(function ($cat) { return $cat->products; })
            ->merge($offers)
            ->unique('id')
            ->keyBy('id')
            ->map(function ($p) use ($locale) {
                $allergenList = $p->visibleAllergens
                    ->sortBy(function ($a) {
                        return $a->sort_order ?? 0;
                    })
                    ->values()
                    ->map(function ($a) use ($locale) {
                        return [
                            'name'      => $a->translate($locale, 'name'),
                            'image'     => $a->image,
                            'image_url' => $a->image_url,
                        ];
                    })->values();

                $pairing = optional($p->pairing);

                return [
                    'id'            => $p->id,
                    'category_id'   => $p->category_id,
                    'sort_order'    => (int) $p->order,
                    'name'          => $p->translate($locale, 'name'),
                    'description'   => $p->translate($locale, 'description'),
                    'price'         => $p->price,
                    'offer_price'   => $p->offer_price,
                    'offer'         => $p->isOfferActive(),
                    'offer_badge'   => $p->offer_badge ?? __('public_menu.offer_default'),
                    'featured'      => (bool) $p->featured,
                    'recommended'   => (bool) $p->recommended,
                    'photo'         => $p->photo,
                    'aller'         => $p->aller,
                    'allergens'     => $allergenList,
                    'pairing'       => $pairing->id ? $pairing->translate($locale, 'description') : null,
                    // Traducciones completas para el selector de idioma en JS
                    'translations'  => $p->getAllTranslations(),
                ];
            });

        // Categorías con nombre traducido para el menú de navegación
        $categoriesData = $categories->map(function ($cat) use ($locale) {
            return [
                'id'   => $cat->id,
                'name' => $cat->translate($locale, 'name'),
            ];
        });

        $availableLocales = $this->getAvailableLocales();

        $categoryOrderIds = $categories->pluck('id')->all();

        $menuBrandPalette = $this->resolveMenuBrandPalette((int) $restaurant->id);

        return view('menu', compact(
            'categories', 'categoriesData', 'categoryOrderIds', 'offers', 'showAlerts', 'advices',
            'productsData', 'ordersMode', 'restaurant',
            'locale', 'availableLocales', 'menuBrandPalette'
        ))->with('publicMenuStrings', trans('public_menu'));
    }

    /**
     * Paleta CSS derivada del logo (ajuste apariencia). Si no hay JSON guardado pero sí logo raster,
     * extrae una vez y persiste.
     *
     * @return array<string, mixed>|null
     */
    private function resolveMenuBrandPalette(int $restaurantId): ?array
    {
        $raw = Setting::get(MenuBrandPaletteService::settingKey(), '', $restaurantId);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && ! empty($decoded['accent_hex']) && is_string($decoded['accent_hex'])) {
                $rebuilt = app(MenuBrandPaletteService::class)->paletteFromAccentHex($decoded['accent_hex']);
                if ($rebuilt !== null) {
                    return $rebuilt;
                }
            }
            if (is_array($decoded) && ! empty($decoded['vars_dark']) && ! empty($decoded['vars_light'])) {
                return app(MenuBrandPaletteService::class)->refreshAccentForegrounds($decoded);
            }
        }

        $logo = Setting::get('admin_logo_path', '', $restaurantId);
        if (! is_string($logo) || $logo === '') {
            return null;
        }

        if (strtolower((string) pathinfo($logo, PATHINFO_EXTENSION)) === 'svg') {
            return null;
        }

        $extracted = app(MenuBrandPaletteService::class)->extractFromStoragePublicPath($logo);
        if ($extracted) {
            Setting::put(MenuBrandPaletteService::settingKey(), json_encode($extracted), $restaurantId);
        }

        return $extracted;
    }

    /**
     * Determina el locale activo:
     * 1. Parámetro ?lang= en la URL
     * 2. Cookie / sesión previa
     * 3. Cabecera Accept-Language del navegador
     * 4. Fallback: 'es'
     */
    private function resolveLocale(Request $request): string
    {
        $supported = $this->getAvailableLocales();

        if ($request->has('lang')) {
            $picked = $this->pickLocaleFromSupported($request->query('lang'), $supported);
            if ($picked !== null) {
                return $picked;
            }
        }

        if ($request->hasCookie('locale')) {
            $picked = $this->pickLocaleFromSupported($request->cookie('locale'), $supported);
            if ($picked !== null) {
                return $picked;
            }
        }

        if (session()->has('locale')) {
            $picked = $this->pickLocaleFromSupported(session('locale'), $supported);
            if ($picked !== null) {
                return $picked;
            }
        }

        // Browser detection
        $acceptLang = $request->header('Accept-Language', 'es');
        $primary    = trim(explode(',', $acceptLang)[0]);
        $primary    = strtolower(str_replace('_', '-', $primary));
        $short      = explode('-', $primary)[0];
        $picked     = $this->pickLocaleFromSupported($primary, $supported);
        if ($picked !== null) {
            return $picked;
        }
        $picked = $this->pickLocaleFromSupported($short, $supported);
        if ($picked !== null) {
            return $picked;
        }

        return 'es';
    }

    /**
     * Coincide ?lang= / cookie con los locales de BD (pt_BR ≠ pt_br roto por strtolower).
     */
    private function pickLocaleFromSupported($value, array $supported): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $candidate = is_string($value) ? trim($value) : (string) $value;
        foreach ($supported as $s) {
            if (strcasecmp($candidate, $s) === 0) {
                return $s;
            }
        }
        $norm = strtolower(str_replace('-', '_', $candidate));
        foreach ($supported as $s) {
            if (strtolower(str_replace('-', '_', $s)) === $norm) {
                return $s;
            }
        }

        return null;
    }

    /**
     * Locales con al menos una traducción en productos, categorías, avisos, maridajes o alérgenos + español base.
     */
    private function getAvailableLocales(): array
    {
        $restaurant = app('restaurant');
        $rid = $restaurant->id;

        $locales = [];

        $mergeLocales = function ($class, $ids) use (&$locales) {
            if ($ids->isEmpty()) {
                return;
            }
            $l = Translation::query()
                ->where('translatable_type', $class)
                ->whereIn('translatable_id', $ids)
                ->distinct()
                ->pluck('locale')
                ->all();
            $locales = array_merge($locales, $l);
        };

        $mergeLocales(Product::class, Product::where('restaurant_id', $rid)->pluck('id'));
        $mergeLocales(Category::class, Category::where('restaurant_id', $rid)->pluck('id'));
        $mergeLocales(Advice::class, Advice::where('restaurant_id', $rid)->pluck('id'));
        $mergeLocales(Pairing::class, Pairing::where('restaurant_id', $rid)->pluck('id'));
        $mergeLocales(Allergen::class, Allergen::query()->pluck('id'));

        return array_values(array_unique(array_merge(['es'], $locales)));
    }

    /**
     * Carpeta resources/lang para __('public_menu'): alinear con el idioma de la carta si hay fichero,
     * si no usar fallback_locale (en) en lugar de es para no mezclar platos en EN con UI en ES.
     */
    private function appLocaleForUi(string $locale): string
    {
        $map = [
            'pt_BR' => 'pt',
            'en-GB' => 'en',
            'en_US' => 'en',
        ];
        $candidate = $map[$locale] ?? $locale;
        $base        = strtolower(explode('-', str_replace('_', '-', $candidate))[0] ?? $candidate);

        $try = array_values(array_unique(array_filter([$candidate, $base])));
        foreach ($try as $dir) {
            $path = resource_path('lang/'.$dir.'/public_menu.php');
            if (is_file($path)) {
                return $dir;
            }
        }

        return (string) config('app.fallback_locale', 'en');
    }

    /**
     * Cambia el idioma y redirige a la carta.
     */
    public function setLocale(Request $request, string $locale)
    {
        $available = $this->getAvailableLocales();
        $picked = $this->pickLocaleFromSupported($locale, $available);
        $locale = $picked ?? 'es';
        session(['locale' => $locale]);
        $restaurant = app('restaurant');

        return redirect()->route('menu', ['restaurant' => $restaurant->id])
            ->withCookie(cookie()->forever('locale', $locale));
    }

    /**
     * Mismo criterio que DetectRestaurant sin subdominio: previsualización con ?restaurant=.
     */
    private function localPreviewHostAllowsRestaurantQuery(Request $request): bool
    {
        $host = $request->getHost();

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if ($host === 'localhost' || Str::endsWith($host, '.localhost')) {
            return true;
        }

        if (Str::endsWith($host, '.app.github.dev')) {
            return true;
        }

        return false;
    }

    public function offer()
    {

        $products = Product::where('active', false)->where('offer', true)->get();

        return view('menulist', compact('products'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
