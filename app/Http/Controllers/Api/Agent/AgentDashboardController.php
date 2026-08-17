<?php

namespace App\Http\Controllers\Api\Agent;

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
     * Point d’entrée principal – renvoie tout le tableau de bord en JSON.
     */
    public function index(Request $request)
    {
        $user   = $request->user();
        $agentId = $user->id;
        $period = $request->get('period', 'today');

        return response()->json([
            'success' => true,
            'data' => [
                'overview'        => $this->getOverviewStats($agentId, $period),
                'collections'     => $this->getCollectionStats($agentId, $period),
                'clients'         => $this->getClientStats($agentId),
                'reminders'       => $this->getReminders($agentId),
                'recentActivities'=> $this->getRecentActivities($agentId),
                'chartData'       => $this->getChartData($agentId),
                'currentPeriod'   => $period,
                'agent'           => [
                    'name'  => $user->full_name,
                    'agency'=> $user->agency->name ?? 'N/A',
                ],
            ],
        ]);
    }

    /**
     * Statistiques d’ensemble pour l’agent.
     */
    public function overview(Request $request)
    {
        $user   = $request->user();
        $period = $request->get('period', 'today');

        return response()->json([
            'success' => true,
            'data'    => $this->getOverviewStats($user->id, $period),
        ]);
    }

    /**
     * Statistiques de collecte pour l’agent.
     */
    public function collections(Request $request)
    {
        $user   = $request->user();
        $period = $request->get('period', 'today');

        return response()->json([
            'success' => true,
            'data'    => $this->getCollectionStats($user->id, $period),
        ]);
    }

    /**
     * Statistiques sur les clients de l’agent.
     */
    public function clients(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->getClientStats($user->id),
        ]);
    }

    /**
     * Rappels et alertes pour l’agent.
     */
    public function reminders(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->getReminders($user->id),
        ]);
    }

    /**
     * Activités récentes de l’agent.
     */
    public function recentActivities(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->getRecentActivities($user->id),
        ]);
    }

    /**
     * Données pour les graphiques.
     */
    public function chartData(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => $this->getChartData($user->id),
        ]);
    }

    /**
     * Statistiques quotidiennes (AJAX) – point d’entrée dédié.
     */
    public function dailyStats(Request $request)
    {
        $user   = $request->user();
        $period = $request->get('period', 'today');

        return response()->json([
            'success' => true,
            'data'    => [
                'collections' => $this->getCollectionStats($user->id, $period),
                'overview'    => $this->getOverviewStats($user->id, $period),
            ],
        ]);
    }

    /* -----------------------------------------------------------------
     * Méthodes privées (logique métier) – identiques à la version Web
     * ----------------------------------------------------------------- */

    private function getOverviewStats($agentId, $period)
    {
        $dateRange = $this->getDateRange($period);
        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        $accountIds = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->pluck('id');

        return [
            'total_balance'      => Account::whereIn('id', $accountIds)->sum('balance'),
            'total_clients'      => $clientIds->count(),
            'active_clients'     => Transaction::whereIn('account_id', $accountIds)
                ->whereBetween('created_at', $dateRange)
                ->distinct('account_id')
                ->count('account_id'),
            'new_accounts'       => Account::whereIn('client_id', $clientIds)
                ->whereBetween('created_at', $dateRange)
                ->count(),
            'savings_accounts'   => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'savings')
                ->where('status', 'active')
                ->count(),
            'tontine_accounts'   => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')
                ->count(),
            'active_loans'       => Loan::whereIn('client_id', $clientIds)
                ->whereIn('status', ['active', 'disbursed'])
                ->count(),
            'total_loan_amount'  => Loan::whereIn('client_id', $clientIds)
                ->whereIn('status', ['active', 'disbursed'])
                ->sum('approved_amount'),
        ];
    }

    private function getCollectionStats($agentId, $period)
    {
        $dateRange = $this->getDateRange($period);

        // On se base sur ce que l'agent a RÉELLEMENT traité (processed_by)
        $transactions = Transaction::where('processed_by', $agentId)
            ->whereBetween('created_at', $dateRange)
            ->where('status', 'completed');

        $depositsByType = (clone $transactions)
            ->where('transaction_type', 'like', '%deposit%')
            ->select('transaction_type', DB::raw('SUM(amount) as total'))
            ->groupBy('transaction_type')
            ->get()
            ->pluck('total', 'transaction_type');

        // Récupérer les 10 dernières collectes pour l'affichage mobile
        $recent = (clone $transactions)
            ->with(['account.client'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'amount' => $t->amount,
                    'client_name' => $t->account->client->full_name ?? 'Client inconnu',
                    'transaction_type' => $t->transaction_type,
                    'created_at' => $t->created_at,
                    'description' => $t->description
                ];
            });

        return [
            'total_collected'    => (clone $transactions)
                ->where('transaction_type', 'like', '%deposit%')
                ->sum('amount'),
            'total_withdrawals'  => (clone $transactions)
                ->where('transaction_type', 'withdrawal')
                ->sum('amount'),
            'transaction_count' => (clone $transactions)->count(),
            'count'             => (clone $transactions)->count(), // Double mapping pour compatibilité mobile
            'collections_count' => (clone $transactions)->where('transaction_type', 'like', '%deposit%')->count(),
            'target'            => 100000, // Objectif fictif flexible ou à lier aux paramètres agence
            'recent'            => $recent,
            'savings_deposits'  => $depositsByType['savings_deposit'] ?? 0,
            'tontine_deposits'  => $depositsByType['tontine_deposit'] ?? 0,
            'loan_repayments'   => (clone $transactions)
                ->where('transaction_type', 'loan_repayment')
                ->sum('amount'),
            'fees_collected'    => (clone $transactions)
                ->where('transaction_type', 'like', '%fee%')
                ->sum('amount'),
            'period'            => $period,
            'date_range'        => [
                'start' => $dateRange[0]->format('d/m/Y'),
                'end'   => $dateRange[1]->format('d/m/Y'),
            ],
        ];
    }

    private function getClientStats($agentId)
    {
        $clients = Client::where('registered_by', $agentId);
        return [
            'total'            => (clone $clients)->where('registration_status', 'approved')->count(),
            'pending'           => (clone $clients)->where('registration_status', 'pending')->count(),
            'with_loans'        => (clone $clients)
                ->whereHas('loans', function($q){ $q->whereIn('status', ['active','disbursed']); })
                ->count(),
            'with_savings'      => (clone $clients)
                ->whereHas('accounts', function($q){ $q->where('account_type','savings')->where('status','active'); })
                ->count(),
            'with_tontine'      => (clone $clients)
                ->whereHas('accounts', function($q){ $q->where('account_type','tontine')->where('status','active'); })
                ->count(),
        ];
    }

    private function getReminders($agentId)
    {
        $clientIds = Client::where('registered_by', $agentId)
            ->whereIn('registration_status', ['approved', 'pending'])
            ->pluck('id');

        $today      = Carbon::today();
        $nextWeek   = Carbon::today()->addDays(7);

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
                    'type'          => 'loan_overdue',
                    'client_id'      => $loan->client_id,
                    'client'         => $loan->client->full_name,
                    'phone'          => $loan->client->phone,
                    'amount'         => $loan->monthly_payment ?? 0,
                    'due_date'       => $loan->first_payment_date,
                    'days_overdue'   => Carbon::parse($loan->first_payment_date)->diffInDays($today),
                    'loan_number'    => $loan->loan_number,
                    'priority'       => 'high',
                ];
            });

        // Prochains paiements de prêts (dans les 7 jours)
        $upcoming_loan_payments = Loan::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->whereBetween('first_payment_date', [$today->copy()->addDay(), $nextWeek])
            ->with('client:id,first_name,last_name,phone')
            ->orderBy('first_payment_date')
            ->limit(10)
            ->get()
            ->map(function ($loan) use ($today) {
                return [
                    'type'          => 'loan_upcoming',
                    'client_id'      => $loan->client_id,
                    'client'         => $loan->client->full_name,
                    'phone'          => $loan->client->phone,
                    'amount'         => $loan->monthly_payment ?? 0,
                    'due_date'       => $loan->first_payment_date,
                    'days_remaining' => $today->diffInDays($loan->first_payment_date, false),
                    'loan_number'    => $loan->loan_number,
                    'priority'       => 'medium',
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
                    'type'          => 'tontine_cycle_end',
                    'client_id'      => $tontine->account->client_id,
                    'client'         => $tontine->account->client->full_name,
                    'phone'          => $tontine->account->client->phone,
                    'amount'         => $tontine->payout_amount ?? $tontine->total_paid,
                    'due_date'       => $tontine->cycle_end_date,
                    'days_remaining' => $today->diffInDays($tontine->cycle_end_date, false),
                    'account_number' => $tontine->account->account_number,
                    'priority'       => 'medium',
                ];
            });

        // Clients inactifs (sans transaction depuis 30 jours)
        $inactiveClients = Account::whereIn('client_id', $clientIds)
            ->where('status', 'active')
            ->where(function($q){
                $q->whereNull('last_transaction_at')
                  ->orWhere('last_transaction_at', '<', Carbon::now()->subDays(30));
            })
            ->with('client:id,first_name,last_name,phone')
            ->limit(5)
            ->get()
            ->map(function ($account) {
                return [
                    'type'          => 'inactive_client',
                    'client_id'      => $account->client_id,
                    'client'         => $account->client->full_name,
                    'phone'          => $account->client->phone,
                    'last_transaction'=> $account->last_transaction_at ?
                        $account->last_transaction_at->format('d/m/Y') : 'Jamais',
                    'days_inactive' => $account->last_transaction_at ?
                        Carbon::parse($account->last_transaction_at)->diffInDays(Carbon::now()) : null,
                    'priority'       => 'low',
                ];
            });

        return [
            'overdue_loans'        => $overdue_loans,
            'upcoming_loan_payments'=> $upcoming_loan_payments,
            'upcoming_tontine_cycles'=> $upcoming_tontine_cycles,
            'inactive_clients'      => $inactiveClients,
            'total_reminders'      => $overdue_loans->count() +
                                    $upcoming_loan_payments->count() +
                                    $upcoming_tontine_cycles->count() +
                                    $inactiveClients->count(),
        ];
    }

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
                    'id'               => $transaction->id,
                    'transaction_number'=> $transaction->transaction_number ?? 'N/A',
                    'transaction_type'  => $transaction->transaction_type,
                    'amount'           => $transaction->amount,
                    'client'           => $transaction->account->client->full_name,
                    'client_number'    => $transaction->account->client->client_number,
                    'account_number'   => $transaction->account->account_number,
                    'performed_by'     => $transaction->processedBy ?
                        $transaction->processedBy->full_name : 'Système',
                    'created_at'       => $transaction->created_at,
                    'status'           => $transaction->status,
                    'description'      => $transaction->description ?? '',
                ];
            });
    }

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
                'date'      => $date->format('d/m'),
                'full_date' => $date->format('Y-m-d'),
                'deposits'  => $deposits,
                'withdrawals'=> $withdrawals,
                'net'       => $deposits - $withdrawals,
            ];
        }

        // Répartition par type de compte
        $accountDistribution = [
            'savings' => [
                'count'   => Account::whereIn('id', $accountIds)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->count(),
                'balance' => Account::whereIn('id', $accountIds)
                    ->where('account_type', 'savings')
                    ->where('status', 'active')
                    ->sum('balance'),
            ],
            'tontine' => [
                'count'   => Account::whereIn('id', $accountIds)
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
                'amount'=> $collected,
            ];
        }

        return [
            'daily_collections' => $last7Days,
            'account_distribution'=> $accountDistribution,
            'monthly_trend'     => $monthlyData,
        ];
    }

    /**
     * Retourne la plage de dates en fonction de la période demandée.
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
}
