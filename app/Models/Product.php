<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'stock', 'sku', 'is_active'
    ];

    public function inStock()
    {
        return $this->stock > 0;
    }
}
