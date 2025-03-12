<?php
namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\OrderShippingAddress;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use stdClass;

class OrdersCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var ShopDomain|string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     *
     * @return void
     */
    public function __construct($shopDomain, $data)
    {
        $this->shopDomain = ShopDomain::fromNative($shopDomain);
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Convert domain
        $domain = $this->shopDomain->toNative();
        $payload = $this->data;

        DB::beginTransaction();


        // Process customer
        $customer = $payload->customer;
        $shopifyCustomer = Customer::updateOrCreate(
            [
                'shopify_customer_id' => $customer->id,
            ],
            [
                'email' => $customer->email,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'phone' => $customer->phone,
            ]
        );


        $shopifyOrder = Order::create([
            'shopify_order_id' => $payload->id,
            'shopify_customer_id' => $shopifyCustomer->id,
            'order_number' => $payload->order_number,
            'financial_status' => $payload->financial_status,
            'fulfillment_status' => $payload->fulfillment_status,
        ]);

        // Save shipping address separately
        $shippingAddress = $payload->shipping_address;
        OrderShippingAddress::create([
            "first_name" => $shippingAddress->first_name,
            "last_name" => $shippingAddress->last_name,
            "address1" => $shippingAddress->address1,
            "address2" => $shippingAddress->address2,
            "city" => $shippingAddress->city,
            "country" => $shippingAddress->country,
            "province" => $shippingAddress->province,
            "phone" => $shippingAddress->phone,
            'shopify_order_id' => $shopifyOrder->id,
        ]);

        // Process order line items
        foreach ($payload->line_items as $lineItem) {
            OrderLineItem::create([
                'order_id' => $shopifyOrder->id,
                'shopify_line_item_id' => $lineItem->id,
                'title' => $lineItem->title,
                'quantity' => $lineItem->quantity,
                'price' => $lineItem->price,
                'variant_id' => $lineItem->variant_id,

            ]);
        }

        DB::commit();



        \Log::info('Webhook processed successfully', [
            'shop_domain' => $domain,
            'payload' => $payload,
        ]);
    }

}
