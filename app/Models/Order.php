<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'status', 'shipping_name', 'shipping_address',
        'shipping_city', 'shipping_state', 'shipping_zipcode',
        'shipping_country', 'shipping_phone', 'subtotal', 'tax',
        'shipping_cost', 'total', 'payment_method', 'payment_status',
        'order_number', 'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber()
    {
        $year = date('Y');
        $random = strtoupper(substr(uniqid(), -6));
        return "ORD-{$year}-{$random}";
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'paid']);
    }
}
