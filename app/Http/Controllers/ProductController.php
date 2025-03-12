<?php
namespace App\Http\Controllers;

use App\Jobs\SyncProductJob;
use App\Models\OrderLineItem;
use App\Models\Product;
use App\Models\ProductVariant;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\ShopifyProductTrait;
use Inertia\Inertia;

class ProductController extends Controller
{


    use ShopifyProductTrait;

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->synced) {
            $this->fetchProducts($user);
            $this->fetchOrders($user);
        }
        $filter = $request->all();
        return Inertia::render('Dashboard', compact(['user', 'filter']));
    }
    public function createShopifyProduct(Request $request)
    {

        $tags = implode(',', $request->tags);

        $query = <<<QUERY
    mutation CreateProduct {
        productCreate(input: {
            title: "$request->title",
            descriptionHtml: "$request->description",
            productType: "$request->productType",
            tags: "$tags",
            status: $request->status,
            productOptions: [{
                name: "$request->optionName",
                values: [
                    { name: "$request->optionValue" }
                ]
            }]
        }) {
            product {
                id
                title
                descriptionHtml
                productType
                tags
                status
                options {
                    name
                    optionValues {
                        name
                    }
                }
            }
            userErrors {
                field
                message
            }
        }
    }
QUERY;

        $user = $request->user();
        $result = $user->api()->graph($query);
        \Log::info('GraphQL Query:', ['query' => $query]);
        \Log::info('GraphQL Response:', ['response' => $result]);

        if (!empty($result['body']['data']['productCreate']['userErrors'])) {
            $product = $result['body']['data']['productCreate'];
            return response()->json(['success' => true, 'message' => 'Product created successfully', 'data' => $product], 200);
        }
        $errors = $result['body']['data']['productCreate']['userErrors'] ??

            $result['errors'];
        return response()->json(['success' => false, 'message' => 'API Failed', 'errors' => $errors], 400);
    }

    // Other methods...
    public function getProducts(Request $request)
    {
        try {
            $query = Product::query();

            if ($request->has('query')) {
                $searchTerm = $request->input('query');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('product_type', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('tags', 'LIKE', "%{$searchTerm}%");
                });
            }

            if ($request->has('status') && !empty($request->input('status'))) {
                $query->whereIn('status', $request->input('status'));
            }

            $perPage = 10; // Number of items per page
            $products = $query->paginate($perPage);

            $productsWithOrders = $products->map(function ($product) {
                $variants = ProductVariant::where('product_id', $product->id)->get();

                $variantOrderCounts = [];
                foreach ($variants as $variant) {
                    $orderCount = OrderLineItem::where('variant_id', $variant->shopify_product_variant_id)
                        ->distinct('order_id')
                        ->count('order_id');

                    $variantOrderCounts[] = [
                        'order_count' => $orderCount
                    ];
                }

                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'product_type' => $product->product_type,
                    'tags' => $product->tags,
                    'status' => $product->status,
                    'variants' => $variantOrderCounts,
                    'total_orders' => collect($variantOrderCounts)->sum('order_count')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $productsWithOrders,
                    'total_pages' => $products->lastPage(),
                    'current_page' => $products->currentPage(),
                ],
                'message' => 'Products fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products: ' . $e->getMessage()
            ], 500);
        }
    }

    // public function syncProducts()
    // {
    //     try {
    //         $user = Auth::user();

    //         $products = Product::where('user_id', $user->id)
    //             ->chunk(100, function ($productsChunk) use ($user) {
    //                 $transformedProducts = $productsChunk->map(function ($product) {
    //                     return [
    //                         'id' => $product->shopify_product_id,
    //                         'title' => $product->title,
    //                         'description' => $product->description,
    //                         'status' => $product->status === null && $product->status !== 'archived' ? 'active' : $product->status,
    //                         'tags' => $product->tags,
    //                         'product_type' => $product->product_type,
    //                     ];
    //                 })->toArray();


    //                 foreach ($transformedProducts as $product) {
    //                     SyncProductJob::dispatch($product, $user);
    //                 }


    //                 Product::whereIn('shopify_id', array_column($transformedProducts, 'id'))
    //                     ->update([
    //                         'status' => DB::raw('CASE WHEN tag IS NULL AND status != "archived" THEN "active" ELSE status END'),
    //                         'tag' => DB::raw('COALESCE(tag, status)'),
    //                     ]);
    //             });

    //         return response()->json(['success' => true, 'message' => 'Products are being synced with Shopify.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => true, 'message' => 'Products are being synced with Shopify.']);
    //     }
    // }


    public function updateProductStatus(Request $request, $id)
    {
        $product = Product::where("id", $id)->first();
        $ShopifyId = $product->shopify_product_id;
        try {
            $query = <<<QUERY
                    mutation ProductUpdate {
                        productUpdate(input: {
                            id: "gid://shopify/Product/$ShopifyId",
                            status: $request->status
                        }) {
                            product {
                                id
                                status
                            }
                            userErrors {
                                field
                                message
                            }
                        }
                    }
        QUERY;

            $user = $request->user();
            $result = $user->api()->graph($query);

            \Log::info('Status Update Query:', ['query' => $query]);
            \Log::info('Status Update Response:', ['response' => $result]);

            if (isset($result['body']['data']['productUpdate']['product'])) {
                $product = Product::where('shopify_product_id', $ShopifyId)->first();
                if ($product) {
                    $product->status = $request->status;
                    $product->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Product status updated successfully',
                    'data' => $result['body']['data']['productUpdate']['product']
                ], 200);
            }

            $errors = $result['body']['data']['productUpdate']['userErrors'] ?? $result['errors'];
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product status',
                'errors' => $errors
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Product status update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating product status',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}





