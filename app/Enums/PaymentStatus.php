<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';     // Awaiting payment
    case COMPLETED = 'completed'; // Payment successful
    case FAILED = 'failed';       // Payment failed
    case REFUNDED = 'refunded';   // Payment refunded

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
