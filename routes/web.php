<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Pairings;
use App\Http\Livewire\Products;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ReorderController;
use App\Http\Controllers\Api\StoreOrderController;
use App\Http\Controllers\UserLocaleController;

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

// Diagnóstico temporal — ELIMINAR tras resolver el 500
Route::get('/debug-info', function () {
    try {
        \DB::connection()->getPdo();
        $db = 'DB OK: ' . \DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        $db = 'DB ERROR: ' . $e->getMessage();
    }
    return response()->json([
        'db'      => $db,
        'env'     => app()->environment(),
        'app_key' => env('APP_KEY') ? 'set' : 'missing',
    ]);
});

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

Route::middleware(['auth:sanctum', 'verified', 'admin.restaurant'])->group(function () {
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
