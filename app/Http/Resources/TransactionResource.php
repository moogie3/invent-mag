<?php

namespace App\Http\Resources;

use App\Http\Resources\AccountResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'journal_entry_id' => $this->journal_entry_id,
            'account_id' => $this->account_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'account' => new AccountResource($this->whenLoaded('account')),
        ];
    }
}
