<?php
namespace App\Services;

use App\Models\Client;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getAgencyPerformanceReport($agencyId, $startDate, $endDate)
    {
        $clients = Client::where('agency_id', $agencyId)->pluck('id');
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'clients' => [
                'total' => Client::where('agency_id', $agencyId)->count(),
                'new_registrations' => Client::where('agency_id', $agencyId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
                'kyc_approved' => Client::where('agency_id', $agencyId)
                    ->where('kyc_status', 'approved')
                    ->count()
            ],
            'accounts' => [
                'total_accounts' => Account::whereIn('client_id', $clients)->count(),
                'savings_accounts' => Account::whereIn('client_id', $clients)
                    ->where('account_type', 'savings')
                    ->count(),
                'tontine_accounts' => Account::whereIn('client_id', $clients)
                    ->where('account_type', 'tontine')
                    ->count(),
                'total_deposits' => Account::whereIn('client_id', $clients)->sum('balance')
            ],
            'loans' => [
                'applications' => Loan::whereIn('client_id', $clients)
                    ->whereBetween('application_date', [$startDate, $endDate])
                    ->count(),
                'approved' => Loan::whereIn('client_id', $clients)
                    ->where('status', 'approved')
                    ->whereBetween('approved_at', [$startDate, $endDate])
                    ->count(),
                'disbursed_amount' => Loan::whereIn('client_id', $clients)
                    ->where('status', 'disbursed')
                    ->whereBetween('disbursed_at', [$startDate, $endDate])
                    ->sum('approved_amount'),
                'outstanding_portfolio' => Loan::whereIn('client_id', $clients)
                    ->whereIn('status', ['active', 'disbursed'])
                    ->sum('outstanding_principal')
            ],
            'transactions' => [
                'total_volume' => Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                    ->whereIn('accounts.client_id', $clients)
                    ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                    ->where('transactions.status', 'completed')
                    ->sum('transactions.amount'),
                'deposits' => Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                    ->whereIn('accounts.client_id', $clients)
                    ->where('transactions.transaction_type', 'deposit')
                    ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                    ->count(),
                'withdrawals' => Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
                    ->whereIn('accounts.client_id', $clients)
                    ->where('transactions.transaction_type', 'withdrawal')
                    ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
                    ->count()
            ]
        ];
    }

    public function getLoanPortfolioReport($agencyId = null)
    {
        $query = Loan::query();
        
        if ($agencyId) {
            $query->whereHas('client', function($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            });
        }

        $portfolioData = $query->select([
            DB::raw('COUNT(*) as total_loans'),
            DB::raw('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_loans'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_loans'),
            DB::raw('SUM(CASE WHEN status = "defaulted" THEN 1 ELSE 0 END) as defaulted_loans'),
            DB::raw('SUM(approved_amount) as total_disbursed'),
            DB::raw('SUM(outstanding_principal) as total_outstanding'),
            DB::raw('SUM(total_paid) as total_collected'),
            DB::raw('SUM(penalty_amount) as total_penalties'),
            DB::raw('AVG(CASE WHEN status IN ("active", "disbursed") THEN days_overdue ELSE 0 END) as avg_days_overdue')
        ])->first();

        // Analyse par tranche de montant
        $amountRanges = $query->select([
            DB::raw('CASE 
                WHEN approved_amount < 100000 THEN "< 100K"
                WHEN approved_amount < 500000 THEN "100K - 500K"
                WHEN approved_amount < 1000000 THEN "500K - 1M"
                ELSE "> 1M"
            END as range'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(approved_amount) as total_amount')
        ])
        ->groupBy('range')
        ->get();

        // Tendance par mois (12 derniers mois)
        $monthlyTrend = $query->select([
            DB::raw('DATE_FORMAT(application_date, "%Y-%m") as month'),
            DB::raw('COUNT(*) as applications'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approvals'),
            DB::raw('SUM(approved_amount) as disbursed_amount')
        ])
        ->whereBetween('application_date', [Carbon::now()->subYear(), Carbon::now()])
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return [
            'summary' => $portfolioData,
            'amount_distribution' => $amountRanges,
            'monthly_trend' => $monthlyTrend,
            'quality_metrics' => [
                'approval_rate' => $portfolioData->total_loans > 0 ? 
                    round(($portfolioData->active_loans + $portfolioData->completed_loans) / $portfolioData->total_loans * 100, 2) : 0,
                'collection_rate' => $portfolioData->total_disbursed > 0 ? 
                    round($portfolioData->total_collected / $portfolioData->total_disbursed * 100, 2) : 0,
                'default_rate' => $portfolioData->total_loans > 0 ? 
                    round($portfolioData->defaulted_loans / $portfolioData->total_loans * 100, 2) : 0
            ]
        ];
    }

    public function getCollectionReport($agencyId, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::now();
        $clients = Client::where('agency_id', $agencyId)->pluck('id');

        // Échéances du jour
        $todayPayments = DB::table('loan_payments')
            ->join('loans', 'loan_payments.loan_id', '=', 'loans.id')
            ->whereIn('loans.client_id', $clients)
            ->whereDate('loan_payments.due_date', $date)
            ->select([
                'loan_payments.*',
                'loans.loan_number',
                'loans.client_id'
            ])
            ->get();

        // Collectes effectuées
        $collections = Transaction::join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->whereIn('accounts.client_id', $clients)
            ->whereDate('transactions.transaction_date', $date)
            ->where('transactions.transaction_type', 'deposit')
            ->where('transactions.status', 'completed')
            ->sum('transactions.amount');

        // Retards
        $overduePayments = DB::table('loan_payments')
            ->join('loans', 'loan_payments.loan_id', '=', 'loans.id')
            ->whereIn('loans.client_id', $clients)
            ->where('loan_payments.due_date', '<', $date)
            ->where('loan_payments.status', 'pending')
            ->count();

        return [
            'date' => $date->format('Y-m-d'),
            'expected_collections' => [
                'count' => $todayPayments->count(),
                'total_amount' => $todayPayments->sum(function($payment) {
                    return $payment->expected_amount + $payment->penalty_amount;
                })
            ],
            'actual_collections' => [
                'amount' => $collections,
                'collection_rate' => $todayPayments->sum('expected_amount') > 0 ? 
                    round($collections / $todayPayments->sum('expected_amount') * 100, 2) : 0
            ],
            'overdue_payments' => $overduePayments,
            'detailed_collections' => $todayPayments
        ];
    }

    public function exportReport($reportType, $data, $format = 'pdf')
    {
        switch ($format) {
            case 'pdf':
                return $this->exportToPDF($reportType, $data);
            case 'excel':
                return $this->exportToExcel($reportType, $data);
            default:
                return $data; // JSON par défaut
        }
    }

    private function exportToPDF($reportType, $data)
    {
        // Utiliser dompdf (doit être installé via barryvdh/laravel-dompdf)
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.' . $reportType, compact('data'));
            return $pdf->download($reportType . '_' . date('Y-m-d') . '.pdf');
        }
        
        return back()->with('error', 'Le module PDF n\'est pas configuré.');
    }

    private function exportToExcel($reportType, $data)
    {
        // L'implémentation réelle se fera via une classe Export Laravel Excel
        return back()->with('info', 'L\'export Excel pour ' . $reportType . ' est en cours de configuration.');
    }
}