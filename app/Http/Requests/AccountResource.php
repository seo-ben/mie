<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_number' => $this->account_number,
            'account_type' => $this->account_type,
            'account_name' => $this->account_name,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'status' => $this->status,
            'interest_rate' => $this->interest_rate,
            'minimum_balance' => $this->minimum_balance,
            'overdraft_limit' => $this->overdraft_limit,

            // Relations
            'client' => new ClientResource($this->whenLoaded('client')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),

            // Dates
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'activated_at' => $this->activated_at?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),

            // Métadonnées
            'is_active' => $this->status === 'active',
            'is_suspended' => $this->status === 'suspended',
            'transactions_count' => $this->whenLoaded('transactions', function () {
                return $this->transactions->count();
            }, 0),
        ];
    }
}
