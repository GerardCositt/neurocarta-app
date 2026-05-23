<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageAssetService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductSyncController extends Controller
{
    public function __construct(
        private PlanEntitlementService $plans,
        private ImageAssetService $images,
    ) {}

    public function sync(Request $request): JsonResponse
    {
        $restaurant = $request->attributes->get('api_restaurant');
        $account    = $restaurant->account;

        $items = $request->validate([
            '*.sku'          => 'required|string|max:100',
            '*.name'         => 'required|string|max:255',
            '*.price'        => 'required|numeric|min:0',
            '*.category_sku' => 'nullable|string|max:100',
            '*.description'  => 'nullable|string|max:2000',
            '*.image_url'    => 'nullable|url|max:500',
            '*.active'       => 'nullable|boolean',
        ]);

        $results = [];

        foreach ($items as $item) {
            $existing = Product::where('restaurant_id', $restaurant->id)
                ->where('external_sku', $item['sku'])
                ->first();

            if (! $existing) {
                try {
                    $this->plans->assertCanCreateProduct($account);
                } catch (\RuntimeException $e) {
                    $results[] = ['sku' => $item['sku'], 'status' => 'skipped', 'reason' => $e->getMessage()];
                    continue;
                }
            }

            $categoryId = null;
            if (! empty($item['category_sku'])) {
                $cat = Category::where('restaurant_id', $restaurant->id)
                    ->where('external_sku', $item['category_sku'])
                    ->first();
                $categoryId = $cat?->id;
            }

            $product = Product::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'external_sku' => $item['sku']],
                [
                    'name'        => $item['name'],
                    'price'       => $item['price'],
                    'description' => $item['description'] ?? null,
                    'hidden'      => ! ($item['active'] ?? true),
                    'category_id' => $categoryId,
                    'is_template' => false,
                ]
            );

            if (! empty($item['image_url'])) {
                try {
                    $response = Http::timeout(10)->get($item['image_url']);
                    if ($response->successful()) {
                        $photo = $this->images->storeBinaryImage(
                            $response->body(),
                            'img',
                            1200,
                            'api-' . $product->id
                        );
                        $product->update(['photo' => $photo]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("API sync: could not download image for SKU {$item['sku']}: " . $e->getMessage());
                }
            }

            $results[] = ['sku' => $item['sku'], 'id' => $product->id, 'status' => 'ok'];
        }

        return response()->json([
            'synced'  => count(array_filter($results, fn($r) => $r['status'] === 'ok')),
            'results' => $results,
        ]);
    }

    public function destroy(Request $request, string $sku): JsonResponse
    {
        $restaurant = $request->attributes->get('api_restaurant');

        $product = Product::where('restaurant_id', $restaurant->id)
            ->where('external_sku', $sku)
            ->first();

        if (! $product) {
            return response()->json(['error' => "Producto con SKU '{$sku}' no encontrado"], 404);
        }

        $product->delete();

        return response()->json(['deleted' => true, 'sku' => $sku]);
    }

    public function status(Request $request, string $sku): JsonResponse
    {
        $restaurant = $request->attributes->get('api_restaurant');

        $product = Product::where('restaurant_id', $restaurant->id)
            ->where('external_sku', $sku)
            ->first();

        if (! $product) {
            return response()->json(['error' => "Producto con SKU '{$sku}' no encontrado"], 404);
        }

        $data = $request->validate(['active' => 'required|boolean']);

        $product->update(['hidden' => ! $data['active']]);

        return response()->json(['sku' => $sku, 'active' => $data['active']]);
    }
}
