<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_reference' => $this->transaction_reference,
            'type' => $this->type,
            'amount' => $this->amount,
            'fee_amount' => $this->fee_amount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'currency' => $this->currency,
            'description' => $this->description,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'status' => $this->status,
            'metadata' => $this->metadata,

            // Relations
            'account' => new AccountResource($this->whenLoaded('account')),
            'processed_by' => $this->whenLoaded('processedBy', function () {
                return [
                    'id' => $this->processedBy->id,
                    'name' => $this->processedBy->name,
                ];
            }),

            // Dates
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'effective_date' => $this->effective_date?->format('Y-m-d'),

            // Métadonnées
            'is_completed' => $this->status === 'completed',
            'is_failed' => $this->status === 'failed',
            'is_pending' => $this->status === 'pending',
            'net_amount' => $this->amount - $this->fee_amount,
        ];
    }
}
