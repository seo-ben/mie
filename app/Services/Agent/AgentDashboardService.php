<?php

namespace App\Services\Agent;

use App\Models\Account;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\TontineAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgentDashboardService
{
    /**
     * Obtenir les IDs des clients enregistrés par l'agent
     */
    private function getAgentClientIds($agentId)
    {
        return Client::where('registered_by', $agentId)
            ->where('registration_status', 'approved')
            ->pluck('id');
    }

    /**
     * Obtenir les IDs des comptes des clients de l'agent
     */
    private function getAgentAccountIds($agentId)
    {
        $clientIds = $this->getAgentClientIds($agentId);

        return Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->pluck('id');
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
     * Statistiques d'ensemble pour l'agent
     */
    public function getOverviewStats($agentId, $period = 'today')
    {
        $dateRange = $this->getDateRange($period);
        $clientIds = $this->getAgentClientIds($agentId);
        $accountIds = $this->getAgentAccountIds($agentId);

        return [
            // Solde total des comptes gérés
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
    public function getCollectionStats($agentId, $period = 'today')
    {
        $dateRange = $this->getDateRange($period);
        $accountIds = $this->getAgentAccountIds($agentId);

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
    public function getClientStats($agentId)
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
    public function getReminders($agentId)
    {
        $clientIds = $this->getAgentClientIds($agentId);
        $today = Carbon::today();
        $nextWeek = Carbon::today()->addDays(7);

        // Prêts en retard
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

        // Prochains paiements de prêts
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

        // Tontines arrivant à échéance
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

        // Clients sans activité depuis 30 jours
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
    public function getRecentActivities($agentId, $limit = 15)
    {
        $accountIds = $this->getAgentAccountIds($agentId);

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
                    'transaction_number' => $transaction->transaction_number,
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'client' => $transaction->account->client->full_name,
                    'client_number' => $transaction->account->client->client_number,
                    'account_number' => $transaction->account->account_number,
                    'performed_by' => $transaction->processedBy ? 
                        $transaction->processedBy->full_name : 'Système',
                    'created_at' => $transaction->created_at,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                ];
            });
    }

    /**
     * Données pour les graphiques
     */
    public function getChartData($agentId)
    {
        $accountIds = $this->getAgentAccountIds($agentId);

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
     * Obtenir les clients de l'agent avec pagination
     */
    public function getAgentClients($agentId, $perPage = 15)
    {
        return Client::where('registered_by', $agentId)
            ->with(['accounts', 'agency'])
            ->withCount(['accounts', 'loans'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtenir les détails d'un client (seulement si enregistré par l'agent)
     */
    public function getClientDetails($agentId, $clientId)
    {
        $client = Client::where('id', $clientId)
            ->where('registered_by', $agentId)
            ->with([
                'accounts.transactions' => function($q) {
                    $q->orderBy('created_at', 'desc')->limit(10);
                },
                'loans',
                'documents',
                'agency'
            ])
            ->first();

        if (!$client) {
            throw new \Exception('Client non trouvé ou vous n\'avez pas accès à ce client.');
        }

        return $client;
    }

    /**
     * Exporter les données de l'agent
     */
    public function exportData($agentId, $type = 'clients')
    {
        // Cette méthode peut être étendue pour générer des exports CSV/Excel
        // Pour l'instant, retourne les données brutes
        
        switch ($type) {
            case 'clients':
                return $this->getAgentClientIds($agentId);
            
            case 'transactions':
                $accountIds = $this->getAgentAccountIds($agentId);
                return Transaction::whereIn('account_id', $accountIds)
                    ->with('account.client')
                    ->get();
            
            default:
                throw new \Exception('Type d\'export non valide');
        }
    }
}