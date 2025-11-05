<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->order->load(['items.product', 'user']);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Order #{$this->order->order_number} Has Been Shipped 🚚")
            ->greeting("Hi {$notifiable->name},")
            ->line("Good news! Your order has been shipped and is on its way.")
            ->line("Shipping Address: {$this->order->shipping_address}")
            ->line("We’ll notify you again when it’s delivered.")
            ->salutation("— The " . config('app.name') . " Team");
    }
}
