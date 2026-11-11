<?php

namespace App\Http\Controllers\Web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\TontineAccount;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgentDashboardController extends Controller
{
    /**
     * Afficher le tableau de bord de l'agent
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $agentId = $user->id;

        // Période par défaut : aujourd'hui
        $period = $request->get('period', 'today');

        $data = [
            'overview' => $this->getOverviewStats($agentId, $period),
            'collections' => $this->getCollectionStats($agentId, $period),
            'clients' => $this->getClientStats($agentId),
            'reminders' => $this->getReminders($agentId),
            'recentActivities' => $this->getRecentActivities($agentId),
            'chartData' => $this->getChartData($agentId),
            'currentPeriod' => $period,
            'agent' => [
                'name' => $user->full_name,
                'agency' => $user->agency->name ?? 'N/A',
            ],
        ];

        return view('agent.dashboard.index', $data);
    }

    /**
     * Statistiques d'ensemble pour l'agent
     */
    private function getOverviewStats($agentId, $period)
    {
        $dateRange = $this->getDateRange($period);

        // Clients enregistrés PAR cet agent uniquement (y compris en attente de validation KYC)
        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        // Comptes des clients de l'agent
        $accountIds = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->pluck('id');

        return [
            // Solde total des comptes gérés par l'agent
            'total_balance' => Account::whereIn('id', $accountIds)->sum('balance'),

            // Nombre total de clients enregistrés par cet agent
            'total_clients' => $clientIds->count(),

            // Clients actifs (avec transaction dans la période)
            'active_clients' => Transaction::whereIn('account_id', $accountIds)
                ->whereBetween('created_at', $dateRange)
                ->distinct('account_id')
                ->count('account_id'),

            // Nouveaux comptes ouverts dans la période
            'new_accounts' => Account::whereIn('client_id', $clientIds)
                ->whereBetween('created_at', $dateRange)
                ->count(),

            // Comptes d'épargne actifs
            'savings_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'savings')
                ->where('status', 'active')
                ->count(),

            // Comptes tontine actifs
            'tontine_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')
                ->count(),

            // Prêts actifs
            'active_loans' => Loan::whereIn('client_id', $clientIds)
                ->whereIn('status', ['active', 'disbursed'])
                ->count(),

            // Montant total des prêts actifs
            'total_loan_amount' => Loan::whereIn('client_id', $clientIds)
                ->whereIn('status', ['active', 'disbursed'])
                ->sum('approved_amount'),
        ];
    }

    /**
     * Statistiques de collecte pour l'agent
     */
    private function getCollectionStats($agentId, $period)
    {
        $dateRange = $this->getDateRange($period);

        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        $accountIds = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->pluck('id');

        // Transactions de la période
        $transactions = Transaction::whereIn('account_id', $accountIds)
            ->whereBetween('created_at', $dateRange)
            ->where('status', 'completed');

        // Dépôts par type
        $depositsByType = (clone $transactions)
            ->where('transaction_type', 'like', '%deposit%')
            ->select('transaction_type', DB::raw('SUM(amount) as total'))
            ->groupBy('transaction_type')
            ->get()
            ->pluck('total', 'transaction_type');

        return [
            // Total collecté (tous les dépôts)
            'total_collected' => (clone $transactions)
                ->where('transaction_type', 'like', '%deposit%')
                ->sum('amount'),

            // Total des retraits
            'total_withdrawals' => (clone $transactions)
                ->where('transaction_type', 'withdrawal')
                ->sum('amount'),

            // Nombre de transactions effectuées
            'transaction_count' => (clone $transactions)->count(),

            // Dépôts épargne
            'savings_deposits' => $depositsByType['savings_deposit'] ?? 0,

            // Dépôts tontine
            'tontine_deposits' => $depositsByType['tontine_deposit'] ?? 0,

            // Remboursements de prêts
            'loan_repayments' => (clone $transactions)
                ->where('transaction_type', 'loan_repayment')
                ->sum('amount'),

            // Frais collectés
            'fees_collected' => (clone $transactions)
                ->where('transaction_type', 'like', '%fee%')
                ->sum('amount'),

            // Période affichée
            'period' => $period,
            'date_range' => [
                'start' => $dateRange[0]->format('d/m/Y'),
                'end' => $dateRange[1]->format('d/m/Y'),
            ],
        ];
    }

    /**
     * Statistiques sur les clients de l'agent
     */
    private function getClientStats($agentId)
    {
        $clients = Client::where('registered_by', $agentId);

        return [
            'total' => (clone $clients)->where('registration_status', 'approved')->count(),
            'pending' => (clone $clients)->where('registration_status', 'pending')->count(),
            'rejected' => (clone $clients)->where('registration_status', 'rejected')->count(),
            'kyc_pending' => (clone $clients)->where('kyc_status', 'pending')->count(),
            'kyc_approved' => (clone $clients)->where('kyc_status', 'approved')->count(),
            'with_loans' => (clone $clients)
                ->whereHas('loans', function($q) {
                    $q->whereIn('status', ['active', 'disbursed']);
                })
                ->count(),
            'with_savings' => (clone $clients)
                ->whereHas('accounts', function($q) {
                    $q->where('account_type', 'savings')
                      ->where('status', 'active');
                })
                ->count(),
            'with_tontine' => (clone $clients)
                ->whereHas('accounts', function($q) {
                    $q->where('account_type', 'tontine')
                      ->where('status', 'active');
                })
                ->count(),
        ];
    }

    /**
     * Rappels et alertes pour l'agent
     */
    private function getReminders($agentId)
    {
        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        $today = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        // 🔴 Prêts en retard
        $overdue_loans = Loan::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->where('first_payment_date', '<', $today)
            ->with('client:id,first_name,last_name,phone')
            ->orderBy('first_payment_date')
            ->limit(10)
            ->get()
            ->map(function ($loan) use ($today) {
                return [
                    'type' => 'loan_overdue',
                    'client_id' => $loan->client_id,
                    'client' => $loan->client->full_name,
                    'phone' => $loan->client->phone,
                    'amount' => $loan->monthly_payment ?? 0,
                    'due_date' => $loan->first_payment_date,
                    'days_overdue' => Carbon::parse($loan->first_payment_date)->diffInDays($today),
                    'loan_number' => $loan->loan_number,
                    'priority' => 'high',
                ];
            });

        // 🟡 Prochains paiements de prêts (dans les 7 jours)
        $upcoming_loan_payments = Loan::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->whereBetween('first_payment_date', [$today->copy()->addDay(), $nextWeek])
            ->with('client:id,first_name,last_name,phone')
            ->orderBy('first_payment_date')
            ->limit(10)
            ->get()
            ->map(function ($loan) use ($today) {
                return [
                    'type' => 'loan_upcoming',
                    'client_id' => $loan->client_id,
                    'client' => $loan->client->full_name,
                    'phone' => $loan->client->phone,
                    'amount' => $loan->monthly_payment ?? 0,
                    'due_date' => $loan->first_payment_date,
                    'days_remaining' => $today->diffInDays($loan->first_payment_date, false),
                    'loan_number' => $loan->loan_number,
                    'priority' => 'medium',
                ];
            });

        // 🔵 Tontines arrivant à échéance
        $upcoming_tontine_cycles = TontineAccount::whereHas('account', function ($q) use ($clientIds) {
                $q->whereIn('client_id', $clientIds)
                  ->where('status', 'active');
            })
            ->whereBetween('cycle_end_date', [$today, $nextWeek])
            ->with('account.client:id,first_name,last_name,phone')
            ->orderBy('cycle_end_date')
            ->limit(10)
            ->get()
            ->map(function ($tontine) use ($today) {
                return [
                    'type' => 'tontine_cycle_end',
                    'client_id' => $tontine->account->client_id,
                    'client' => $tontine->account->client->full_name,
                    'phone' => $tontine->account->client->phone,
                    'amount' => $tontine->payout_amount ?? $tontine->total_paid,
                    'due_date' => $tontine->cycle_end_date,
                    'days_remaining' => $today->diffInDays($tontine->cycle_end_date, false),
                    'account_number' => $tontine->account->account_number,
                    'priority' => 'medium',
                ];
            });

        // ⚪ Clients inactifs (sans transaction depuis 30 jours)
        $inactiveClients = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('last_transaction_at')
                  ->orWhere('last_transaction_at', '<', Carbon::now()->subDays(30));
            })
            ->with('client:id,first_name,last_name,phone')
            ->limit(5)
            ->get()
            ->map(function ($account) {
                return [
                    'type' => 'inactive_client',
                    'client_id' => $account->client_id,
                    'client' => $account->client->full_name,
                    'phone' => $account->client->phone,
                    'last_transaction' => $account->last_transaction_at ? 
                        $account->last_transaction_at->format('d/m/Y') : 'Jamais',
                    'days_inactive' => $account->last_transaction_at ? 
                        Carbon::parse($account->last_transaction_at)->diffInDays(Carbon::now()) : null,
                    'priority' => 'low',
                ];
            });

        return [
            'overdue_loans' => $overdue_loans,
            'upcoming_loan_payments' => $upcoming_loan_payments,
            'upcoming_tontine_cycles' => $upcoming_tontine_cycles,
            'inactive_clients' => $inactiveClients,
            'total_reminders' => $overdue_loans->count() + 
                                $upcoming_loan_payments->count() + 
                                $upcoming_tontine_cycles->count() +
                                $inactiveClients->count(),
        ];
    }

    /**
     * Activités récentes de l'agent
     */
    private function getRecentActivities($agentId, $limit = 15)
    {
        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        $accountIds = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->pluck('id');

        return Transaction::whereIn('account_id', $accountIds)
            ->with([
                'account.client:id,first_name,last_name,client_number',
                'processedBy:id,first_name,last_name'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number ?? 'N/A',
                    'transaction_type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'client' => $transaction->account->client->full_name,
                    'client_number' => $transaction->account->client->client_number,
                    'account_number' => $transaction->account->account_number,
                    'performed_by' => $transaction->processedBy ? 
                        $transaction->processedBy->full_name : 'Système',
                    'created_at' => $transaction->created_at,
                    'status' => $transaction->status,
                    'description' => $transaction->description ?? '',
                ];
            });
    }

    /**
     * Données pour les graphiques
     */
    private function getChartData($agentId)
    {
        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        $accountIds = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->pluck('id');

        // Collectes des 7 derniers jours
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            $deposits = Transaction::whereIn('account_id', $accountIds)
                ->whereDate('created_at', $date)
                ->where('transaction_type', 'like', '%deposit%')
                ->where('status', 'completed')
                ->sum('amount');

            $withdrawals = Transaction::whereIn('account_id', $accountIds)
                ->whereDate('created_at', $date)
                ->where('transaction_type', 'withdrawal')
                ->where('status', 'completed')
                ->sum('amount');

            $last7Days[] = [
                'date' => $date->format('d/m'),
                'full_date' => $date->format('Y-m-d'),
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'net' => $deposits - $withdrawals,
            ];
        }

        // Répartition par type de compte
        $accountDistribution = [
            'savings' => [
                'count' => Account::whereIn('id', $accountIds)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->count(),
                'balance' => Account::whereIn('id', $accountIds)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->sum('balance'),
            ],
            'tontine' => [
                'count' => Account::whereIn('id', $accountIds)
                    ->where('account_type', 'tontine')
                    ->where('status', 'active')
                    ->count(),
                'balance' => Account::whereIn('id', $accountIds)
                    ->where('account_type', 'tontine')
                    ->where('status', 'active')
                    ->sum('balance'),
            ],
        ];

        // Évolution mensuelle (12 derniers mois)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            $collected = Transaction::whereIn('account_id', $accountIds)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->where('transaction_type', 'like', '%deposit%')
                ->where('status', 'completed')
                ->sum('amount');

            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'amount' => $collected,
            ];
        }

        return [
            'daily_collections' => $last7Days,
            'account_distribution' => $accountDistribution,
            'monthly_trend' => $monthlyData,
        ];
    }

    /**
     * Obtenir la plage de dates selon la période
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];

            case 'week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];

            case 'month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];

            case 'year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];

            default:
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
        }
    }

    /**
     * API pour les statistiques quotidiennes (AJAX)
     */
    public function dailyStats(Request $request)
    {
        $user = $request->user();
        $agentId = $user->id;
        $period = $request->get('period', 'today');

        return response()->json([
            'success' => true,
            'data' => [
                'collections' => $this->getCollectionStats($agentId, $period),
                'overview' => $this->getOverviewStats($agentId, $period),
            ],
        ]);
    }
}