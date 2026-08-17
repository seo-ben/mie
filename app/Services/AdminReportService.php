<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\Account;
use App\Models\Agency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminReportService
{
    /**
     * Dashboard global avec toutes les métriques
     */
    public function getGlobalDashboard($period = '30d')
    {
        $days = $period === '30d' ? 30 : ($period === '7d' ? 7 : 90);
        $startDate = Carbon::now()->subDays($days);

        return Cache::remember("admin_global_dashboard_{$period}", 300, function () use ($startDate) {
            return [
                'overview' => [
                    'total_clients' => Client::count(),
                    'active_loans_value' => Loan::where('status', 'active')->sum('outstanding_principal'),
                    'total_deposits' => Account::sum('balance'),
                    'cash_in_hand' => Agency::sum('cash_limit'), // Simplification
                ],
                'growth' => [
                    'new_clients' => Client::where('created_at', '>=', $startDate)->count(),
                    'loan_disbursements' => Loan::where('disbursed_at', '>=', $startDate)->sum('approved_amount'),
                ],
                'risk' => [
                    'par_30' => $this->calculatePAR(30),
                    'npl_ratio' => $this->calculateNPLRatio(),
                ]
            ];
        });
    }

    /**
     * Rapport réglementaire
     */
    public function generateRegulatoryReport($type, $date)
    {
        // Logique simplifiée pour simulation
        return [
            'report_name' => "Rapport " . strtoupper($type),
            'period' => $date,
            'metrics' => [
                'liquidity_ratio' => rand(80, 120),
                'solvency_ratio' => rand(15, 25),
                'portfolio_at_risk' => rand(2, 8),
            ]
        ];
    }

    /**
     * Piste d'audit
     */
    public function getAuditTrail($filters)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->paginate(50);
    }

    /**
     * Performance financière
     */
    public function getFinancialPerformance($period, $year)
    {
        return [
            'revenue' => Transaction::whereYear('transaction_date', $year)->sum('fee_amount'),
            'expenses' => 0, // À implémenter si table dépenses existe
            'net_income' => 0,
            'yield_on_portfolio' => rand(10, 18)
        ];
    }

    /**
     * Analyse de risque
     */
    public function getRiskAnalysis()
    {
        return [
            'portfolio_quality' => $this->getPortfolioQuality(),
            'concentration_risk' => $this->getConcentrationRisk(),
        ];
    }

    public function getGrowthTrends($period)
    {
        // Données mensuelles pour graphiques
        return Transaction::selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->where('transaction_date', '>=', now()->subYear())
            ->groupBy('month')
            ->get();
    }

    public function getPortfolioQuality()
    {
        return [
            'healthy' => Loan::where('status', 'active')->where('days_overdue', 0)->count(),
            'overdue_1_30' => Loan::where('days_overdue', '>', 0)->where('days_overdue', '<=', 30)->count(),
            'at_risk' => Loan::where('days_overdue', '>', 30)->count(),
        ];
    }

    private function calculatePAR($days)
    {
        $outstandingAtRisk = Loan::where('days_overdue', '>=', $days)->sum('outstanding_principal');
        $totalPortfolio = Loan::whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal');
        
        return $totalPortfolio > 0 ? round(($outstandingAtRisk / $totalPortfolio) * 100, 2) : 0;
    }

    private function calculateNPLRatio()
    {
        return $this->calculatePAR(90);
    }

    private function getConcentrationRisk()
    {
        // Top 10 clients concentration
        return 15.5; // Exemple statique
    }

    public function exportReport($reportType, $format, $params)
    {
        // Appel au service d'exportation (à implémenter ou utiliser le ReportService existant)
        return "storage/reports/{$reportType}_{$format}"; 
    }

    public function scheduleReport($schedule)
    {
        return $schedule;
    }

    /**
     * Rapport Balance Agée Analytique du Portefeuille de Crédits
     */
    public function getRegulatoryAgingReport()
    {
        $loanTypes = [
            'CREDIT ORDINAIRE',
            'CREDIT SUR TONTINE',
            'FNFI AGRISEF',
            'FNFI AGRISEF ACOMPAGNEMENT SPC',
            'FNFI AGRISEF INTEGRE',
            'FNFI APSEF INTEGRER'
        ];

        $data = [];
        $totals = [
            'encours' => 0,
            'retard_1_30' => 0,
            'retard_31_90' => 0,
            'retard_91_180' => 0,
            'retard_181_360' => 0,
            'retard_plus_360' => 0,
            'total_retard' => 0,
            'nbre_benef' => 0,
            'dont_dirigeants' => 0,
        ];

        foreach ($loanTypes as $type) {
            $loans = Loan::where('loan_type', $type)
                ->whereIn('status', ['active', 'disbursed', 'defaulted'])
                ->with('client')
                ->get();

            $row = [
                'type' => $type,
                'encours' => $loans->sum('outstanding_principal'),
                'retard_1_30' => 0,
                'retard_31_90' => 0,
                'retard_91_180' => 0,
                'retard_181_360' => 0,
                'retard_plus_360' => 0,
                'total_retard' => 0,
                'nbre_benef' => $loans->pluck('client_id')->unique()->count(),
                'dont_dirigeants' => $loans->filter(fn($l) => optional($l->client)->is_leader_or_elected)->pluck('client_id')->unique()->count(),
            ];

            foreach ($loans as $loan) {
                $days = $loan->days_overdue;
                if ($days > 0) {
                    $amount = $loan->outstanding_principal;
                    
                    if ($days <= 30) $row['retard_1_30'] += $amount;
                    elseif ($days <= 90) $row['retard_31_90'] += $amount;
                    elseif ($days <= 180) $row['retard_91_180'] += $amount;
                    elseif ($days <= 360) $row['retard_181_360'] += $amount;
                    else $row['retard_plus_360'] += $amount;
                }
            }
            
            $row['total_retard'] = $row['retard_1_30'] + $row['retard_31_90'] + $row['retard_91_180'] + $row['retard_181_360'] + $row['retard_plus_360'];

            $data[] = $row;

            // Totaux
            $totals['encours'] += $row['encours'];
            $totals['retard_1_30'] += $row['retard_1_30'];
            $totals['retard_31_90'] += $row['retard_31_90'];
            $totals['retard_91_180'] += $row['retard_91_180'];
            $totals['retard_181_360'] += $row['retard_181_360'];
            $totals['retard_plus_360'] += $row['retard_plus_360'];
            $totals['total_retard'] += $row['total_retard'];
            $totals['nbre_benef'] += $row['nbre_benef'];
            $totals['dont_dirigeants'] += $row['dont_dirigeants'];
        }

        // PAR calculations for footer summary
        $summary = [
            'par_1d' => $totals['encours'] > 0 ? ($totals['total_retard'] / $totals['encours']) * 100 : 0,
            'par_30d' => $totals['encours'] > 0 ? (($totals['total_retard'] - $totals['retard_1_30']) / $totals['encours']) * 100 : 0,
            'par_90d' => $totals['encours'] > 0 ? (($totals['total_retard'] - $totals['retard_1_30'] - $totals['retard_31_90']) / $totals['encours']) * 100 : 0,
            'par_180d' => $totals['encours'] > 0 ? (($totals['retard_181_360'] + $totals['retard_plus_360']) / $totals['encours']) * 100 : 0,
        ];

        return [
            'data' => $data,
            'totals' => $totals,
            'summary' => $summary
        ];
    }
}