<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use App\Models\{
    Client,
    Account,
    Transaction,
    Loan,
    LoanPayment,
    User,
    Agency,
    SavingsAccount,
    TontineAccount,
    TontineCycle
};
use App\Models\StaffPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function __construct(
        private MonitoringService $monitoringService
    ) {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasRole(['administrateur_systeme', 'administrateur_reglementaire'])) {
                abort(403, 'Accès non autorisé');
            }
            return $next($request);
        });
    }

    /**
     * Afficher le tableau de bord admin avec cache
     */
    public function index(Request $request)
    {
        $period = $request->integer('period', 30);
        $cacheKey = "admin_dashboard_{$period}_" . auth()->id();

        // Cache de 5 minutes pour améliorer les performances
        $dashboardData = Cache::remember($cacheKey, 300, function () use ($period) {
            return [
                'overview' => $this->getOverviewMetrics($period),
                'growth' => $this->getGrowthMetrics($period),
                'financial' => $this->getFinancialMetrics($period),
                'operational' => $this->getOperationalMetrics(),
                'geographic' => $this->getGeographicDistribution(),
                'accounts' => $this->getAccountsBreakdown(),
                'loans' => $this->getLoansBreakdown(),
                'performance' => $this->getPerformanceIndicators($period),
            ];
        });

        // Préparer les données pour les graphiques
        $growthChartData = $this->prepareGrowthChartData($dashboardData['growth']);
        $geographicChartData = $this->prepareGeographicChartData($dashboardData['geographic']);
        $accountsChartData = $this->prepareAccountsChartData($dashboardData['accounts']);
        $loansChartData = $this->prepareLoansChartData($dashboardData['loans']);

        // Harmonize data for the institutional dashboard view
        $stats = [
            'total_clients' => $dashboardData['overview']['clients']['total'],
            'new_clients_period' => $dashboardData['overview']['clients']['new_period'],
            'total_balance' => $dashboardData['overview']['financial']['total_deposits'],
            'total_loans' => $dashboardData['overview']['financial']['loan_portfolio'],
            'active_loans_count' => $dashboardData['overview']['loans']['active'],
            
            'active_tontines_count' => $dashboardData['financial']['tontine_performance']['active_accounts'] ?? 0,
            'tontine_collections_sum' => $dashboardData['financial']['tontine_performance']['total_collected'] ?? 0,
            'tontine_payouts_sum' => $dashboardData['financial']['tontine_performance']['total_payouts'] ?? 0,
            
            'savings_deposits_sum' => $dashboardData['financial']['savings_performance']['new_deposits'] ?? 0,
            'savings_withdrawals_sum' => $dashboardData['financial']['savings_performance']['withdrawals'] ?? 0,
            'savings_net_flow' => $dashboardData['financial']['savings_performance']['net_flow'] ?? 0,
        ];

        return view('admin.dashboard', array_merge($dashboardData, [
            'stats' => $stats,
            'growthChartData' => $growthChartData,
            'geographicChartData' => $geographicChartData,
            'accountsChartData' => $accountsChartData,
            'loansChartData' => $loansChartData,
            'period' => $period,
        ]));
    }

    /**
     * Afficher l'état de santé du système
     */
    public function systemHealth()
    {
        $systemHealth = Cache::remember('system_health', 60, function () {
            return $this->monitoringService->getSystemHealth();
        });

        return view('admin.system-health', compact('systemHealth'));
    }

    /**
     * Métriques générales du système
     */
    private function getOverviewMetrics(int $period): array
    {
        $startDate = Carbon::now()->subDays($period);
        $previousStartDate = Carbon::now()->subDays($period * 2);

        // Clients
        $totalClients = Client::count();
        $newClientsPeriod = Client::where('created_at', '>=', $startDate)->count();
        $previousNewClients = Client::whereBetween('created_at', [$previousStartDate, $startDate])->count();
        $clientsGrowth = $this->calculateGrowthPercentage($newClientsPeriod, $previousNewClients);

        // Comptes
        $totalAccounts = Account::count();
        $activeAccounts = Account::where('status', 'active')->count();
        $newAccountsPeriod = Account::where('created_at', '>=', $startDate)->count();

        // Prêts
        $activeLoans = Loan::whereIn('status', ['active', 'disbursed'])->count();
        $newLoansPeriod = Loan::where('application_date', '>=', $startDate)->count();

        // Finances
        $totalDeposits = Account::where('status', 'active')->sum('balance');
        $loanPortfolio = Loan::whereIn('status', ['active', 'disbursed'])
            ->sum('outstanding_principal');
        $totalInterest = Loan::whereIn('status', ['active', 'disbursed'])
            ->sum('outstanding_interest');

        // Taux d'activation KYC
        $kycApproved = Client::where('kyc_status', 'approved')->count();
        $kycApprovalRate = $totalClients > 0 ? round(($kycApproved / $totalClients) * 100, 2) : 0;

        return [
            'clients' => [
                'total' => $totalClients,
                'new_period' => $newClientsPeriod,
                'growth' => $clientsGrowth,
                'active' => Client::active()->count(),
                'kyc_approved' => $kycApproved,
                'kyc_approval_rate' => $kycApprovalRate,
            ],
            'accounts' => [
                'total' => $totalAccounts,
                'active' => $activeAccounts,
                'new_period' => $newAccountsPeriod,
                'activation_rate' => $totalAccounts > 0 ? round(($activeAccounts / $totalAccounts) * 100, 2) : 0,
            ],
            'loans' => [
                'active' => $activeLoans,
                'new_period' => $newLoansPeriod,
                'portfolio_value' => $loanPortfolio,
                'total_interest' => $totalInterest,
            ],
            'financial' => [
                'total_deposits' => $totalDeposits,
                'loan_portfolio' => $loanPortfolio,
                'loan_to_deposit_ratio' => $totalDeposits > 0 ? round(($loanPortfolio / $totalDeposits) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Métriques de croissance journalière
     */
    private function getGrowthMetrics(int $period): array
    {
        $data = [];
        $startDate = Carbon::now()->subDays($period);

        // Optimisation: une seule requête groupée par date
        $clientsData = Client::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $accountsData = Account::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $loansData = Loan::select(
            DB::raw('DATE(application_date) as date'),
            DB::raw('COUNT(*) as count')
        )->where('application_date', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $transactionsData = Transaction::select(
            DB::raw('DATE(transaction_date) as date'),
            DB::raw('SUM(amount) as total')
        )->where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->groupBy('date')
            ->pluck('total', 'date');

        for ($i = $period; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $data[] = [
                'date' => $date,
                'new_clients' => $clientsData[$date] ?? 0,
                'new_accounts' => $accountsData[$date] ?? 0,
                'loan_applications' => $loansData[$date] ?? 0,
                'transaction_volume' => $transactionsData[$date] ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Métriques financières détaillées
     */
    private function getFinancialMetrics(int $period): array
    {
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        // 1. REVENUS (PNB)
        // Intérêts de prêts réellement perçus sur la période
        $loanInterestIncome = LoanPayment::whereBetween('paid_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('interest_amount');
            
        $loanPenalties = LoanPayment::whereBetween('paid_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('penalty_amount');

        // Commissions Tontines : 1/31 (Daily/Monthly) ou 1/52 (Weekly)
        // Selon la logique du AdminTontineController (l.298 et l.473)
        $tontineRevenue = 0;
        
        // Volume collecté pour cycles Daily/Monthly (Commission 1/31)
        $volumeStandard = TontineCycle::whereBetween('payout_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereHas('tontineAccount', function($q) {
                $q->whereIn('payment_frequency', ['daily', 'monthly']);
            })
            ->sum('target_amount');
        $tontineRevenue += ($volumeStandard / 31);

        // Volume collecté pour cycles Weekly (Commission 1/52 selon règle 52 semaines)
        $volumeWeekly = TontineCycle::whereBetween('payout_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereHas('tontineAccount', function($q) {
                $q->where('payment_frequency', 'weekly');
            })
            ->sum('target_amount');
        $tontineRevenue += ($volumeWeekly / 52);

        // Frais divers (Activation de compte, Frais de transaction, Retraits)
        $accountFees = Account::whereBetween('created_at', [$startDate, $endDate])->sum('activation_fee');
        $transactionFees = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereIn('transaction_type', ['withdrawal', 'transfer_in', 'transfer_out'])
            ->sum('fee_amount');

        $totalRevenue = $loanInterestIncome + $loanPenalties + $tontineRevenue + $accountFees + $transactionFees;

        // 2. COÛTS (Charges d'exploitation financières)
        // Intérêts versés aux épargnants sur la période
        $savingsInterestCost = DB::table('savings_accounts')
            ->join('accounts', 'accounts.id', '=', 'savings_accounts.account_id')
            ->where('accounts.status', 'active')
            ->sum(DB::raw('accounts.balance * savings_accounts.interest_rate / 100 / 365')) * $period;

        $payroll = StaffPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');

        $totalCosts = $savingsInterestCost + $payroll;
        $netProfit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;

        // Qualité du portefeuille de prêts
        $loanQuality = $this->getLoanQualityMetrics($period);

        // Liquidité
        $cashReserves = $this->calculateCashReserves();
        $loanToDepositRatio = $this->calculateLoanToDepositRatio();

        return [
            'revenue' => [
                'loan_interest' => $loanInterestIncome,
                'loan_penalties' => $loanPenalties,
                'tontine' => $tontineRevenue,
                'fees' => $accountFees + $transactionFees,
                'total' => $totalRevenue,
            ],
            'profitability' => [
                'net_profit' => $netProfit,
                'total_costs' => $totalCosts,
                'margin' => $profitMargin,
            ],
            'loan_quality' => $loanQuality,
            'liquidity' => [
                'cash_reserves' => $cashReserves,
                'loan_to_deposit_ratio' => $loanToDepositRatio,
                'available_for_lending' => $cashReserves * 0.8,
            ],
            'savings_performance' => $this->getSavingsPerformance($period),
            'tontine_performance' => $this->getTontinePerformance($period),
        ];
    }

    /**
     * Qualité du portefeuille de prêts
     */
    private function getLoanQualityMetrics(int $period): array
    {
        $startDate = Carbon::now()->subDays($period);

        // Taux d'approbation
        $totalApplications = Loan::where('application_date', '>=', $startDate)->count();
        $approvedLoans = Loan::where('application_date', '>=', $startDate)
            ->whereIn('status', ['approved', 'disbursed', 'active', 'completed'])
            ->count();
        $approvalRate = $totalApplications > 0 ? round(($approvedLoans / $totalApplications) * 100, 2) : 0;

        // Taux de défaut (Structure critique)
        $totalLoans = Loan::whereIn('status', ['active', 'completed', 'defaulted'])->count();
        $defaultedLoans = Loan::where('status', 'defaulted')->count();
        $defaultRate = $totalLoans > 0 ? round(($defaultedLoans / $totalLoans) * 100, 2) : 0;

        // Efficacité de recouvrement
        $collectionEfficiency = $this->calculateCollectionEfficiency($period);

        // Matrice PAR (Portfolio at Risk) selon les standards institutionnels
        $par1 = $this->calculatePAR(1);
        $par30 = $this->calculatePAR(30);
        $par60 = $this->calculatePAR(60);
        $par90 = $this->calculatePAR(90);

        // Distribution par niveau de risque
        $riskDistribution = Loan::whereIn('status', ['active', 'disbursed'])
            ->select('risk_level', DB::raw('COUNT(*) as count'))
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level');

        return [
            'approval_rate' => $approvalRate,
            'default_rate' => $defaultRate,
            'collection_efficiency' => $collectionEfficiency,
            'par_1' => $par1,
            'par_30' => $par30,
            'par_60' => $par60,
            'par_90' => $par90,
            'risk_distribution' => $riskDistribution,
        ];
    }

    /**
     * Calcul du PAR (Portfolio at Risk)
     * Formule : (Encours des prêts avec retard >= X jours / Encours total) * 100
     */
    private function calculatePAR(int $days): float
    {
        $cutoffDate = Carbon::now()->subDays($days);

        // La règle : Somme du capital restant dû de TOUS les crédits ayant AU MOINS une échéance en retard de X jours
        $overduePrincipal = Loan::whereIn('status', ['active', 'disbursed'])
            ->whereHas('payments', function ($query) use ($cutoffDate) {
                $query->where('status', 'overdue')
                    ->where('due_date', '<=', $cutoffDate);
            })
            ->sum('outstanding_principal');

        $totalOutstanding = Loan::whereIn('status', ['active', 'disbursed'])
            ->sum('outstanding_principal');

        return $totalOutstanding > 0 ? round(($overduePrincipal / $totalOutstanding) * 100, 2) : 0;
    }

    /**
     * Performance des comptes d'épargne
     */
    private function getSavingsPerformance(int $period): array
    {
        $startDate = Carbon::now()->subDays($period);

        $totalSavingsAccounts = SavingsAccount::count();
        $totalDeposits = SavingsAccount::join('accounts', 'savings_accounts.account_id', '=', 'accounts.id')
            ->where('accounts.status', 'active')
            ->sum('accounts.balance');

        $newDeposits = Transaction::where('transaction_type', 'deposit')
            ->where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->whereHas('account', function ($query) {
                $query->where('account_type', 'savings');
            })
            ->sum('amount');

        $withdrawals = Transaction::where('transaction_type', 'withdrawal')
            ->where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->whereHas('account', function ($query) {
                $query->where('account_type', 'savings');
            })
            ->sum('amount');

        return [
            'total_accounts' => $totalSavingsAccounts,
            'total_balance' => $totalDeposits,
            'new_deposits' => $newDeposits,
            'withdrawals' => $withdrawals,
            'net_flow' => $newDeposits - $withdrawals,
            'average_balance' => $totalSavingsAccounts > 0 ? round($totalDeposits / $totalSavingsAccounts, 2) : 0,
        ];
    }

    /**
     * Performance des tontines
     */
    private function getTontinePerformance(int $period): array
    {
        $startDate = Carbon::now()->subDays($period);

        // Comptes tontine totaux et actifs
        $totalTontineAccounts = TontineAccount::count();
        $activeTontineAccounts = Account::where('account_type', 'tontine')
            ->where('status', 'active')
            ->count();

        // Cycles actifs et complétés via TontineCycle
        $activeCycles = TontineCycle::where('status', 'active')->count();
        $completedCycles = TontineCycle::where('status', 'completed')
            ->where('end_date', '>=', $startDate)
            ->count();

        // Montants collectés et attendus via les cycles
        $totalCollected = TontineCycle::where('start_date', '>=', $startDate)
            ->sum('collected_amount');
        $totalExpected = TontineCycle::where('start_date', '>=', $startDate)
            ->sum('target_amount');

        // Taux de collecte
        $collectionRate = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100, 2) : 0;

        // Montant total des payouts sur la période
        $totalPayouts = TontineCycle::where('status', 'completed')
            ->where('payout_date', '>=', $startDate)
            ->sum('payout_amount');

        // Distribution des cycles par statut
        $cyclesByStatus = TontineCycle::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Cycles en cours avec leur progression
        $ongoingCycles = TontineCycle::where('status', 'active')
            ->select('cycle_number', 'target_amount', 'collected_amount', 'start_date', 'end_date')
            ->get()
            ->map(function ($cycle) {
                return [
                    'cycle_number' => $cycle->cycle_number,
                    'target_amount' => $cycle->target_amount,
                    'collected_amount' => $cycle->collected_amount,
                    'progress' => $cycle->target_amount > 0
                        ? round(($cycle->collected_amount / $cycle->target_amount) * 100, 2)
                        : 0,
                    'days_remaining' => Carbon::parse($cycle->end_date)->diffInDays(Carbon::now(), false),
                ];
            });

        return [
            'total_accounts' => $totalTontineAccounts,
            'active_accounts' => $activeTontineAccounts,
            'active_cycles' => $activeCycles,
            'completed_cycles' => $completedCycles,
            'total_collected' => $totalCollected,
            'total_expected' => $totalExpected,
            'collection_rate' => $collectionRate,
            'total_payouts' => $totalPayouts,
            'cycles_by_status' => $cyclesByStatus,
            'ongoing_cycles' => $ongoingCycles->take(5), // Top 5 cycles en cours
            'average_cycle_value' => $totalTontineAccounts > 0
                ? round($totalCollected / $totalTontineAccounts, 2)
                : 0,
        ];
    }

    /**
     * Métriques opérationnelles
     */
    private function getOperationalMetrics(): array
    {
        // Performance des agents
        $agentsPerformance = User::whereIn('role', ['agent_terrain', 'agent_agence'])
            ->active()
            ->with('agency:id,name')
            ->withCount(['clients as clients_registered'])
            ->orderByDesc('clients_registered')
            ->limit(10)
            ->get()
            ->map(function ($agent) {
                $clientsApproved = Client::where('registered_by', $agent->id)
                    ->where('kyc_status', 'approved')
                    ->count();

                return [
                    'id' => $agent->id,
                    'name' => $agent->full_name,
                    'role' => $agent->role,
                    'agency' => $agent->agency->name ?? 'N/A',
                    'clients_registered' => $agent->clients_registered,
                    'clients_approved' => $clientsApproved,
                    'approval_rate' => $agent->clients_registered > 0
                        ? round(($clientsApproved / $agent->clients_registered) * 100, 2)
                        : 0,
                ];
            });

        // Tâches en attente
        $pendingTasks = [
            'kyc_pending' => Client::where('kyc_status', 'pending')->count(),
            'kyc_under_review' => Client::where('kyc_status', 'under_review')->count(),
            'loan_applications' => Loan::where('status', 'pending')->count(),
            'loan_reviews' => Loan::where('status', 'under_review')->count(),
            'account_activations' => Account::where('status', 'pending')
                ->where('activation_fee_paid', true)
                ->count(),
        ];

        // Performance des agences
        $agenciesPerformance = Agency::where('is_active', true)
            ->withCount([
                'clients as total_clients',
                'users as total_agents'
            ])
            ->with(['manager:id,first_name,last_name'])
            ->get()
            ->map(function ($agency) {
                $activeAccounts = Account::whereHas('client', function ($query) use ($agency) {
                    $query->where('agency_id', $agency->id);
                })->where('status', 'active')->count();

                return [
                    'id' => $agency->id,
                    'name' => $agency->name,
                    'city' => $agency->city,
                    'manager' => $agency->manager->full_name ?? 'Non assigné',
                    'total_clients' => $agency->total_clients,
                    'total_agents' => $agency->total_agents,
                    'active_accounts' => $activeAccounts,
                ];
            });

        // Alertes système
        $systemAlerts = $this->monitoringService->getSecurityAlerts();

        return [
            'agents_performance' => $agentsPerformance,
            'pending_tasks' => $pendingTasks,
            'agencies_performance' => $agenciesPerformance,
            'system_alerts' => $systemAlerts,
        ];
    }

    /**
     * Distribution géographique
     */
    private function getGeographicDistribution(): array
    {
        $totalClients = Client::count();

        return Client::select('region', DB::raw('COUNT(*) as count'))
            ->groupBy('region')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($totalClients) {
                $accountsInRegion = Account::whereHas('client', function ($query) use ($item) {
                    $query->where('region', $item->region);
                })->where('status', 'active')->count();

                $depositsInRegion = Account::whereHas('client', function ($query) use ($item) {
                    $query->where('region', $item->region);
                })->sum('balance');

                return [
                    'region' => $item->region ?? 'Non spécifié',
                    'clients' => $item->count,
                    'percentage' => $totalClients > 0 ? round(($item->count / $totalClients) * 100, 1) : 0,
                    'active_accounts' => $accountsInRegion,
                    'total_deposits' => $depositsInRegion,
                ];
            })
            ->toArray();
    }

    /**
     * Répartition des comptes par type
     */
    private function getAccountsBreakdown(): array
    {
        $accountsByType = Account::select('account_type', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('account_type', 'status')
            ->get();

        $breakdown = [];
        foreach ($accountsByType as $item) {
            $breakdown[$item->account_type][$item->status] = $item->count;
        }

        return [
            'by_type' => Account::select('account_type', DB::raw('COUNT(*) as count'))
                ->groupBy('account_type')
                ->pluck('count', 'account_type'),
            'by_status' => Account::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'detailed' => $breakdown,
        ];
    }

    /**
     * Répartition des prêts
     */
    private function getLoansBreakdown(): array
    {
        return [
            'by_status' => Loan::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_risk_level' => Loan::whereIn('status', ['active', 'disbursed'])
                ->select('risk_level', DB::raw('COUNT(*) as count'))
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level'),
            'by_duration' => Loan::whereIn('status', ['active', 'disbursed'])
                ->select('duration_months', DB::raw('COUNT(*) as count'))
                ->groupBy('duration_months')
                ->orderBy('duration_months')
                ->pluck('count', 'duration_months'),
        ];
    }

    /**
     * Indicateurs de performance
     */
    private function getPerformanceIndicators(int $period): array
    {
        $startDate = Carbon::now()->subDays($period);

        // ROA (Return on Assets)
        $totalAssets = Account::sum('balance');
        $netIncome = Transaction::whereIn('transaction_type', ['interest', 'fee'])
            ->where('transaction_date', '>=', $startDate)
            ->where('status', 'completed')
            ->sum('amount');
        $roa = $totalAssets > 0 ? round(($netIncome / $totalAssets) * 100, 2) : 0;

        // Taux d'utilisation des ressources
        $cashReserves = $this->calculateCashReserves();
        $loanPortfolio = Loan::whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal');
        $utilizationRate = $cashReserves > 0 ? round(($loanPortfolio / $cashReserves) * 100, 2) : 0;

        return [
            'roa' => $roa,
            'utilization_rate' => $utilizationRate,
            'client_retention_rate' => $this->calculateClientRetentionRate($period),
            'average_loan_size' => Loan::whereIn('status', ['active', 'disbursed'])->avg('approved_amount'),
            'average_savings_balance' => Account::where('account_type', 'savings')
                ->where('status', 'active')
                ->avg('balance'),
        ];
    }

    /**
     * Calculer le taux de rétention des clients
     */
    private function calculateClientRetentionRate(int $period): float
    {
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        $clientsAtStart = Client::where('created_at', '<', $startDate)->count();
        $clientsAtEnd = Client::where('created_at', '<', $endDate)->count();
        $newClients = Client::whereBetween('created_at', [$startDate, $endDate])->count();

        $retainedClients = $clientsAtEnd - $newClients;

        return $clientsAtStart > 0 ? round(($retainedClients / $clientsAtStart) * 100, 2) : 0;
    }

    /**
     * Calculer l'efficacité de recouvrement
     */
    private function calculateCollectionEfficiency(int $period): float
    {
        $startDate = Carbon::now()->subDays($period);

        $expectedCollections = LoanPayment::where('due_date', '>=', $startDate)
            ->where('due_date', '<=', Carbon::now())
            ->sum('expected_amount');

        $actualCollections = LoanPayment::where('due_date', '>=', $startDate)
            ->whereNotNull('paid_date')
            ->where('paid_date', '<=', Carbon::now())
            ->sum('paid_amount');

        return $expectedCollections > 0 ? round(($actualCollections / $expectedCollections) * 100, 2) : 0;
    }

    /**
     * Calculer les réserves de liquidité
     */
    private function calculateCashReserves(): float
    {
        return Account::where('status', 'active')->sum('balance');
    }

    /**
     * Calculer le ratio prêts/dépôts
     */
    private function calculateLoanToDepositRatio(): float
    {
        $totalDeposits = $this->calculateCashReserves();
        $totalLoans = Loan::whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal');

        return $totalDeposits > 0 ? round(($totalLoans / $totalDeposits) * 100, 2) : 0;
    }

    /**
     * Calculer le pourcentage de croissance
     */
    private function calculateGrowthPercentage(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Préparer les données pour le graphique de croissance
     */
    private function prepareGrowthChartData(array $growth): array
    {
        return [
            'labels' => array_column($growth, 'date'),
            'datasets' => [
                [
                    'label' => 'Nouveaux clients',
                    'data' => array_column($growth, 'new_clients'),
                ],
                [
                    'label' => 'Nouveaux comptes',
                    'data' => array_column($growth, 'new_accounts'),
                ],
                [
                    'label' => 'Demandes de prêt',
                    'data' => array_column($growth, 'loan_applications'),
                ],
            ],
        ];
    }

    /**
     * Préparer les données pour le graphique géographique
     */
    private function prepareGeographicChartData(array $geographic): array
    {
        return [
            'labels' => array_column($geographic, 'region'),
            'clients' => array_column($geographic, 'clients'),
            'percentages' => array_column($geographic, 'percentage'),
            'deposits' => array_column($geographic, 'total_deposits'),
        ];
    }

    /**
     * Préparer les données pour le graphique des comptes
     */
    private function prepareAccountsChartData(array $accounts): array
    {
        return [
            'by_type' => [
                'labels' => array_keys($accounts['by_type']->toArray()),
                'data' => array_values($accounts['by_type']->toArray()),
            ],
            'by_status' => [
                'labels' => array_keys($accounts['by_status']->toArray()),
                'data' => array_values($accounts['by_status']->toArray()),
            ],
        ];
    }

    /**
     * Préparer les données pour le graphique des prêts
     */
    private function prepareLoansChartData(array $loans): array
    {
        return [
            'by_status' => [
                'labels' => array_keys($loans['by_status']->toArray()),
                'data' => array_values($loans['by_status']->toArray()),
            ],
            'by_risk_level' => [
                'labels' => array_keys($loans['by_risk_level']->toArray()),
                'data' => array_values($loans['by_risk_level']->toArray()),
            ],
        ];
    }

    /**
     * Nettoyer le cache du dashboard
     */
    public function clearCache()
    {
        Cache::flush();
        return redirect()->route('admin.dashboard')->with('success', 'Cache vidé avec succès');
    }
}
