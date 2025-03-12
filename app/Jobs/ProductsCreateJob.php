<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

use stdClass;

class ProductsCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shop's myshopify domain.
     *
     * @var ShopDomain
     */
    public ShopDomain $shopDomain;

    /**
     * The webhook data.
     *
     * @var stdClass
     */
    public stdClass $data;

    /**
     * Create a new job instance.
     *
     * @param string   $shopDomain The shop's myshopify domain.
     * @param stdClass $data       The webhook data (JSON decoded).
     */
    public function __construct(string $shopDomain, stdClass $data)
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

        $domain = $this->shopDomain->toNative();
        $user = User::where('name', $domain)->first();
        $userId = $user->id;


        $payload = $this->data;
        DB::beginTransaction();
        $product = Product::create([
            'shopify_product_id' => $payload->id,  // Correct field name
            'description' => $payload->body_html,
            'product_type' => $payload->product_type,
            'title' => $payload->title,
            'status' => $payload->status,
            'tags' => $payload->tags,
            'user_id' => $userId,
        ]);
        $productVariants = $payload->variants;
        foreach ($productVariants as $productVariant) {
            $product->variants()->create([
                'shopify_product_variant_id' => $productVariant->id,
                'title' => $productVariant->title,
                'inventory_quantity' => $productVariant->inventory_quantity,
                'price' => $productVariant->price,

            ]);
        }


        $Images = $payload->media;
        foreach ($Images as $Image) {
            $product->images()->create([
                'shopify_product_image_id' => $Image->id,
                'position' => $Image->position,
                'src' => $Image->preview_image->src,
            ]);
        }
        DB::commit();

        \Log::info('Webhook received', [
            'shop_domain' => $domain,
            'data' => json_encode($payload, JSON_PRETTY_PRINT)
        ]);


    }
}
