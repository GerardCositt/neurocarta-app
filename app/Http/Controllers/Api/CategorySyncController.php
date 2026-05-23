<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\PlanEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategorySyncController extends Controller
{
    public function __construct(private PlanEntitlementService $plans) {}

    public function sync(Request $request): JsonResponse
    {
        $restaurant = $request->attributes->get('api_restaurant');
        $account    = $restaurant->account;

        $items = $request->validate([
            '*.sku'    => 'required|string|max:100',
            '*.name'   => 'required|string|max:255',
            '*.hidden' => 'nullable|boolean',
        ]);

        $results = [];

        foreach ($items as $item) {
            $existing = Category::where('restaurant_id', $restaurant->id)
                ->where('external_sku', $item['sku'])
                ->first();

            if (! $existing) {
                try {
                    $this->plans->assertCanCreateCategory($account);
                } catch (\RuntimeException $e) {
                    $results[] = ['sku' => $item['sku'], 'status' => 'skipped', 'reason' => $e->getMessage()];
                    continue;
                }
            }

            $category = Category::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'external_sku' => $item['sku']],
                [
                    'name'   => $item['name'],
                    'hidden' => $item['hidden'] ?? false,
                ]
            );

            $results[] = ['sku' => $item['sku'], 'id' => $category->id, 'status' => 'ok'];
        }

        return response()->json(['synced' => count(array_filter($results, fn($r) => $r['status'] === 'ok')), 'results' => $results]);
    }
}
