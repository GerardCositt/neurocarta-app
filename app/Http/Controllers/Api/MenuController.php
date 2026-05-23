<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $restaurant = $request->attributes->get('api_restaurant');

        $categories = Category::where('restaurant_id', $restaurant->id)
            ->with(['products' => function ($q) {
                $q->select('id', 'external_sku', 'name', 'description', 'price', 'offer_price',
                           'offer', 'hidden', 'featured', 'recommended', 'photo', 'category_id', 'order')
                  ->orderBy('order');
            }])
            ->orderBy('order')
            ->get(['id', 'external_sku', 'name', 'hidden', 'order']);

        return response()->json([
            'restaurant' => $restaurant->name,
            'categories' => $categories,
        ]);
    }
}
