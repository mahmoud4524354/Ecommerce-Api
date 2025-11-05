<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the payment provider
     */
    public function label(): string
    {
        return match($this) {
            self::STRIPE => 'Credit Card (Stripe)',
            self::PAYPAL => 'PayPal',
        };
    }
}
