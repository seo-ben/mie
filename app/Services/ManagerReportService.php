<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Loan;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManagerReportService extends ReportService
{
    /**
     * Rapport de performance de l'agence
     */
    public function getAgencyPerformanceReport($agencyId, $startDate, $endDate = null)
    {
        // Si $startDate est une période (ex: '30d') et $endDate est null
        if (is_string($startDate) && str_ends_with($startDate, 'd') && $endDate === null) {
            $days = intval($startDate);
            $startDate = Carbon::now()->subDays($days ?: 30);
            $endDate = Carbon::now();
        }

        return parent::getAgencyPerformanceReport($agencyId, $startDate, $endDate);
    }

    /**
     * Performance des agents de l'agence
     */
    public function getAgentPerformanceReport($agencyId, $period = '30d')
    {
        $days = $period === '30d' ? 30 : ($period === '7d' ? 7 : 90);
        $startDate = Carbon::now()->subDays($days);

        return User::where('agency_id', $agencyId)
            ->where('role', 'agent_terrain')
            ->get()
            ->map(function ($agent) use ($startDate) {
                return [
                    'agent_name' => $agent->full_name,
                    'new_clients' => Client::where('registered_by', $agent->id)
                        ->where('created_at', '>=', $startDate)
                        ->count(),
                    'collections_count' => Transaction::where('processed_by', $agent->id)
                        ->where('transaction_date', '>=', $startDate)
                        ->count(),
                    'collections_amount' => Transaction::where('processed_by', $agent->id)
                        ->where('transaction_date', '>=', $startDate)
                        ->sum('amount'),
                ];
            });
    }

    /**
     * Rapport financier de l'agence
     */
    public function getFinancialReport($agencyId, $period, $year)
    {
        $clients = Client::where('agency_id', $agencyId)->pluck('id');
        
        return [
            'agency_revenue' => Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->whereIn('accounts.client_id', $clients)
                ->whereYear('transactions.transaction_date', $year)
                ->sum('fee_amount'),
            'disbursements' => Loan::whereIn('client_id', $clients)
                ->whereYear('disbursed_at', $year)
                ->sum('approved_amount'),
        ];
    }

    /**
     * Rapport de conformité
     */
    public function getComplianceReport($agencyId)
    {
        return [
            'kyc_completion_rate' => $this->calculateKYCRate($agencyId),
            'pending_kyc' => Client::where('agency_id', $agencyId)->where('kyc_status', 'pending')->count(),
        ];
    }

    public function generateCustomReport($agencyId, $metrics, $dateRange, $filters)
    {
        return [
            'message' => 'Rapport personnalisé généré',
            'agency_id' => $agencyId,
            'period' => $dateRange
        ];
    }

    private function calculateKYCRate($agencyId)
    {
        $total = Client::where('agency_id', $agencyId)->count();
        $approved = Client::where('agency_id', $agencyId)->where('kyc_status', 'approved')->count();
        
        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    }
}