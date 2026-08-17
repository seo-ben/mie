<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\SavingsAccount;
use App\Models\StaffPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminProfitabilityController extends Controller
{
    /**
     * Dashboard centralisé de rentabilité avec tous les rapports
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30days');
        $startDate = $this->getStartDate($period, $request->get('start_date'));
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        // Section 1: Statistiques globales de rentabilité
        $profitability = $this->calculateProfitability($startDate, $endDate);

        // Section 2: Revenus par source
        $revenueBySource = $this->getRevenueBySource($startDate, $endDate);

        // Section 3: Breakdown détaillé des revenus
        $revenueBreakdown = [
            'loan_interest' => $this->getCollectedLoanInterest($startDate, $endDate),
            'loan_penalties' => $this->getLoanPenalties($startDate, $endDate),
            'account_fees' => $this->getAccountActivationFees($startDate, $endDate),
            'transaction_fees' => $this->getTransactionFees($startDate, $endDate),
            'withdrawal_fees' => $this->getWithdrawalFees($startDate, $endDate),
            'transfer_fees' => $this->getTransferFees($startDate, $endDate),
            'monthly_fees' => $this->getMonthlySavingsFees($startDate, $endDate),
            'tontine_revenue' => $this->getTontineCompletedRevenue($startDate, $endDate),
        ];
        $totalRevenue = array_sum(array_values($revenueBreakdown));
        $revenuePercentages = [];
        foreach ($revenueBreakdown as $key => $value) {
            $revenuePercentages[$key] = $totalRevenue > 0 ? round(($value / $totalRevenue) * 100, 2) : 0;
        }

        // Section 4: Rapport détaillé sur les frais
        $feesReport = [
            'account_activation' => [
                'total' => $this->getAccountActivationFees($startDate, $endDate),
                'label' => 'Frais d\'ouverture de compte'
            ],
            'transaction_fees' => [
                'total' => $this->getTransactionFees($startDate, $endDate),
                'label' => 'Frais de transaction'
            ],
            'withdrawal_fees' => [
                'total' => $this->getWithdrawalFees($startDate, $endDate),
                'label' => 'Frais de retrait'
            ],
            'transfer_fees' => [
                'total' => $this->getTransferFees($startDate, $endDate),
                'label' => 'Frais de transfert'
            ],
            'monthly_savings_fees' => [
                'total' => $this->getMonthlySavingsFees($startDate, $endDate),
                'label' => 'Frais mensuels d\'épargne'
            ],
        ];
        $totalFees = array_sum(array_column($feesReport, 'total'));

        // Section 5: Rapport sur les intérêts des prêts
        $loanInterestReport = [
            'collected_interest' => $this->getCollectedLoanInterest($startDate, $endDate),
            'pending_interest' => $this->getPendingLoanInterest($startDate, $endDate),
            'penalties' => $this->getLoanPenalties($startDate, $endDate),
            'active_portfolio' => $this->getActiveLoanPortfolio(),
            'projected_interest' => $this->getProjectedLoanInterest(),
        ];
        $loanInterestByRiskLevel = $this->getLoanInterestByRiskLevel($startDate, $endDate);

        // Section 6: Rapport sur la rentabilité des tontines
        $tontineReport = [
            'completed_cycles_revenue' => $this->getTontineCompletedRevenue($startDate, $endDate),
            'recurring_revenue' => $this->getTontineRecurringRevenue($startDate, $endDate),
            'funds_in_circulation' => $this->getTontineFundsInCirculation(),
            'retention_rate' => $this->getTontineRetentionRate($startDate, $endDate),
            'projected_revenue' => $this->getProjectedTontineRevenue(),
        ];
        $tontineByFrequency = $this->getTontineRevenueByFrequency($startDate, $endDate);
        $tontineCyclePerformance = $this->getTontineCyclePerformance($startDate, $endDate);

        // Section 7: Rapport sur les intérêts d'épargne (coûts)
        $savingsReport = [
            'paid_interest' => $this->getSavingsPaidInterest($startDate, $endDate),
            'total_savings_balance' => $this->getTotalSavingsBalance(),
            'projected_interest_cost' => $this->getProjectedSavingsInterestCost(),
            'savings_fees' => $this->getSavingsAccountFees($startDate, $endDate),
        ];
        $savingsReport['net_margin'] = $savingsReport['savings_fees'] - $savingsReport['paid_interest'];

        // Section 8: Rapport pour investisseurs - Métriques clés
        $investorMetrics = [
            'roi' => $this->calculateROI($startDate, $endDate),
            'roe' => $this->calculateROE($startDate, $endDate),
            'net_profit_margin' => $this->calculateNetProfitMargin($startDate, $endDate),
            'revenue_growth_rate' => $this->calculateRevenueGrowthRate($startDate, $endDate),
            'loan_to_deposit_ratio' => $this->calculateLoanToDepositRatio(),
            'default_rate' => $this->calculateDefaultRate($startDate, $endDate),
            'customer_acquisition_cost' => $this->calculateCustomerAcquisitionCost($startDate, $endDate),
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
        ];

        // Section 9: KPIs pour investisseurs
        $kpis = $this->getInvestorKPIs($startDate, $endDate);

        // Section 10: Comparaison avec période précédente
        $previousPeriodComparison = $this->getPreviousPeriodComparison($startDate, $endDate);

        // Section 11: Projections futures
        $projections = $this->calculateProjections();
        $projections12Months = $this->get12MonthProjections();

        // Section 12: Analyse des risques
        $riskAnalysis = $this->getRiskAnalysis();

        // Section 13: Évolution temporelle
        $revenueTimeline = $this->getRevenueTimeline($startDate, $endDate);

        // Section 14: Flux de trésorerie
        $cashFlow = [
            'inflows' => [
                'deposits' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('transaction_type', 'deposit')
                    ->where('status', 'completed')
                    ->sum('amount'),

                // Capital remboursé (sans intérêt)
                'loan_repayments' => LoanPayment::whereBetween('paid_date', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->sum('principal_amount'),

                // Tous types de frais (transactions + retrait + intérêts + pénalités)
                'fees' => $this->getTransactionFees($startDate, $endDate),

                // Tontine récurrente
                'tontine_contributions' => $this->getTontineRecurringRevenue($startDate, $endDate)['total'],
            ],

            'outflows' => [
                'withdrawals' => Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                    ->where('transaction_type', 'withdrawal')
                    ->where('status', 'completed')
                    ->sum('amount'),

                'loan_disbursements' => Loan::whereBetween('disbursed_at', [$startDate, $endDate])
                    ->whereIn('status', ['disbursed', 'active'])
                    ->sum('approved_amount'),

                'tontine_payouts' => TontineCycle::whereBetween('payout_date', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->sum('payout_amount'),

                'operational_costs' => StaffPayment::whereBetween('payment_date', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->sum('amount'),
            ],
        ];

        $cashFlow['total_inflows'] = array_sum($cashFlow['inflows']);
        $cashFlow['total_outflows'] = array_sum($cashFlow['outflows']);
        $cashFlow['net_cash_flow'] = $cashFlow['total_inflows'] - $cashFlow['total_outflows'];

        $cashFlowTimeline = $this->getCashFlowTimeline($startDate, $endDate);


        // Retourner toutes les données dans une seule vue
        return view('admin.profitability.index', compact(
            // Données de base
            'period',
            'startDate',
            'endDate',

            // Statistiques principales
            'profitability',
            'revenueBySource',
            'kpis',

            // Breakdown des revenus
            'revenueBreakdown',
            'revenuePercentages',
            'totalRevenue',

            // Rapports détaillés
            'feesReport',
            'totalFees',
            'loanInterestReport',
            'loanInterestByRiskLevel',
            'tontineReport',
            'tontineByFrequency',
            'tontineCyclePerformance',
            'savingsReport',

            // Métriques investisseurs
            'investorMetrics',
            'previousPeriodComparison',

            // Projections et analyses
            'projections',
            'projections12Months',
            'riskAnalysis',

            // Timeline et flux
            'revenueTimeline',
            'cashFlow',
            'cashFlowTimeline'
        ));
    }

    /**
     * Export rapport pour investisseurs (PDF/Excel)
     */
    public function exportInvestorReport(Request $request)
    {
        $format = $request->get('format', 'pdf');
        $period = $request->get('period', 'year');

        // Générer les données du rapport
        $reportData = $this->generateInvestorReportData($period);

        switch ($format) {
            case 'pdf':
                return $this->generatePdfReport($reportData);
            case 'excel':
                return $this->generateExcelReport($reportData);
            default:
                return back()->with('error', 'Format non supporté');
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Calculer la rentabilité globale
     */
    private function calculateProfitability($startDate, $endDate)
    {
        $totalRevenue = $this->getTotalRevenue($startDate, $endDate);
        $totalCosts = $this->getTotalCosts($startDate, $endDate);
        $netProfit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_costs' => $totalCosts,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];
    }

    /**
     * Obtenir les revenus totaux
     */
    private function getTotalRevenue($startDate, $endDate)
    {
        return $this->getCollectedLoanInterest($startDate, $endDate)
            + $this->getLoanPenalties($startDate, $endDate)
            + $this->getAccountActivationFees($startDate, $endDate)
            + $this->getTransactionFees($startDate, $endDate)
            + $this->getWithdrawalFees($startDate, $endDate)
            + $this->getTransferFees($startDate, $endDate)
            + $this->getMonthlySavingsFees($startDate, $endDate)
            + $this->getTontineCompletedRevenue($startDate, $endDate);
    }

    /**
     * Obtenir les coûts totaux
     */
    private function getTotalCosts($startDate, $endDate)
    {
        $savingsInterest = $this->getSavingsPaidInterest($startDate, $endDate);
        $payroll = StaffPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');
            
        return $savingsInterest + $payroll;
    }

    /**
     * Revenus par source
     */
    private function getRevenueBySource($startDate, $endDate)
    {
        return [
            'loan_interest' => [
                'amount' => $this->getCollectedLoanInterest($startDate, $endDate),
                'label' => 'Intérêts sur prêts',
            ],
            'loan_penalties' => [
                'amount' => $this->getLoanPenalties($startDate, $endDate),
                'label' => 'Pénalités de retard',
            ],
            'fees' => [
                'amount' => $this->getAccountActivationFees($startDate, $endDate)
                    + $this->getTransactionFees($startDate, $endDate)
                    + $this->getWithdrawalFees($startDate, $endDate)
                    + $this->getTransferFees($startDate, $endDate)
                    + $this->getMonthlySavingsFees($startDate, $endDate),
                'label' => 'Frais divers',
            ],
            'tontine' => [
                'amount' => $this->getTontineCompletedRevenue($startDate, $endDate),
                'label' => 'Revenus tontines',
            ],
        ];
    }

    /**
     * Frais d'activation de compte
     */
    private function getAccountActivationFees($startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            return Account::whereBetween('created_at', [$startDate, $endDate])
                ->sum('activation_fee');
        }
        return Account::sum('activation_fee');
    }

    /**
     * Frais de transaction généraux
     */
    private function getTransactionFees($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('fee_amount');
    }

    /**
     * Frais de retrait
     */
    private function getWithdrawalFees($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('fee_amount');
    }

    /**
     * Frais de transfert
     */
    private function getTransferFees($startDate, $endDate)
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereIn('transaction_type', ['transfer_in', 'transfer_out'])
            ->where('status', 'completed')
            ->sum('fee_amount');
    }

    /**
     * Frais mensuels d'épargne
     */
    private function getMonthlySavingsFees($startDate, $endDate)
    {
        $months = $startDate->diffInMonths($endDate) + 1;
        return SavingsAccount::whereHas('account', function($q) {
                $q->where('status', 'active');
            })
            ->sum('monthly_fee') * $months;
    }

    /**
     * Intérêts collectés sur les prêts
     */
    private function getCollectedLoanInterest($startDate, $endDate)
    {
        return LoanPayment::whereBetween('paid_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('interest_amount');
    }

    /**
     * Intérêts en attente sur les prêts
     */
    private function getPendingLoanInterest($startDate, $endDate)
    {
        return LoanPayment::whereBetween('due_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('interest_amount');
    }

    /**
     * Pénalités de retard sur prêts
     */
    private function getLoanPenalties($startDate, $endDate)
    {
        return LoanPayment::whereBetween('paid_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('penalty_amount');
    }

    /**
     * Portfolio de prêts actifs
     */
    private function getActiveLoanPortfolio()
    {
        return Loan::whereIn('status', ['disbursed', 'active'])
            ->sum('outstanding_principal');
    }

    /**
     * Intérêts projetés sur prêts actifs
     */
    private function getProjectedLoanInterest()
    {
        return Loan::whereIn('status', ['disbursed', 'active'])
            ->sum('outstanding_interest');
    }

    /**
     * Intérêts sur prêts par niveau de risque
     */
    private function getLoanInterestByRiskLevel($startDate, $endDate)
    {
        return LoanPayment::from('loan_payments as lp')
            ->whereBetween('lp.paid_date', [$startDate, $endDate])
            ->where('lp.status', 'paid')
            ->join('loans as l', 'lp.loan_id', '=', 'l.id')
            ->selectRaw('l.risk_level, SUM(lp.interest_amount) as total_interest')
            ->groupBy('l.risk_level')
            ->get()
            ->pluck('total_interest', 'risk_level');
    }

    /**
     * Revenus des cycles de tontine complétés
     */
    private function getTontineCompletedRevenue($startDate, $endDate)
    {
        $commissionRate = 0.02;
        return TontineCycle::whereBetween('payout_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('collected_amount') * $commissionRate;
    }

    /**
     * Revenus récurrents des tontines
     */
    private function getTontineRecurringRevenue($startDate, $endDate)
    {
        $daily = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'deposit')
            ->whereHas('account', function($q) {
                $q->where('account_type', 'tontine')
                  ->whereHas('tontineAccount', function($q2) {
                      $q2->where('payment_frequency', 'daily');
                  });
            })
            ->where('status', 'completed')
            ->sum('amount');

        $weekly = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'deposit')
            ->whereHas('account', function($q) {
                $q->where('account_type', 'tontine')
                  ->whereHas('tontineAccount', function($q2) {
                      $q2->where('payment_frequency', 'weekly');
                  });
            })
            ->where('status', 'completed')
            ->sum('amount');

        $monthly = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('transaction_type', 'deposit')
            ->whereHas('account', function($q) {
                $q->where('account_type', 'tontine')
                  ->whereHas('tontineAccount', function($q2) {
                      $q2->where('payment_frequency', 'monthly');
                  });
            })
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'total' => $daily + $weekly + $monthly,
        ];
    }

    /**
     * Fonds en circulation dans les tontines
     */
    private function getTontineFundsInCirculation()
    {
        return Account::where('account_type', 'tontine')
            ->where('status', 'active')
            ->sum('balance');
    }

    /**
     * Taux de rétention des tontines
     */
    private function getTontineRetentionRate($startDate, $endDate)
    {
        $totalCycles = TontineCycle::whereBetween('start_date', [$startDate, $endDate])->count();
        $completedCycles = TontineCycle::whereBetween('payout_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        return $totalCycles > 0 ? round(($completedCycles / $totalCycles) * 100, 2) : 0;
    }

    /**
     * Revenus projetés des tontines
     */
    private function getProjectedTontineRevenue()
    {
        $activeTontines = TontineAccount::whereHas('account', function($q) {
                $q->where('status', 'active');
            })
            ->get();

        $projectedRevenue = 0;
        $commissionRate = 0.02;

        foreach ($activeTontines as $tontine) {
            $remaining = $tontine->total_expected - $tontine->total_paid;
            $projectedRevenue += $remaining * $commissionRate;
        }

        return $projectedRevenue;
    }

    /**
     * Revenus tontine par fréquence
     */
    private function getTontineRevenueByFrequency($startDate, $endDate)
    {
        return TontineAccount::join('accounts', 'tontine_accounts.account_id', '=', 'accounts.id')
            ->join('transactions', 'transactions.account_id', '=', 'accounts.id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->where('transactions.transaction_type', 'deposit')
            ->where('transactions.status', 'completed')
            ->selectRaw('tontine_accounts.payment_frequency, SUM(transactions.amount) as total')
            ->groupBy('tontine_accounts.payment_frequency')
            ->get()
            ->pluck('total', 'payment_frequency');
    }

    /**
     * Performance des cycles de tontine
     */
    private function getTontineCyclePerformance($startDate, $endDate)
    {
        return TontineCycle::whereBetween('start_date', [$startDate, $endDate])
            ->selectRaw('
                status,
                COUNT(*) as count,
                AVG(collected_amount) as avg_collected,
                AVG(target_amount) as avg_target,
                AVG(CASE WHEN target_amount > 0 THEN (collected_amount / target_amount) * 100 ELSE 0 END) as avg_completion_rate
            ')
            ->groupBy('status')
            ->get();
    }

    /**
     * Intérêts versés aux épargnants
     */
    private function getSavingsPaidInterest($startDate, $endDate)
    {
        return DB::table('savings_accounts')
            ->join('accounts', 'accounts.id', '=', 'savings_accounts.account_id')
            ->where('accounts.status', 'active')
            ->whereBetween('savings_accounts.last_interest_calculated', [$startDate, $endDate])
            ->sum(DB::raw('accounts.balance * savings_accounts.interest_rate / 100 / 12'));
    }

    /**
     * Solde total d'épargne
     */
    private function getTotalSavingsBalance()
    {
        return Account::where('account_type', 'savings')
            ->where('status', 'active')
            ->sum('balance');
    }

    /**
     * Coût projeté des intérêts d'épargne
     */
    private function getProjectedSavingsInterestCost()
    {
        $totalBalance = $this->getTotalSavingsBalance();
        $avgRate = SavingsAccount::avg('interest_rate') ?? 2.5;
        return ($totalBalance * $avgRate / 100);
    }

    /**
     * Frais collectés sur comptes d'épargne
     */
    private function getSavingsAccountFees($startDate, $endDate)
    {
        $months = $startDate->diffInMonths($endDate) + 1;
        return SavingsAccount::whereHas('account', function($q) {
                $q->where('status', 'active');
            })
            ->sum('monthly_fee') * $months;
    }

    /**
     * Calculer le ROI
     */
    private function calculateROI($startDate, $endDate)
    {
        $revenue = $this->getTotalRevenue($startDate, $endDate);
        $costs = $this->getTotalCosts($startDate, $endDate);
        $netProfit = $revenue - $costs;
        $investment = 10000000;

        return $investment > 0 ? round(($netProfit / $investment) * 100, 2) : 0;
    }

    /**
     * Calculer le ROE
     */
    private function calculateROE($startDate, $endDate)
    {
        $netProfit = $this->getTotalRevenue($startDate, $endDate) - $this->getTotalCosts($startDate, $endDate);
        $equity = $this->getTotalSavingsBalance() + $this->getTontineFundsInCirculation();

        return $equity > 0 ? round(($netProfit / $equity) * 100, 2) : 0;
    }

    /**
     * Marge bénéficiaire nette
     */
    private function calculateNetProfitMargin($startDate, $endDate)
    {
        $revenue = $this->getTotalRevenue($startDate, $endDate);
        $costs = $this->getTotalCosts($startDate, $endDate);
        $netProfit = $revenue - $costs;

        return $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0;
    }

    /**
     * Taux de croissance des revenus
     */
    private function calculateRevenueGrowthRate($startDate, $endDate)
    {
        $currentRevenue = $this->getTotalRevenue($startDate, $endDate);
        $days = $startDate->diffInDays($endDate);
        $prevStart = (clone $startDate)->subDays($days);
        $prevEnd = clone $startDate;
        $previousRevenue = $this->getTotalRevenue($prevStart, $prevEnd);

        return $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0;
    }

    /**
     * Ratio prêts/dépôts
     */
    private function calculateLoanToDepositRatio()
    {
        $totalLoans = Loan::whereIn('status', ['disbursed', 'active'])->sum('approved_amount');
        $totalDeposits = Account::where('status', 'active')->sum('balance');

        return $totalDeposits > 0 ? round(($totalLoans / $totalDeposits) * 100, 2) : 0;
    }

    /**
     * Taux de défaut
     */
    private function calculateDefaultRate($startDate, $endDate)
    {
        $totalLoans = Loan::whereBetween('disbursed_at', [$startDate, $endDate])->count();
        $defaultedLoans = Loan::whereBetween('disbursed_at', [$startDate, $endDate])
            ->where('status', 'defaulted')
            ->count();

        return $totalLoans > 0 ? round(($defaultedLoans / $totalLoans) * 100, 2) : 0;
    }

    /**
     * Coût d'acquisition client
     */
    private function calculateCustomerAcquisitionCost($startDate, $endDate)
    {
        $marketingCosts = 500000;
        $newClients = DB::table('clients')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return $newClients > 0 ? round($marketingCosts / $newClients, 2) : 0;
    }

    /**
     * Valeur vie client
     */
    private function calculateCustomerLifetimeValue()
    {
        $totalClients = DB::table('clients')->where('kyc_status', 'approved')->count();
        if ($totalClients == 0) return 0;

        $totalRevenue = $this->getTotalRevenue(now()->subYear(), now());
        $avgRevenuePerClient = $totalRevenue / $totalClients;
        $avgLifetime = 3;

        return round($avgRevenuePerClient * $avgLifetime, 2);
    }

    /**
     * Comparaison avec période précédente
     */
    private function getPreviousPeriodComparison($startDate, $endDate)
    {
        $days = $startDate->diffInDays($endDate);
        $prevStart = (clone $startDate)->subDays($days);
        $prevEnd = clone $startDate;

        $current = [
            'revenue' => $this->getTotalRevenue($startDate, $endDate),
            'costs' => $this->getTotalCosts($startDate, $endDate),
            'profit' => 0,
        ];
        $current['profit'] = $current['revenue'] - $current['costs'];

        $previous = [
            'revenue' => $this->getTotalRevenue($prevStart, $prevEnd),
            'costs' => $this->getTotalCosts($prevStart, $prevEnd),
            'profit' => 0,
        ];
        $previous['profit'] = $previous['revenue'] - $previous['costs'];

        return [
            'current' => $current,
            'previous' => $previous,
            'revenue_change' => $previous['revenue'] > 0
                ? round((($current['revenue'] - $previous['revenue']) / $previous['revenue']) * 100, 2)
                : 0,
            'profit_change' => $previous['profit'] > 0
                ? round((($current['profit'] - $previous['profit']) / $previous['profit']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Projections sur 12 mois
     */
    private function get12MonthProjections()
    {
        $last3Months = now()->subMonths(3);
        $avgMonthlyRevenue = $this->getTotalRevenue($last3Months, now()) / 3;
        $growthRate = 0.05;

        $projections = [];
        for ($i = 1; $i <= 12; $i++) {
            $projectedRevenue = $avgMonthlyRevenue * pow(1 + $growthRate, $i);
            $projectedCosts = $projectedRevenue * 0.3;
            $projectedProfit = $projectedRevenue - $projectedCosts;

            $projections[] = [
                'month' => now()->addMonths($i)->format('M Y'),
                'revenue' => round($projectedRevenue, 2),
                'costs' => round($projectedCosts, 2),
                'profit' => round($projectedProfit, 2),
            ];
        }

        return $projections;
    }

    /**
     * Analyse des risques
     */
    private function getRiskAnalysis()
    {
        return [
            'loan_concentration' => $this->getLoanConcentrationRisk(),
            'portfolio_quality' => $this->getPortfolioQuality(),
            'liquidity_ratio' => $this->getLiquidityRatio(),
            'risk_exposure' => $this->getRiskExposure(),
            'npl_ratio' => $this->getNPLRatio(),
            'par_1' => $this->calculatePAR(1),
            'par_30' => $this->calculatePAR(30),
            'par_60' => $this->calculatePAR(60),
            'par_90' => $this->calculatePAR(90),
        ];
    }

    /**
     * Calcul du PAR (Portfolio at Risk) pour la rentabilité
     */
    private function calculatePAR(int $days): float
    {
        $cutoffDate = Carbon::now()->subDays($days);

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
     * Risque de concentration des prêts
     */
    private function getLoanConcentrationRisk()
    {
        $totalPortfolio = Loan::whereIn('status', ['disbursed', 'active'])->sum('approved_amount');
        if ($totalPortfolio == 0) return 0;

        $top10 = Loan::whereIn('status', ['disbursed', 'active'])
            ->orderBy('approved_amount', 'desc')
            ->limit(10)
            ->sum('approved_amount');

        return round(($top10 / $totalPortfolio) * 100, 2);
    }

    /**
     * Qualité du portefeuille
     */
    private function getPortfolioQuality()
    {
        $totalLoans = Loan::whereIn('status', ['disbursed', 'active'])->count();
        if ($totalLoans == 0) return 100;

        $performingLoans = Loan::whereIn('status', ['disbursed', 'active'])
            ->where('risk_level', 'low')
            ->count();

        return round(($performingLoans / $totalLoans) * 100, 2);
    }

    /**
     * Ratio de liquidité
     */
    private function getLiquidityRatio()
    {
        $cashAvailable = Account::where('status', 'active')->sum('balance');
        $shortTermLiabilities = Loan::whereIn('status', ['disbursed', 'active'])
            ->sum('outstanding_principal');

        return $shortTermLiabilities > 0
            ? round(($cashAvailable / $shortTermLiabilities) * 100, 2)
            : 100;
    }

    /**
     * Exposition par niveau de risque
     */
    private function getRiskExposure()
    {
        $byRisk = Loan::whereIn('status', ['disbursed', 'active'])
            ->selectRaw('risk_level, SUM(outstanding_principal) as exposure')
            ->groupBy('risk_level')
            ->get()
            ->pluck('exposure', 'risk_level');

        $total = $byRisk->sum();

        return [
            'low' => $total > 0 ? round((($byRisk->get('low', 0) / $total) * 100), 2) : 0,
            'medium' => $total > 0 ? round((($byRisk->get('medium', 0) / $total) * 100), 2) : 0,
            'high' => $total > 0 ? round((($byRisk->get('high', 0) / $total) * 100), 2) : 0,
            'very_high' => $total > 0 ? round((($byRisk->get('very_high', 0) / $total) * 100), 2) : 0,
        ];
    }

    /**
     * Non-Performing Loans Ratio
     */
    private function getNPLRatio()
    {
        $totalLoans = Loan::whereIn('status', ['disbursed', 'active'])->sum('approved_amount');
        if ($totalLoans == 0) return 0;

        $overdueLoans = Loan::whereIn('status', ['disbursed', 'active'])
            ->whereHas('payments', function($q) {
                $q->where('status', 'overdue')
                  ->where('due_date', '<', now()->subDays(90));
            })
            ->sum('outstanding_principal');

        return round(($overdueLoans / $totalLoans) * 100, 2);
    }

    /**
     * Timeline des revenus (version simple)
     */
   private function getRevenueTimeline($startDate, $endDate)
    {
        $timeline = [];

        // 1. Revenus des prêts (intérêts + pénalités)
        $loanRevenues = LoanPayment::whereBetween('paid_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->selectRaw("DATE_FORMAT(paid_date, '%Y-%m-%d') as date, SUM(interest_amount + penalty_amount) as revenue")
            ->groupBy('date')
            ->pluck('revenue', 'date');

        // 2. Revenus des transactions (frais + frais retrait)
        $transactionRevenues = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m-%d') as date, SUM(fee_amount + withdrawal_fee) as revenue")
            ->groupBy('date')
            ->pluck('revenue', 'date');

        // 3. Revenus activation comptes (tous, sans condition)
        $activationRevenues = Account::whereBetween('activated_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(activated_at, '%Y-%m-%d') as date, SUM(activation_fee) as revenue")
            ->groupBy('date')
            ->pluck('revenue', 'date');

        // 4. Revenus tontines (commissions 2%)
        $tontineRevenues = TontineCycle::whereBetween('payout_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw("DATE_FORMAT(payout_date, '%Y-%m-%d') as date, SUM(collected_amount) * 0.02 as revenue")
            ->groupBy('date')
            ->pluck('revenue', 'date');

        // 5. Fusionner toutes les dates
        $allDates = collect(array_unique(array_merge(
            $loanRevenues->keys()->toArray(),
            $transactionRevenues->keys()->toArray(),
            $activationRevenues->keys()->toArray(),
            $tontineRevenues->keys()->toArray()
        )))->sort();

        // 6. Construction de la timeline finale
        foreach ($allDates as $date) {
            $timeline[] = [
                'date' => $date,
                'revenue' => round(
                    ($loanRevenues[$date] ?? 0)
                    + ($transactionRevenues[$date] ?? 0)
                    + ($activationRevenues[$date] ?? 0)
                    + ($tontineRevenues[$date] ?? 0),
                    2
                )
            ];
        }

        return $timeline;
    }

    /**
     * KPIs pour investisseurs
     */
    private function getInvestorKPIs($startDate, $endDate)
    {
        return [
            'total_clients' => DB::table('clients')->where('kyc_status', 'approved')->count(),
            'active_accounts' => Account::where('status', 'active')->count(),
            'total_deposits' => Account::where('status', 'active')->sum('balance'),
            'active_loans' => Loan::whereIn('status', ['disbursed', 'active'])->count(),
            'loan_portfolio' => Loan::whereIn('status', ['disbursed', 'active'])->sum('approved_amount'),
            'total_revenue' => $this->getTotalRevenue($startDate, $endDate),
            'net_profit' => $this->getTotalRevenue($startDate, $endDate) - $this->getTotalCosts($startDate, $endDate),
            'profit_margin' => $this->calculateNetProfitMargin($startDate, $endDate),
            'roi' => $this->calculateROI($startDate, $endDate),
            'default_rate' => $this->calculateDefaultRate($startDate, $endDate),
        ];
    }

    /**
     * Projections futures
     */
    private function calculateProjections()
    {
        return [
            'next_month_revenue' => $this->projectNextMonthRevenue(),
            'next_quarter_revenue' => $this->projectNextQuarterRevenue(),
            'annual_revenue' => $this->projectAnnualRevenue(),
            'growth_potential' => $this->calculateGrowthPotential(),
        ];
    }

    /**
     * Projection du revenu du mois prochain
     */
    private function projectNextMonthRevenue()
    {
        $lastMonth = $this->getTotalRevenue(now()->subMonth(), now());
        $avgGrowth = 0.05;

        return round($lastMonth * (1 + $avgGrowth), 2);
    }

    /**
     * Projection du revenu du prochain trimestre
     */
    private function projectNextQuarterRevenue()
    {
        $last3Months = $this->getTotalRevenue(now()->subMonths(3), now());
        $avgGrowth = 0.05;

        return round($last3Months * (1 + $avgGrowth * 3), 2);
    }

    /**
     * Projection du revenu annuel
     */
    private function projectAnnualRevenue()
    {
        $last12Months = $this->getTotalRevenue(now()->subYear(), now());
        $avgGrowth = 0.15;

        return round($last12Months * (1 + $avgGrowth), 2);
    }

    /**
     * Potentiel de croissance
     */
    private function calculateGrowthPotential()
    {
        $currentClients = DB::table('clients')->where('kyc_status', 'approved')->count();
        $targetMarket = 10000;

        $potential = $targetMarket > 0
            ? round((($targetMarket - $currentClients) / $targetMarket) * 100, 2)
            : 0;

        return [
            'current_clients' => $currentClients,
            'target_market' => $targetMarket,
            'growth_potential' => $potential,
        ];
    }

    /**
     * Timeline du cash flow
     */
    private function getCashFlowTimeline($startDate, $endDate)
    {
        $timeline = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $inflows = Transaction::whereBetween('transaction_date', [$dayStart, $dayEnd])
                ->whereIn('transaction_type', ['deposit'])
                ->where('status', 'completed')
                ->sum('amount');

            $outflows = Transaction::whereBetween('transaction_date', [$dayStart, $dayEnd])
                ->whereIn('transaction_type', ['withdrawal'])
                ->where('status', 'completed')
                ->sum('amount');

            $timeline[] = [
                'date' => $current->format('Y-m-d'),
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
            ];

            $current->addDay();
        }

        return $timeline;
    }

    /**
     * Générer les données du rapport investisseur
     */
    private function generateInvestorReportData($period)
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'profitability' => $this->calculateProfitability($startDate, $endDate),
            'kpis' => $this->getInvestorKPIs($startDate, $endDate),
            'revenue_breakdown' => $this->getRevenueBySource($startDate, $endDate),
            'projections' => $this->get12MonthProjections(),
            'risk_analysis' => $this->getRiskAnalysis(),
            'comparison' => $this->getPreviousPeriodComparison($startDate, $endDate),
        ];
    }

    /**
     * Générer un rapport PDF
     */
    private function generatePdfReport($reportData)
    {
        // Nécessite une bibliothèque comme DomPDF ou TCPDF
        // Exemple avec DomPDF
        try {
            $pdf = \PDF::loadView('admin.profitability.pdf-report', $reportData);
            return $pdf->download('rapport-investisseur-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Générer un rapport Excel
     */
    private function generateExcelReport($reportData)
    {
        // Nécessite PhpSpreadsheet ou Laravel Excel
        try {
            return \Excel::download(new \App\Exports\InvestorReportExport($reportData),
                'rapport-investisseur-' . date('Y-m-d') . '.xlsx');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la génération du fichier Excel: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir la date de début selon la période
     */
    private function getStartDate($period, $customStart = null)
    {
        return match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            'year' => now()->subYear(),
            'custom' => $customStart ? Carbon::parse($customStart) : now()->subDays(30),
            default => now()->subDays(30),
        };
    }
}
