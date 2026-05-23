<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
| Reordenación (drag & drop): routes/web.php con sesión y CSRF.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->only(['id', 'name', 'email', 'phone', 'locale']);
});

/*
|--------------------------------------------------------------------------
| NeuroCarta Public API v1
|--------------------------------------------------------------------------
| Autenticación: Authorization: Bearer nc_live_...
| Solo disponible en planes Pro y Premium.
*/
Route::prefix('v1')->middleware(['api.key', 'throttle:120,1'])->group(function () {
    Route::get('menu', [\App\Http\Controllers\Api\MenuController::class, 'index']);

    Route::post('products/sync',           [\App\Http\Controllers\Api\ProductSyncController::class, 'sync']);
    Route::delete('products/{sku}',        [\App\Http\Controllers\Api\ProductSyncController::class, 'destroy']);
    Route::patch('products/{sku}/status',  [\App\Http\Controllers\Api\ProductSyncController::class, 'status']);

    Route::post('categories/sync', [\App\Http\Controllers\Api\CategorySyncController::class, 'sync']);
});
