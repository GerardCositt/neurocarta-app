<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreOrderController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'               => 'required|array|min:1|max:80',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1|max:99',
            'customer_name'      => 'nullable|string|max:120',
            'customer_phone'     => 'nullable|string|max:40',
            'customer_notes'     => 'nullable|string|max:2000',
        ]);

        $quantities = [];
        foreach ($data['items'] as $row) {
            $pid = (int) $row['product_id'];
            $quantities[$pid] = ($quantities[$pid] ?? 0) + (int) $row['quantity'];
        }

        $ids = array_keys($quantities);
        $restaurant = app()->bound('restaurant') ? app('restaurant') : null;

        $products = Product::query()
            ->visible()
            ->when($restaurant, function ($query) use ($restaurant) {
                $query->where('restaurant_id', $restaurant->id);
            })
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($ids)) {
            return response()->json([
                'message' => 'Algún producto no está disponible en la carta. Actualiza la página e inténtalo de nuevo.',
            ], 422);
        }

        $order = DB::transaction(function () use ($data, $quantities, $products, $restaurant) {
            $order = Order::create([
                'customer_name'   => $data['customer_name'] ?? null,
                'customer_phone'  => $data['customer_phone'] ?? null,
                'customer_notes'  => $data['customer_notes'] ?? null,
                'status'          => Order::STATUS_PENDING,
                'restaurant_id'   => $restaurant?->id,
            ]);

            foreach ($quantities as $productId => $qty) {
                /** @var Product $p */
                $p = $products[$productId];
                $unit = $p->isOfferActive() && strlen((string) $p->offer_price) > 0
                    ? $p->offer_price
                    : $p->price;

                OrderItem::create([
                    'order_id'      => $order->id,
                    'product_id'    => $p->id,
                    'product_name'  => $p->name,
                    'unit_price'    => (string) $unit,
                    'quantity'      => $qty,
                ]);
            }

            return $order;
        });

        return response()->json([
            'ok'        => true,
            'order_id'  => $order->id,
            'message'   => 'Pedido recibido. Gracias.',
        ]);
    }
}
