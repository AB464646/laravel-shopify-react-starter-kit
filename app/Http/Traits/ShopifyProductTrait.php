<?php

namespace App\Http\Traits;
use App\Jobs\GetOrderJob;
use App\Jobs\GetProductJob;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Log;
use Exception;

trait ShopifyProductTrait
{
    public function fetchProducts(?User $user)
    {
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not provided'], 400);
        }

        $perPage = 100;
        $afterCursor = null;


        $query = <<<QUERY
    query GetProducts(\$first: Int!, \$after: String) {
        products(first: \$first, after: \$after) {
            edges {
                cursor
                node {
                    id
                    title
                    bodyHtml
                    productType
                    status
                    tags
                    variants(first: 10) {
                        edges {
                            node {
                                id
                                title
                                price
                                inventoryQuantity
                            }
                        }
                    }
                }
            }
            pageInfo {
                hasNextPage
            }
        }
    }
QUERY;

        try {
            do {

                $result = $user->api()->graph($query, [
                    'first' => $perPage,
                    'after' => $afterCursor,
                ]);


                \Log::info('GraphQL Response:', ['response' => $result]);

                $products = $result['body']['data']['products']['edges'] ?? [];


                foreach ($products as $productEdge) {
                    $product = $productEdge['node'];
                    GetProductJob::dispatch($user, $product);
                }


                $pageInfo = $result['body']['data']['products']['pageInfo'];
                $afterCursor = $pageInfo['hasNextPage'] ? end($products)['cursor'] : null;

            } while ($afterCursor);



            return response()->json(['success' => true, 'message' => 'Products fetched and processed successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching products: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error fetching products'], 500);
        }
    }

    public function storeWithDatabase(User $user, array $productArray)
    {
        $shop = User::find($user->id);
        $dbProduct = null;
        $product = $productArray ?? null;
        $tags = isset($product['tags']) ? implode(',', $product['tags']) : '';
        preg_match('/\d+$/', $product['id'], $matches);
        $changedId = isset($matches[0]) ? (int) $matches[0] : null;
        try {
            $dbProduct = Product::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'shopify_product_id' => $changedId,
                ],
                [
                    'title' => $product['title'] ?? '',
                    'tags' => $tags ?? '',
                    'status' => $product['status'] ?? '',
                    'description' => $product['bodyHtml'] ?? '',
                    'product_type' => $product['productType'] ?? '',
                ]
            );
            if (!empty($product['variants']['edges'])) {
                foreach ($product['variants']['edges'] as $variantEdge) {
                    $variant = $variantEdge['node'] ?? null;
                    if ($variant && isset($variant['id'])) {
                        preg_match('/\d+$/', $variant['id'], $matches);
                        $convertedId = isset($matches[0]) ? (int) $matches[0] : null;

                        if ($convertedId) {
                            ProductVariant::updateOrCreate(
                                [
                                    'shopify_product_variant_id' => $convertedId,
                                    'product_id' => $dbProduct->id,
                                ],
                                [
                                    'price' => $variant['price'],
                                    'inventoryQuantity' => $variant['inventoryQuantity'] ?? '',
                                    'title' => $variant['title'] ?? '',
                                ]
                            );
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Error storing product in database: ' . $e->getMessage(), ['exception' => $e]);
            throw new Exception('Error while storing product in the database');
        }

        return $dbProduct;
    }
    public function fetchOrders(?User $user)
    {
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not provided'], 400);
        }

        $perPage = 100;
        $afterCursor = null;

        $query = <<<QUERY
            query GetOrders(\$first: Int!, \$after: String) {
                orders(first: \$first, after: \$after) {
                    edges {
                        cursor
                        node {
                            id
                            name
                            displayFinancialStatus
                            displayFulfillmentStatus
                            displayAddress {
                                firstName
                                lastName
                                address1
                                address2
                                city
                                province
                                country
                                phone
                            }
                            lineItems(first: 10) {
                                edges {
                                    node {
                                        id
                                        name
                                        currentQuantity
                                        originalUnitPriceSet {
                                            shopMoney {
                                                amount
                                            }
                                        }
                                        variant {
                                            id
                                        }
                                    }
                                }
                            }
                            customer {
                                id
                                firstName
                                lastName
                                email
                            }
                        }
                    }
                    pageInfo {
                        hasNextPage
                    }
                }
            }
QUERY;

        try {
            do {

                $result = $user->api()->graph($query, [
                    'first' => $perPage,
                    'after' => $afterCursor,
                ]);


                \Log::info('GraphQL Response:', ['response' => $result]);


                // if (isset($result['errors'])) {
                //     \Log::error('GraphQL Errors:', ['errors' => $result['errors']]);
                //     return response()->json(['success' => false, 'message' => 'API Failed', 'errors' => $result['errors']], 400);
                // }


                $orders = $result['body']['data']['orders']['edges'] ?? [];

                foreach ($orders as $orderEdge) {
                    $order = $orderEdge['node'];
                    GetOrderJob::dispatch($user, $order);
                }


                $pageInfo = $result['body']['data']['orders']['pageInfo'];
                $afterCursor = $pageInfo['hasNextPage'] ? end($orders)['cursor'] : null;

            } while ($afterCursor);

            return response()->json(['success' => true, 'message' => 'Orders fetched and processed successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching orders: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Error fetching orders'], 500);
        }
    }
}
