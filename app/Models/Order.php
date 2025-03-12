<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'shopify_order_id',
        'shopify_customer_id',
        'order_number',
        'financial_status',
        'fullfillment_status',
    ];
    public function orderLineItems()
    {
        return $this->hasMany(OrderLineItem::class);
    }
    public function orderShippingAddress()
    {
        return $this->hasOne(OrderShippingAddress::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);

    }

}
