<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        private MonitoringService $monitoringService
    ) {}

    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // jours

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $this->getOverviewMetrics($period),
                'growth' => $this->getGrowthMetrics($period),
                'financial' => $this->getFinancialMetrics($period),
                'operational' => $this->getOperationalMetrics(),
                'geographic' => $this->getGeographicDistribution()
            ]
        ]);
    }

    public function systemHealth()
    {
        return response()->json([
            'success' => true,
            'data' => $this->monitoringService->getSystemHealth()
        ]);
    }

    private function getOverviewMetrics($period)
    {
        $startDate = Carbon::now()->subDays($period);

        return [
            'total_clients' => Client::count(),
            'new_clients_period' => Client::where('created_at', '>=', $startDate)->count(),
            'total_accounts' => \App\Models\Account::count(),
            'active_loans' => Loan::whereIn('status', ['active', 'disbursed'])->count(),
            'total_deposits' => \App\Models\Account::sum('balance'),
            'loan_portfolio' => Loan::whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal')
        ];
    }

    private function getGrowthMetrics($period)
    {
        $data = [];
        $startDate = Carbon::now()->subDays($period);

        // Croissance par jour
        for ($i = $period; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'new_clients' => Client::whereDate('created_at', $date)->count(),
                'new_accounts' => \App\Models\Account::whereDate('created_at', $date)->count(),
                'loan_applications' => Loan::whereDate('application_date', $date)->count(),
                'transaction_volume' => Transaction::whereDate('transaction_date', $date)
                    ->where('status', 'completed')
                    ->sum('amount')
            ];
        }

        return $data;
    }

    private function getFinancialMetrics($period)
    {
        $startDate = Carbon::now()->subDays($period);

        return [
            'revenue' => [
                'interest_income' => Transaction::where('transaction_type', 'interest')
                    ->where('transaction_date', '>=', $startDate)
                    ->sum('amount'),
                'fee_income' => Transaction::where('transaction_type', 'fee')
                    ->where('transaction_date', '>=', $startDate)
                    ->sum('amount'),
                'penalty_income' => Transaction::where('transaction_type', 'penalty')
                    ->where('transaction_date', '>=', $startDate)
                    ->sum('amount')
            ],
            'loan_quality' => [
                'approval_rate' => $this->calculateApprovalRate($period),
                'default_rate' => $this->calculateDefaultRate(),
                'collection_efficiency' => $this->calculateCollectionEfficiency($period)
            ],
            'liquidity' => [
                'cash_reserves' => $this->calculateCashReserves(),
                'loan_to_deposit_ratio' => $this->calculateLoanToDepositRatio()
            ]
        ];
    }

    private function getOperationalMetrics()
    {
        return [
            'agents_performance' => \App\Models\User::where('role', 'LIKE', 'agent%')
                ->withCount(['clients as clients_registered'])
                ->get()
                ->map(function($agent) {
                    return [
                        'name' => $agent->full_name,
                        'clients_registered' => $agent->clients_registered,
                        'agency' => $agent->agency->name ?? 'N/A'
                    ];
                }),
            'kyc_pending' => Client::where('kyc_status', 'pending')->count(),
            'loan_approvals_pending' => Loan::where('status', 'pending')->count(),
            'system_alerts' => $this->monitoringService->getSecurityAlerts()
        ];
    }

    private function getGeographicDistribution()
    {
        return Client::select('region', \DB::raw('COUNT(*) as count'))
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'region' => $item->region ?? 'Non spécifié',
                    'clients' => $item->count,
                    'percentage' => round(($item->count / Client::count()) * 100, 1)
                ];
            });
    }

    private function calculateApprovalRate($period)
    {
        $startDate = Carbon::now()->subDays($period);
        $total = Loan::where('application_date', '>=', $startDate)->count();
        $approved = Loan::where('application_date', '>=', $startDate)
            ->whereIn('status', ['approved', 'disbursed', 'active', 'completed'])
            ->count();

        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    }

    private function calculateDefaultRate()
    {
        $totalLoans = Loan::whereIn('status', ['active', 'completed', 'defaulted'])->count();
        $defaultedLoans = Loan::where('status', 'defaulted')->count();

        return $totalLoans > 0 ? round(($defaultedLoans / $totalLoans) * 100, 2) : 0;
    }

    private function calculateCollectionEfficiency($period)
    {
        $startDate = Carbon::now()->subDays($period);

        $expectedCollections = \App\Models\LoanPayment::where('due_date', '>=', $startDate)
            ->where('due_date', '<=', Carbon::now())
            ->sum('expected_amount');

        $actualCollections = \App\Models\LoanPayment::where('due_date', '>=', $startDate)
            ->where('paid_date', '<=', Carbon::now())
            ->sum('paid_amount');

        return $expectedCollections > 0 ? round(($actualCollections / $expectedCollections) * 100, 2) : 0;
    }

    private function calculateCashReserves()
    {
        return \App\Models\Account::where('account_type', 'savings')
            ->where('status', 'active')
            ->sum('balance');
    }

    private function calculateLoanToDepositRatio()
    {
        $totalDeposits = $this->calculateCashReserves();
        $totalLoans = Loan::whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal');

        return $totalDeposits > 0 ? round(($totalLoans / $totalDeposits) * 100, 2) : 0;
    }
}
