<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $items = CartItemResource::collection($this->items);

        $total = $this->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => $items,
            'total' => $total
        ];
    }
}
