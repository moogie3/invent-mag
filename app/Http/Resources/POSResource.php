<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POSResource extends JsonResource
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
            'invoice' => $this->invoice,
            'supplier_id' => $this->supplier_id,
            'user_id' => $this->user_id,
            'order_date' => $this->order_date,
            'due_date' => $this->due_date,
            'payment_type' => $this->payment_type,
            'discount_total' => $this->discount_total,
            'discount_total_type' => $this->discount_total_type,
            'total' => $this->total,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
