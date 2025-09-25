<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderResource extends BaseApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'items_count' => $this->whenLoaded('orderItems', fn() => $this->orderItems->count()),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}