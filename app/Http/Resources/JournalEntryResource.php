<?php

namespace App\Http\Resources;

use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
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
            'date' => $this->date,
            'description' => $this->description,
            'sourceable_type' => $this->sourceable_type,
            'sourceable_id' => $this->sourceable_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
