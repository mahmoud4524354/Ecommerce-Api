<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'session_id',
        'payment_intent_id',
        'paypal_order_id',
        'paypal_capture_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'completed_at'
    ];


    protected $casts = [
        'provider' => PaymentProvider::class,
        'status' => PaymentStatus::class,
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(string $paymentIntentId, array $metadata = []): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'payment_intent_id' => $paymentIntentId,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'completed_at' => now(),
        ]);

        // Also update the associated order
        $this->order->markAsPaid($paymentIntentId);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(array $metadata = []): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);

        // Update order payment status
        $this->order->markPaymentFailed();
    }

    /**
     * Check if the payment is in a final state
     */
    public function isFinal(): bool
    {
        return in_array($this->status, [
            PaymentStatus::COMPLETED,
            PaymentStatus::FAILED,
            PaymentStatus::REFUNDED
        ]);
    }
}
