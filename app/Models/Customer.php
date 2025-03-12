<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_customer_id',
        'first_name',
        'last_name',
        'email',
        'phone'
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    // Add any other customer-specific fields here]
}
