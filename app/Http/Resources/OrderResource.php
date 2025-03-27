<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'order_number' => $this->order_number,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'shipping_method' => $this->shipping_method,
            'shipping_cost' => $this->shipping_cost,
            'notes' => $this->notes,
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'items_count' => $this->when(
                $this->items_count !== null,
                $this->items_count
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 