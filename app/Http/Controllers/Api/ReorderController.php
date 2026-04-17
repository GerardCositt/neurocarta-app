<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Reordenar categorías
     * POST /api/reorder/categories
     * Body: { "ids": [5, 2, 8, 1, 3] }
     */
    public function categories(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array']);

        abort_unless(app()->bound('restaurant'), 403);
        $restaurantId = app('restaurant')->id;

        foreach ($data['ids'] as $order => $id) {
            Category::query()
                ->where('restaurant_id', $restaurantId)
                ->where('id', $id)
                ->update(['order' => $order]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Reordenar productos
     * POST /api/reorder/products
     * Body: { "ids": [12, 7, 3, 15, 1] }
     */
    public function products(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array']);

        abort_unless(app()->bound('restaurant'), 403);
        $restaurantId = app('restaurant')->id;

        foreach ($data['ids'] as $order => $id) {
            Product::query()
                ->where('restaurant_id', $restaurantId)
                ->where('id', $id)
                ->update(['order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
