<?php

// namespace App\Jobs;

// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Bus\Dispatchable;
// use Illuminate\Queue\InteractsWithQueue;
// use Illuminate\Queue\SerializesModels;
// use App\Http\Traits\ShopifyProductTrait;
// use Log;

// class SyncProductJob implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShopifyProductTrait;

//     protected $product;
//     protected $user;

//     /**
//      * Create a new job instance.
//      */
//     public function __construct($product, $user)
//     {
//         $this->product = $product;
//         $this->user = $user;
//     }

//     /**
//      * Execute the job.
//      */
//     public function handle(): bool
//     {
//         try {
//             $this->syncWithShopify($this->product, $this->user);
//             return true;
//         } catch (\Exception $e) {
//             Log::error("Shopify Sync Failed: " . $e->getMessage());
//             return false;
//         }
//     }
// }

