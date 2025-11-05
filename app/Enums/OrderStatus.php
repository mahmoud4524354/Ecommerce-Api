<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';       // Initial state when order is created
    case PAID = 'paid';             // Payment received
    case PROCESSING = 'processing'; // Preparing the order
    case SHIPPED = 'shipped';       // Order sent to delivery
    case DELIVERED = 'delivered';   // Order received by customer
    case CANCELLED = 'cancelled';   // Order cancelled


    /**
     * Get all allowed transitions FROM this status TO other statuses
     * This defines our business rules for state changes
     *
     * @return array Array of OrderStatus enums that this status can transition to
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            // Pending orders can be paid or cancelled
            self::PENDING => [self::PAID, self::CANCELLED],

            // Paid orders can move to processing or be cancelled (refund scenario)
            self::PAID => [self::PROCESSING, self::CANCELLED],

            // Processing orders can be shipped or cancelled (inventory issues)
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],

            // Shipped orders can only be delivered (final happy path)
            self::SHIPPED => [self::DELIVERED],

            // Delivered and cancelled are final states - no transitions allowed
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    /**
     * Check if this status can transition to the target status
     *
     * @param OrderStatus $targetStatus The status we want to change to
     * @return bool True if transition is allowed, false otherwise
     */
    public function canTransitionTo(OrderStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getAllowedTransitions());
    }

    /**
     * Get human-readable label for this status
     *
     * @return string Friendly display name for the status
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending Payment',
            self::PAID => 'Payment Confirmed',
            self::PROCESSING => 'Being Prepared',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get CSS class for status display (useful for frontend styling)
     *
     * @return string CSS class name
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::PENDING => 'status-warning',
            self::PAID => 'status-info',
            self::PROCESSING => 'status-primary',
            self::SHIPPED => 'status-success',
            self::DELIVERED => 'status-success',
            self::CANCELLED => 'status-danger',
        };
    }


    // Helper method to get all statuses "valus" as array
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
