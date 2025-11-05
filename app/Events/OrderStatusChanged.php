<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $previousStatus = null,
        public ?string $changedBy = null
    ) {
        // Load relationships for broadcast
        $this->order->load(['user', 'items.product']);
    }

    /**
     * Channels to broadcast on
     */
    public function broadcastOn(): array
    {
        return [
            // Customer channel
            new PrivateChannel('user.' . $this->order->user_id . '.orders'),

            // Admins channel
            new PrivateChannel('admin.orders'),
        ];
    }

    /**
     * Custom event name
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Data sent in the broadcast
     */
    public function broadcastWith(): array
    {
        $broadcastData = [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'current_status' => $this->order->status->value,
            'previous_status' => $this->previousStatus,
            'changed_by' => $this->changedBy,
            'total' => $this->order->total,
            'updated_at' => $this->order->updated_at->toISOString(),
            'user' => [
                'id' => $this->order->user->id,
                'name' => $this->order->user->name,
                'email' => $this->order->user->email,
            ],
            'items_count' => $this->order->items->count(),
            'items_summary' => $this->order->items->take(3)->map(fn($item) => [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
            ])->toArray(),
        ];

        Log::info('Broadcasting order status changed', $broadcastData);

        return $broadcastData;
    }
}
