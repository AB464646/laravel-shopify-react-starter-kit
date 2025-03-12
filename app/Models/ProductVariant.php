<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_product_variant_id',
        'title',
        'inventoryQuantity',
        'price',
        'product_id',

    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function orderLineItem()
    {
        return $this->hasOne(OrderLineItem::class);
    }
}
