<?php
namespace App\Http\Controllers;

use App\Jobs\OrderSyncJob;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Auth;
use DB;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function getOrders(Request $request)
    {
        try {
            $query = Order::query();

            if ($request->has('query')) {
                $searchTerm = $request->input('query');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('financial_status', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('order_number', 'LIKE', "%{$searchTerm}%");
                });
            }

            if ($request->has('status') && !empty($request->input('status'))) {
                $query->whereIn('financial_status', $request->input('status'));
            }

            $perPage = 10; // Number of items per page
            $orders = $query->paginate($perPage);

            $ordersWithProducts = $orders->map(function ($order) {
                $orderLineItems = OrderLineItem::where('order_id', $order->id)->get();

                $products = [];
                foreach ($orderLineItems as $lineItem) {
                    $variant = ProductVariant::where('shopify_product_variant_id', $lineItem->variant_id)->first();
                    if ($variant) {
                        $product = Product::where('id', $variant->product_id)->first();
                        if ($product) {
                            $products[] = [
                                'product_name' => $product->title,
                            ];
                        }
                    } else {
                        \Log::warning('Variant not found', ['variant_id' => $lineItem->variant_id]);
                    }
                }

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'financial_status' => $order->financial_status,
                    'products' => $products,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $ordersWithProducts,
                    'total_pages' => $orders->lastPage(),
                    'current_page' => $orders->currentPage(),
                ],
                'message' => 'Orders fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching orders: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncOrders()
    {
        try {
            $user = Auth::user();

            Order::where('user_id', $user->id)
                ->chunk(100, function ($ordersChunk) use ($user) {
                    $transformedOrders = $ordersChunk->map(function ($order) {
                        return [
                            'id' => $order->shopify_order_id,
                            'order_number' => $order->order_number,
                            'financial_status' => $order->financial_status ?? 'paid',
                            'user_id' => $order->user_id
                        ];
                    })->toArray();

                    foreach ($transformedOrders as $orderData) {
                        OrderSyncJob::dispatch($orderData, $user);
                    }

                    Order::whereIn('shopify_order_id', array_column($transformedOrders, 'id'))
                        ->update([
                            'financial_status' => DB::raw("CASE
                            WHEN financial_status IS NULL THEN 'paid'
                            WHEN financial_status != 'Expired' THEN financial_status
                            ELSE financial_status
                        END")
                        ]);
                });

            return response()->json([
                'success' => true,
                'message' => 'Orders synchronization has been initiated.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Order sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error syncing orders: ' . $e->getMessage()
            ], 500);
        }
    }
}
