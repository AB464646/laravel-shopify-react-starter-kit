<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\ShopifyOrderTrait;
use Log;

class OrderSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShopifyOrderTrait;


    protected $orderData;
    protected $user;
    /**
     * Create a new job instance.
     */
    public function __construct($orderData, $user)
    {
        $this->orderData = $orderData;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->syncWithShopify($this->orderData, $this->user);
        } catch (\Exception $e) {
            Log::error("Shopify Sync Failed: " . $e->getMessage());
        }
    }
}
