<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Livewire\Pairings;
use App\Http\Livewire\Products;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\CheckPublicMenuSubscription;
use App\Http\Middleware\DetectRestaurant;
use App\Http\Controllers\Api\ReorderController;
use App\Http\Controllers\Api\StoreOrderController;
use App\Http\Controllers\UserLocaleController;
use App\Mail\WelcomeSetPassword;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Health check para Render
Route::get('/up', fn () => response('OK', 200));

// ─── Registro: selector de plan + formulario ───────────────────────────────
// Rutas estáticas ANTES del parámetro dinámico {plan}

// GET /register/check-email → pantalla "revisa tu correo"
Route::get('/register/check-email', function () {
    return view('auth.check-email');
})->middleware('guest')->name('register.check-email');

// GET /register/{plan} → formulario de registro con plan preseleccionado
Route::get('/register/{plan}', function (string $plan) {
    $validPlans = ['trial', 'basico', 'pro', 'premium'];
    if (! in_array($plan, $validPlans, true)) {
        return redirect()->route('register');
    }
    return view('auth.register', ['plan' => $plan]);
})->middleware(['guest', 'throttle:register'])->name('register.plan');

// POST /register/resend-activation → reenvío del email de activación (M2)
Route::post('/register/resend-activation', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);

    $user = User::where('email', $request->email)
                ->whereNull('email_verified_at')
                ->first();

    if ($user) {
        $setPasswordUrl = URL::temporarySignedRoute(
            'set-password.show',
            now()->addDays(3),
            ['user' => $user->id]
        );
        try {
            Mail::to($user->email)->send(new WelcomeSetPassword($user, $setPasswordUrl));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Resend activation mail failed: ' . $e->getMessage());
        }
    }

    // Siempre 200 para no exponer si el email existe
    return back()->with('status', 'Si el email existe y la cuenta no está activada, te hemos enviado el enlace.');
})->middleware(['guest', 'throttle:3,5'])->name('register.resend');

// ─── Crear contraseña (enlace del email) ───────────────────────────────────
Route::get('/set-password/{user}', [SetPasswordController::class, 'show'])
    ->middleware('guest')
    ->name('set-password.show');

Route::post('/set-password', [SetPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('set-password.store');

// Raíz: panel admin en hosts centrales; carta pública en subdominio de restaurante o preview local.
Route::get('/', function (\Illuminate\Http\Request $request) {
    $host = $request->getHost();

    $isDirectLocalAccess = filter_var($host, FILTER_VALIDATE_IP) !== false
        || $host === 'localhost';

    $isRestaurantSubdomain = false;
    if (! $isDirectLocalAccess) {
        $parts = explode('.', $host);
        $subdomain = count($parts) >= 3 ? $parts[0] : null;
        $isRestaurantSubdomain = $subdomain && ! in_array($subdomain, ['app', 'www'], true);
    }

    // Hosts centrales (app.neurocarta.ai / www.neurocarta.ai): siempre panel admin.
    // DetectRestaurant no puede resolver la carta desde estos hosts; cualquier preview
    // context aquí causaría un 404 al buscar subdomain 'app' en la BD.
    if (! $isDirectLocalAccess && ! $isRestaurantSubdomain) {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }

    // IP / localhost: solo ir a la carta si hay un contexto de preview que DetectRestaurant
    // puede resolver (session, cookie, o ?restaurant= numérico en entornos no-producción).
    if ($isDirectLocalAccess) {
        $resolvablePreview = session('admin_restaurant_id')
            || $request->cookie('preview_restaurant_id')
            || (! app()->isProduction()
                && is_numeric($request->query('restaurant'))
                && (int) $request->query('restaurant') > 0);

        if (! $resolvablePreview) {
            return auth()->check()
                ? redirect()->route('dashboard')
                : redirect()->route('login');
        }
    }

    return app(DetectRestaurant::class)->handle($request, function ($req) {
        return app(CheckPublicMenuSubscription::class)->handle($req, function ($req2) {
            return app(ProductController::class)->index($req2);
        });
    });
})->name('menu');

Route::post('/api/orders', StoreOrderController::class)
    ->middleware(['throttle:30,1', 'orders.enabled', 'detect.restaurant', 'subscription.public'])
    ->name('api.orders.store');

Route::middleware(['auth:sanctum', 'verified'])->post('/user/locale', UserLocaleController::class)
    ->name('user.locale');

// ─── Trial expirado ───────────────────────────────────────────────────────────
Route::get('/subscription/expired', function () {
    return view('subscription.expired');
})->middleware(['auth:sanctum', 'verified'])->name('subscription.expired');

// ─── Stripe Checkout ──────────────────────────────────────────────────────────
// No subscription.check — expired/inactive users must be able to reach checkout.
Route::middleware(['auth:sanctum', 'verified', 'throttle:10,1'])->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
});

// ─── Stripe Webhook ───────────────────────────────────────────────────────────
// No auth — Stripe signs the payload; signature is verified in the controller.
// Excluded from CSRF in VerifyCsrfToken::$except.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

Route::middleware(['auth:sanctum', 'verified', 'admin.restaurant', 'subscription.check'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/category', App\Http\Livewire\Category\Show::class)->name('category_list');

    Route::get('/allergen', App\Http\Livewire\Allergen\Show::class)->name('allergen_list');

    Route::get('/advice', App\Http\Livewire\Advices\Show::class)->name('advice');

    Route::get('/settings', function () {
        return redirect()->to('/settings/appearance');
    })->name('settings');

    Route::get('/settings/appearance', function () {
        return view('settings.appearance');
    })->name('settings.appearance');

    Route::get('/settings/orders', function () {
        return view('settings.orders');
    })->name('settings.orders');

    Route::get('/settings/ai-billing', function () {
        return view('settings.ai-billing');
    })->name('settings.ai-billing');

    Route::get('/settings/import-products', function () {
        return view('settings.import-products');
    })->name('settings.import-products');

    Route::get('/settings/import-ai', function () {
        return view('settings.import-ai');
    })->name('settings.import-ai');

    Route::get('/settings/import-products/template', function () {
        $headers = [
            'id',
            'nombre',
            'categoria',
            'descripcion',
            'precio',
            'oferta',
            'precio_oferta',
            'etiqueta_oferta',
            'inicio_oferta',
            'fin_oferta',
            'oculto',
            'destacado',
            'recomendado',
            'orden',
            'alergenos',
        ];

        $example = [
            '',
            'Ensalada Mixta',
            'Entrantes',
            'Lechuga, tomate, atún…',
            '9,50€',
            '0',
            '',
            'Oferta',
            '',
            '',
            '0',
            '0',
            '0',
            '',
            'Gluten|Pescado',
        ];

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers, ';');
        fputcsv($out, $example, ';');
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-productos.csv"',
        ]);
    })->name('settings.import-products.template');

    Route::get('/pairing', Pairings::class)->name('pairing');
    Route::get('/product', Products::class)->name('product');

    Route::get('/translations', App\Http\Livewire\Admin\TranslationManager::class)
        ->name('translations');

    Route::post('/api/reorder/categories', [ReorderController::class, 'categories']);
    Route::post('/api/reorder/products', [ReorderController::class, 'products']);
});

// Cambio de idioma en la carta pública
Route::get('/locale/{locale}', [ProductController::class, 'setLocale'])
    ->middleware(['detect.restaurant'])
    ->name('locale.set');
