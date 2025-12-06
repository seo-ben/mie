<?php
namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class LightDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // Données client minimales
            'user' => [
                'name' => $this->resource['client']->full_name,
                'number' => $this->resource['client']->client_number,
                'kyc' => $this->resource['client']->kyc_status === 'approved'
            ],
            
            // Soldes principaux uniquement
            'balances' => [
                'total' => (int) $this->resource['total_balance'],
                'savings' => (int) $this->resource['total_savings'],
                'tontine' => (int) $this->resource['total_tontine']
            ],
            
            // Prêts actifs uniquement
            'loans' => [
                'active_count' => $this->resource['active_loans_count'],
                'next_payment' => $this->resource['next_payment_amount'] ? [
                    'date' => $this->resource['next_payment_date'],
                    'amount' => (int) $this->resource['next_payment_amount']
                ] : null
            ],
            
            // Notifications importantes seulement
            'alerts' => [
                'count' => $this->resource['urgent_notifications_count'] ?? 0,
                'has_overdue' => $this->resource['has_overdue_payments'] ?? false
            ],
            
            // Actions rapides disponibles
            'quick_actions' => [
                'can_deposit' => true,
                'can_apply_loan' => $this->resource['client']->is_eligible_for_loan,
                'can_withdraw' => $this->resource['total_balance'] > 0
            ]
        ];
    }
}