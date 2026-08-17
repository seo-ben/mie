<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminUserReportController extends Controller
{
    /**
     * Liste des utilisateurs avec statistiques de base
     */
    public function index(Request $request)
    {
        $query = User::with(['agency'])
            ->withCount([
                'clients',
                'clients as active_clients_count' => function($q) {
                    $q->where('kyc_status', 'approved');
                }
            ]);

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Période d'activité
        if ($request->filled('activity_period')) {
            $period = $request->activity_period;
            $date = match($period) {
                'today' => today(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                '3months' => now()->subMonths(3),
                default => null,
            };

            if ($date) {
                $query->where('last_login', '>=', $date);
            }
        }

        $users = $query->latest('last_login')->paginate(20);

        // Statistiques globales avec whereDate pour aujourd'hui
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'users_logged_today' => User::whereDate('last_login', today())->count(),
            'users_logged_week' => User::where('last_login', '>=', now()->subWeek())->count(),
            'total_clients' => Client::count(),
            'total_transactions_today' => Transaction::whereDate('transaction_date', today())->count(),
            'total_amount_today' => Transaction::whereDate('transaction_date', today())
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        // Données pour le graphique de performance globale
        $chartData = Transaction::where('transaction_date', '>=', now()->subDays(30))
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Liste des agences pour le filtre
        $agencies = Agency::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.reports.users.index', compact('users', 'stats', 'agencies', 'chartData'));
    }

    /**
     * Rapport détaillé d'un utilisateur spécifique
     */
    public function show($userId, Request $request)
    {
        $user = User::with(['agency'])
            ->withCount('clients')
            ->findOrFail($userId);

        // Période pour les statistiques avec gestion correcte des dates
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        // 1. Statistiques des clients
        $clientStats = $this->getClientStats($user, $startDate, $endDate);

        // 2. Statistiques des comptes
        $accountStats = $this->getAccountStats($user, $startDate, $endDate);

        // 3. Statistiques des transactions
        $transactionStats = $this->getTransactionStats($user, $startDate, $endDate);

        // 4. Performance quotidienne
        $dailyPerformance = $this->getDailyPerformance($user, $startDate, $endDate);

        // 5. Activités récentes
        $recentActivities = $this->getRecentActivities($user, 20);

        // 6. Top clients
        $topClients = $this->getTopClients($user, 10);

        // 7. Répartition par type de compte
        $accountTypeDistribution = $this->getAccountTypeDistribution($user);

        // 8. Répartition par méthode de paiement
        $paymentMethodDistribution = $this->getPaymentMethodDistribution($user, $startDate, $endDate);

        return view('admin.reports.users.show', compact(
            'user',
            'startDate',
            'endDate',
            'clientStats',
            'accountStats',
            'transactionStats',
            'dailyPerformance',
            'recentActivities',
            'topClients',
            'accountTypeDistribution',
            'paymentMethodDistribution'
        ));
    }

    /**
     * Rapport d'activité de l'agence
     */
    public function agencyReport($agencyId, Request $request)
    {
        $agency = Agency::with(['users', 'manager'])
            ->withCount('users')
            ->findOrFail($agencyId);

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        // Récupérer les IDs des utilisateurs de l'agence
        $userIds = $agency->users->pluck('id')->toArray();

        // Statistiques globales de l'agence
        $stats = [
            'total_users' => $agency->users_count,
            'active_users' => $agency->users->where('is_active', true)->count(),
            'total_clients' => Client::where('agency_id', $agency->id)->count(),
            'clients_period' => Client::where('agency_id', $agency->id)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->count(),
            'total_accounts' => Account::whereHas('client', function($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->count(),
            'active_accounts' => Account::whereHas('client', function($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'active')->count(),
            'total_balance' => Account::whereHas('client', function($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'active')->sum('balance'),
            'transactions_period' => Transaction::where('agency_id', $agency->id)
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate)
            ->count(),
            'amount_period' => Transaction::where('agency_id', $agency->id)
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate)
            ->where('status', 'completed')
            ->sum('amount'),
        ];

        // Performance par utilisateur
        $userPerformances = [];
        foreach ($agency->users as $user) {
            $userPerformances[] = [
                'user' => $user,
                'clients_count' => Client::where('registered_by', $user->id)->count(),
                'clients_period' => Client::where('registered_by', $user->id)
                    ->where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate)
                    ->count(),
                'transactions_count' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->count(),
                'transactions_amount' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->where('status', 'completed')
                    ->sum('amount'),
                'last_activity' => Transaction::where('processed_by', $user->id)
                    ->latest('transaction_date')
                    ->first()?->transaction_date,
            ];
        }

        // Trier par nombre de transactions
        usort($userPerformances, function($a, $b) {
            return $b['transactions_count'] <=> $a['transactions_count'];
        });

        // Performance quotidienne de l'agence
        $dailyPerformance = Transaction::whereHas('account.client', function($q) use ($userIds) {
                $q->whereIn('registered_by', $userIds);
            })
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate)
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.reports.agencies.show', compact(
            'agency',
            'startDate',
            'endDate',
            'stats',
            'userPerformances',
            'dailyPerformance'
        ));
    }

    /**
     * Performance globale de toutes les agences
     */
    public function agenciesPerformance(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $agencies = Agency::withCount(['users', 'users as active_users_count' => function($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $agenciesPerformance = [];

        foreach ($agencies as $agency) {
            $userIds = $agency->users->pluck('id')->toArray();

            $stats = [
                'agency' => $agency,
                'clients_count' => Client::where('agency_id', $agency->id)->count(),
                'clients_period' => Client::where('agency_id', $agency->id)
                    ->where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate)
                    ->count(),
                'transactions_count' => Transaction::where('agency_id', $agency->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->where('status', 'completed')
                    ->count(),
                'transactions_amount' => Transaction::where('agency_id', $agency->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->where('status', 'completed')
                    ->sum('amount'),
                'total_balance' => Account::whereHas('client', function($q) use ($agency) {
                        $q->where('agency_id', $agency->id);
                    })
                    ->where('status', 'active')
                    ->sum('balance'),
            ];

            $agenciesPerformance[] = $stats;
        }

        // Global stats for all agencies
        $globalStats = [
            'total_agencies' => $agencies->count(),
            'total_clients' => Client::count(),
            'total_transactions' => Transaction::where('transaction_date', '>=', $startDate)
                ->where('transaction_date', '<=', $endDate)
                ->where('status', 'completed')
                ->count(),
            'total_amount' => Transaction::where('transaction_date', '>=', $startDate)
                ->where('transaction_date', '<=', $endDate)
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        return view('admin.reports.agencies.index', compact(
            'agenciesPerformance',
            'globalStats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Rapport comparatif entre utilisateurs
     */
    public function compareUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:2|max:5',
            'user_ids.*' => 'exists:users,id'
        ]);

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $users = User::with('agency')
            ->whereIn('id', $request->user_ids)
            ->get();

        $comparisons = [];

        foreach ($users as $user) {
            $comparisons[] = [
                'user' => $user,
                'clients_total' => Client::where('registered_by', $user->id)->count(),
                'clients_period' => Client::where('registered_by', $user->id)
                    ->where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate)
                    ->count(),
                'clients_approved' => Client::where('registered_by', $user->id)
                    ->where('kyc_status', 'approved')
                    ->count(),
                'accounts_total' => Account::whereHas('client', function($q) use ($user) {
                    $q->where('registered_by', $user->id);
                })->count(),
                'accounts_active' => Account::whereHas('client', function($q) use ($user) {
                    $q->where('registered_by', $user->id);
                })->where('status', 'active')->count(),
                'transactions_count' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->count(),
                'transactions_amount' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->where('status', 'completed')
                    ->sum('amount'),
                'avg_transaction' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->where('status', 'completed')
                    ->avg('amount') ?? 0,
                'deposits_count' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_type', 'deposit')
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->count(),
                'withdrawals_count' => Transaction::where('processed_by', $user->id)
                    ->where('transaction_type', 'withdrawal')
                    ->where('transaction_date', '>=', $startDate)
                    ->where('transaction_date', '<=', $endDate)
                    ->count(),
            ];
        }

        return view('admin.reports.users.compare', compact(
            'comparisons',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export des données utilisateur (JSON/Excel)
     */
    public function export($userId, Request $request)
    {
        $user = User::with('agency')->findOrFail($userId);

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $data = [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'agency' => $user->agency ? [
                    'name' => $user->agency->name,
                    'code' => $user->agency->code,
                    'city' => $user->agency->city,
                ] : null,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
            ],
            'statistics' => [
                'clients' => $this->getClientStats($user, $startDate, $endDate),
                'accounts' => $this->getAccountStats($user, $startDate, $endDate),
                'transactions' => $this->getTransactionStats($user, $startDate, $endDate),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="user-report-' . $user->id . '-' . now()->format('Y-m-d') . '.json"');
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Statistiques des clients
     */
    private function getClientStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = Client::where('registered_by', $user->id);

        return [
            'total' => (clone $baseQuery)->count(),
            'created_period' => (clone $baseQuery)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->count(),
            'kyc_approved' => (clone $baseQuery)
                ->where('kyc_status', 'approved')
                ->count(),
            'kyc_pending' => (clone $baseQuery)
                ->where('kyc_status', 'pending')
                ->count(),
            'kyc_rejected' => (clone $baseQuery)
                ->where('kyc_status', 'rejected')
                ->count(),
            'with_accounts' => (clone $baseQuery)
                ->has('accounts')
                ->count(),
            'without_accounts' => (clone $baseQuery)
                ->doesntHave('accounts')
                ->count(),
        ];
    }

    /**
     * Statistiques des comptes
     */
    private function getAccountStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $clientIds = Client::where('registered_by', $user->id)->pluck('id')->toArray();

        return [
            'total' => Account::whereIn('client_id', $clientIds)->count(),
            'active' => Account::whereIn('client_id', $clientIds)->where('status', 'active')->count(),
            'suspended' => Account::whereIn('client_id', $clientIds)->where('status', 'suspended')->count(),
            'closed' => Account::whereIn('client_id', $clientIds)->where('status', 'closed')->count(),
            'created_period' => Account::whereIn('client_id', $clientIds)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->count(),
            'activated_period' => Account::whereIn('client_id', $clientIds)
                ->whereNotNull('activated_at')
                ->where('activated_at', '>=', $startDate)
                ->where('activated_at', '<=', $endDate)
                ->count(),
            'savings_count' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'savings')
                ->count(),
            'tontine_count' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->count(),
            'total_balance' => Account::whereIn('client_id', $clientIds)
                ->where('status', 'active')
                ->sum('balance'),
            'savings_balance' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'savings')
                ->where('status', 'active')
                ->sum('balance'),
            'tontine_balance' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')
                ->sum('balance'),
        ];
    }

    /**
     * Statistiques des transactions
     */
    private function getTransactionStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = Transaction::where('processed_by', $user->id)
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate);

        $completedQuery = (clone $baseQuery)->where('status', 'completed');

        return [
            'total_count' => (clone $baseQuery)->count(),
            'completed_count' => (clone $completedQuery)->count(),
            'pending_count' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_count' => (clone $baseQuery)->where('status', 'failed')->count(),
            'total_amount' => (clone $completedQuery)->sum('amount'),
            'deposits_count' => (clone $completedQuery)->where('transaction_type', 'deposit')->count(),
            'deposits_amount' => (clone $completedQuery)->where('transaction_type', 'deposit')->sum('amount'),
            'withdrawals_count' => (clone $completedQuery)->where('transaction_type', 'withdrawal')->count(),
            'withdrawals_amount' => (clone $completedQuery)->where('transaction_type', 'withdrawal')->sum('amount'),
            'transfers_count' => (clone $completedQuery)->where('transaction_type', 'transfer')->count(),
            'transfers_amount' => (clone $completedQuery)->where('transaction_type', 'transfer')->sum('amount'),
            'avg_transaction_amount' => (clone $completedQuery)->avg('amount') ?? 0,
            'total_fees' => (clone $completedQuery)->sum('fee_amount') + (clone $completedQuery)->sum('withdrawal_fee') ?? 0,
        ];
    }

    /**
     * Performance quotidienne
     */
    private function getDailyPerformance(User $user, Carbon $startDate, Carbon $endDate)
    {
        return Transaction::where('processed_by', $user->id)
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate)
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transactions_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(CASE WHEN transaction_type = "deposit" THEN 1 END) as deposits'),
                DB::raw('COUNT(CASE WHEN transaction_type = "withdrawal" THEN 1 END) as withdrawals')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Activités récentes
     */
    private function getRecentActivities(User $user, int $limit = 20)
    {
        return Transaction::where('processed_by', $user->id)
            ->with(['account.client'])
            ->latest('transaction_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Top clients par volume de transactions
     */
    private function getTopClients(User $user, int $limit = 10)
    {
        $clientIds = Client::where('registered_by', $user->id)->pluck('id')->toArray();

        return Client::whereIn('id', $clientIds)
            ->withCount([
                'accounts',
                'accounts as active_accounts_count' => function($q) {
                    $q->where('status', 'active');
                }
            ])
            ->withSum([
                'accounts as total_balance' => function($q) {
                    $q->where('status', 'active');
                }
            ], 'balance')
            ->with(['accounts' => function($q) {
                $q->withCount('transactions');
            }])
            ->get()
            ->map(function($client) {
                $client->transactions_count = $client->accounts->sum('transactions_count');
                return $client;
            })
            ->sortByDesc('transactions_count')
            ->take($limit)
            ->values();
    }

    /**
     * Répartition par type de compte
     */
    private function getAccountTypeDistribution(User $user)
    {
        $clientIds = Client::where('registered_by', $user->id)->pluck('id')->toArray();

        return Account::whereIn('client_id', $clientIds)
            ->select(
                'account_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(balance) as total_balance')
            )
            ->where('status', 'active')
            ->groupBy('account_type')
            ->get();
    }

    /**
     * Répartition par méthode de paiement
     */
    private function getPaymentMethodDistribution(User $user, Carbon $startDate, Carbon $endDate)
    {
        return Transaction::where('processed_by', $user->id)
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate)
            ->where('status', 'completed')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('payment_method')
            ->get();
    }
}
