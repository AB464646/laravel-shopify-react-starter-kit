<!-- <?php

namespace App\Http\Traits;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

trait ShopifyOrderTrait
{
    public function syncWithShopify($orderData, User $user)
    {
        $query = <<<QUERY

        QUERY;

        $status = strtoupper($orderData['status']);

        $variables = [
            "input" => [
                "id" => "gid://shopify/Product/{$orderData['id']}",
                "title" => "{$orderData['title']}",
                "descriptionHtml" => "{$orderData['description']}",
                "status" => $status,
                "tags" => "{$orderData['tags']}",
                "productType" => "{$orderData['product_type']}"
            ]
        ];

        try {
            $response = $user->api()->graph($query, $variables);

            // Log errors if any exist
            if (!empty($response['errors'])) {
                \Log::error('GraphQL Errors: ' . json_encode($response['errors']));
            }

            if (!empty($response['body']['data']['productUpdate']['userErrors'])) {
                \Log::error('User Errors: ' . json_encode($response['body']['data']['productUpdate']['userErrors']));
            }

            return $response['body']['data']['productUpdate'] ?? null;
        } catch (Exception $e) {
            \Log::error('GraphQL Sync Failed: ' . $e->getMessage());
            throw new Exception('Error syncing product variants with Shopify');
        }
    }




}
