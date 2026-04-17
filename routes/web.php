<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Pairings;
use App\Http\Livewire\Products;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\ProductController;
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

// Raíz de app.neurocarta.ai → login o dashboard según autenticación
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

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

// Stub Stripe para planes de pago (próximamente)
Route::get('/checkout/pending', function () {
    return view('auth.checkout-pending');
})->middleware('guest')->name('checkout.pending');

// ─── Crear contraseña (enlace del email) ───────────────────────────────────
Route::get('/set-password/{user}', [SetPasswordController::class, 'show'])
    ->middleware('guest')
    ->name('set-password.show');

Route::post('/set-password', [SetPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('set-password.store');

//Route::get('/', function () {
//    return view('menu');
//});

Route::get('/', [ProductController::class, 'index'])
    ->middleware(['detect.restaurant'])
    ->name('menu');

Route::post('/api/orders', StoreOrderController::class)
    ->middleware(['throttle:30,1', 'orders.enabled', 'detect.restaurant'])
    ->name('api.orders.store');

Route::middleware(['auth:sanctum', 'verified'])->post('/user/locale', UserLocaleController::class)
    ->name('user.locale');

// ─── Trial expirado ───────────────────────────────────────────────────────────
Route::get('/subscription/expired', function () {
    return view('subscription.expired');
})->middleware(['auth:sanctum', 'verified'])->name('subscription.expired');

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
