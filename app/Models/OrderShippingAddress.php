<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderShippingAddress extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'address1',
        'city',
        'country',
        'phone',
        'province',
        'shopify_order_id'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
