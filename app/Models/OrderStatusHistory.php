<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes'
    ];

    /**
     * Cast status fields to OrderStatus enums for type safety
     */
    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
    ];

    /**
     * Get the order this history entry belongs to
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made this status change
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
