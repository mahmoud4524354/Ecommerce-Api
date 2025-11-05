<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'status', 'shipping_name', 'shipping_address',
        'shipping_city', 'shipping_state', 'shipping_zipcode',
        'shipping_country', 'shipping_phone', 'subtotal', 'tax',
        'shipping_cost', 'total', 'payment_method', 'payment_status',
        'transaction_id', 'paid_at', 'order_number', 'notes',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'paid_at' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the status history for this order (newest first)
     */
    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    /**
     * Safely transition order status with validation and history tracking
     *
     * @param OrderStatus $newStatus The status we want to change to
     * @param User|null $changedBy Who is making the change (defaults to current user)
     * @param string|null $notes Optional reason for the change
     * @return bool True if transition was successful, false if not allowed
     * @throws \Exception If transition is not allowed
     */
    public function transitionTo(OrderStatus $newStatus, $changedBy = null, ?string $notes = null): bool
    {
        // Don't allow transition to the same status
        if ($this->status === $newStatus) {
            return true; // Already in target status, consider it successful
        }

        // Check if this transition is allowed by our business rules
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \Exception(
                "Cannot transition order #{$this->order_number} from {$this->status->value} to {$newStatus->value}. "
            );
        }

        // Store the old status for history
        $oldStatus = $this->status;

        // Update the order status
        $this->status = $newStatus;
        $this->save();

        // Record the status change in history
        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $changedBy?->id ?? Auth::id(), // Use provided user or current logged-in user
            'notes' => $notes,
        ]);


        // Broadcast real-time event
        OrderStatusChanged::dispatch(
            $this,
            $oldStatus->value,
            $changedBy?->name ?? Auth::user()?->name
        );

        return true;
    }

    /**
     * Get all possible next statuses for this order
     *
     * @return array Array of OrderStatus enums this order can transition to
     */
    public function getAvailableTransitions(): array
    {
        return $this->status->getAllowedTransitions();
    }


    /**
     * Get the latest status change from history
     *
     * @return OrderStatusHistory|null
     */
    public function getLatestStatusChange()
    {
        return $this->statusHistory()->first();
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

    public function markAsPaid(string $transactionId): void
    {
        $this->update([
            'status' => OrderStatus::PAID,
            'payment_status' => PaymentStatus::COMPLETED,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markPaymentFailed(): void
    {
        $this->update([
            'payment_status' => PaymentStatus::FAILED,
        ]);
    }

}
