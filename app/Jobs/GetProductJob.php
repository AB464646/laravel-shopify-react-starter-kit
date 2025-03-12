<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Traits\ShopifyProductTrait;
use Log;

class GetProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ShopifyProductTrait;
    protected $user, $product;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $product)
    {
        $this->user = $user;
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        try {

            $productArray = $this->product->toArray();
            $user = $this->user;


            $response = $this->storeWithDatabase($this->user, $productArray);


            Log::info('Product stored successfully', ['response' => $response]);

            return true;
        } catch (Exception $e) {
            Log::error('Error in GetProductJob: ' . $e->getMessage());
            return false;
        }
    }
}
