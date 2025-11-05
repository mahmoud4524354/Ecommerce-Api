<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;


class SendOrderStatusEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $order = $event->order;

        switch ($order->status) {
            case OrderStatus::PAID:
                $order->user->notify(new OrderConfirmationNotification($order));
                break;

            case OrderStatus::SHIPPED:
                $order->user->notify(new OrderShippedNotification($order));
                break;

            case OrderStatus::DELIVERED:
                $order->user->notify(new OrderDeliveredNotification($order));
                break;

            case OrderStatus::CANCELLED:
                $order->user->notify(new OrderCancelledNotification($order));
                break;

            default:
                // No email needed for PENDING/PROCESSING
                break;
        }
    }
}
