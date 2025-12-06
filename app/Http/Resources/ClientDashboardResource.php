<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'client_info' => [
                'id' => $this->resource['client']->id,
                'client_number' => $this->resource['client']->client_number,
                'full_name' => $this->resource['client']->full_name,
                'phone' => $this->resource['client']->phone,
                'kyc_status' => $this->resource['client']->kyc_status,
                'credit_score' => $this->resource['client']->credit_score,
                'is_eligible_for_loan' => $this->resource['client']->is_eligible_for_loan
            ],
            'accounts_summary' => [
                'total_savings' => number_format($this->resource['total_savings'], 0),
                'total_tontine' => number_format($this->resource['total_tontine'], 0),
                'total_balance' => number_format($this->resource['total_balance'], 0),
                'savings_accounts_count' => $this->resource['savings_accounts_count'],
                'tontine_accounts_count' => $this->resource['tontine_accounts_count']
            ],
            'loans_summary' => [
                'active_loans_count' => $this->resource['active_loans_count'],
                'total_outstanding' => number_format($this->resource['total_outstanding'], 0),
                'next_payment_date' => $this->resource['next_payment_date'],
                'next_payment_amount' => $this->resource['next_payment_amount'] ? 
                    number_format($this->resource['next_payment_amount'], 0) : null
            ],
            'recent_transactions' => TransactionResource::collection($this->resource['recent_transactions']),
            'tontine_progress' => $this->resource['tontine_progress'],
            'notifications_count' => $this->resource['unread_notifications_count']
        ];
    }
}