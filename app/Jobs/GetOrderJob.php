<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\OrderShippingAddress;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;

class GetOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user, $order;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $order)
    {
        $this->user = $user;
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        try {

            $orderArray = $this->order->toArray();
            $user = $this->user;


            $response = $this->storeOrderWithDatabase($user, $orderArray);


            Log::info('Order stored successfully', ['response' => $response]);

            return true;
        } catch (Exception $e) {
            Log::error('Error in GetOrderJob: ' . $e->getMessage());
            return false;
        }
    }

    public function storeOrderWithDatabase(User $user, array $orderArray)
    {
        $shop = User::find($user->id);
        $dbOrder = null;
        $order = $orderArray ?? null;

        DB::beginTransaction();

        try {

            $customer = $order['customer'] ?? null;
            $shopifyCustomer = null;
            preg_match('/\d+$/', $customer['id'] ?? '', $matches);
            $changedId = isset($matches[0]) ? (int) $matches[0] : null;
            if ($customer) {
                $shopifyCustomer = Customer::updateOrCreate(
                    [
                        'shopify_customer_id' => $changedId,
                    ],
                    [
                        'email' => $customer['email'],
                        'first_name' => $customer['firstName'] ?? '',
                        'last_name' => $customer['lastName'] ?? '',
                        'phone' => $customer['phone'] ?? '',
                    ]
                );
            }
            preg_match('/\d+$/', $order['id'], $matches);
            $changedId1 = isset($matches[0]) ? (int) $matches[0] : null;
            // Create or update the order
            $dbOrder = Order::updateOrCreate(
                [
                    'shopify_order_id' => $changedId1,
                ],
                [
                    'shopify_customer_id' => $shopifyCustomer->shopify_customer_id ?? null,
                    'order_number' => $order['name'] ?? '',
                    'financial_status' => $order['displayFinancialStatus'] ?? '',
                    'fulfillment_status' => $order['displayFulfillmentStatus'] ?? '',
                ]
            );


            $shippingAddress = $order['displayAddress'] ?? null;
            if ($shippingAddress) {
                OrderShippingAddress::updateOrCreate(
                    [
                        'shopify_order_id' => $dbOrder->shopify_order_id,
                    ],
                    [
                        'first_name' => $shippingAddress['firstName'] ?? '',
                        'last_name' => $shippingAddress['lastName'] ?? '',
                        'address1' => $shippingAddress['address1'] ?? '',
                        'address2' => $shippingAddress['address2'] ?? '',
                        'city' => $shippingAddress['city'] ?? '',
                        'country' => $shippingAddress['country'] ?? '',
                        'province' => $shippingAddress['province'] ?? '',
                        'phone' => $shippingAddress['phone'] ?? '',
                    ]
                );
            }


            if (!empty($order['lineItems']['edges'])) {
                foreach ($order['lineItems']['edges'] as $lineItemEdge) {
                    $lineItem = $lineItemEdge['node'] ?? null;
                    preg_match('/\d+$/', $lineItem['id'], $matches);

                    $changedId2 = isset($matches[0]) ? (int) $matches[0] : null;
                    preg_match('/\d+$/', $lineItem['variant']['id'] ?? '', $matches1);
                    $changedId3 = isset($matches1[0]) ? (int) $matches1[0] : null;
                    if ($lineItem && isset($lineItem['id'])) {
                        OrderLineItem::updateOrCreate(
                            [
                                'shopify_line_item_id' => $changedId2,
                                'order_id' => $dbOrder->id,
                            ],
                            [
                                'title' => $lineItem['name'] ?? '',
                                'quantity' => $lineItem['currentQuantity'] ?? 0,
                                'price' => $lineItem['originalUnitPriceSet']['shopMoney']['amount'] ?? 0,
                                'currency' => $lineItem['originalUnitPriceSet']['shopMoney']['currencyCode'] ?? '',
                                'variant_id' => $changedId3 ?? 0,
                            ]
                        );
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error storing order in database: ' . $e->getMessage(), ['exception' => $e]);
            throw new Exception('Error while storing order in the database');
        }
        $shop->synced = true;
        $shop->save();

        return $dbOrder;
    }
}
