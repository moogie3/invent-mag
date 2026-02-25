<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POItemResource extends JsonResource
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
            'po_id' => $this->po_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'price' => $this->price,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'total' => $this->total,
            'expiry_date' => $this->expiry_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Conditionally load relations only if they were eager loaded
            'purchase' => new PurchaseResource($this->whenLoaded('purchaseOrder')),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
