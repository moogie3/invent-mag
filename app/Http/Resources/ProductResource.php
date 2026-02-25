<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'code' => $this->code,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            // Handle image URL generation carefully
            'image' => $this->image ? url("storage/image/{$this->image}") : url('img/default_placeholder.png'),
            'price' => $this->price,
            'selling_price' => $this->selling_price,
            'stock_quantity' => $this->stock_quantity,
            'category_id' => $this->category_id,
            'supplier_id' => $this->supplier_id,
            'units_id' => $this->units_id,
            'has_expiry' => $this->has_expiry,
            'low_stock_threshold' => $this->low_stock_threshold,
            'warehouse_id' => $this->warehouse_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Add relationships if needed
            'category' => new CategoryResource($this->whenLoaded('category')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
        ];
    }
}
