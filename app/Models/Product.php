<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'stock', 'sku', 'is_active'
    ];

    public function inStock()
    {
        return $this->stock > 0;
    }

    public function categories(){
        return $this->hasMany(Category::class);
    }
}
