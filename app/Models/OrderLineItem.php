<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shopify_line_item_id',
        'title',
        'quantity',
        'price',
        'variant_id',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

