<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_product_image_id',
        'position',
        'src',
        'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
