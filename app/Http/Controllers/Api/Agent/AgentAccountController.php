<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgentAccountController extends Controller
{
    /**
     * Liste de tous les comptes tontine de l'agent
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Récupérer les IDs des clients de l'agent
        $clientIds = Client::where('registered_by', $user->id)->pluck('id');

        $query = Account::with(['client', 'tontineAccount.activeCycle'])
            ->whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine');

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('client_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $accounts = $query->latest()->paginate(15);

        // Statistiques
        $stats = [
            'total_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')->count(),
            'active_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')->count(),
            'total_balance' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')
                ->sum('balance'),
        ];

        return response()->json([
            'data' => $accounts->items(),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Créer un nouveau compte tontine
     */
    public function store(Request $request, int $clientId): JsonResponse
    {
        $validated = $request->validate([
            'target_amount' => 'required|numeric|min:200',
            'cycle_duration_months' => 'required|integer|min:1|max:24',
            'payment_frequency' => 'required|in:daily,weekly,monthly',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Vérifier que le client appartient à l'agent
            $client = Client::where('id', $clientId)
                ->where('registered_by', $user->id)
                ->firstOrFail();

            // 1. Créer le compte de base - Directement actif
            $account = Account::create([
                'client_id' => $clientId,
                'account_number' => $this->generateAccountNumber('tontine'),
                'account_type' => 'tontine',
                'status' => 'active',
                'activation_fee' => 0,
                'balance' => 0,
                'created_by' => $user->id,
                'activated_by' => $user->id,
                'activated_at' => now(),
                'created_at' => now(),
            ]);

            // 2. Calcul du nombre de périodes selon la fréquence
            $startDate = now();
            $durationMonths = (int) $validated['cycle_duration_months'];
            $totalPeriods = 0;
            $endDate = null;

            switch ($validated['payment_frequency']) {
                case 'daily':
                    // Règle des 31 jours par mois (372 jours pour 12 mois)
                    $totalPeriods = $durationMonths * 31;
                    $endDate = (clone $startDate)->addDays($totalPeriods);
                    break;
                case 'weekly':
                    // Règle des 52 semaines par an
                    $totalPeriods = floor(($durationMonths * 52) / 12);
                    $endDate = (clone $startDate)->addWeeks($totalPeriods);
                    break;
                case 'monthly':
                    $totalPeriods = $durationMonths;
                    $endDate = (clone $startDate)->addMonths($durationMonths);
                    break;
            }

            // 3. Calcul des montants
            $targetAmount = (float) $validated['target_amount'];
            $totalExpected = $targetAmount * $totalPeriods;

            // 4. Création du compte tontine
            $tontineAccount = TontineAccount::create([
                'account_id' => $account->id,
                'tontine_amount' => $targetAmount,
                'cycle_duration_months' => (int) $validated['cycle_duration_months'],
                'payment_frequency' => $validated['payment_frequency'],
                'expected_monthly_payment' => $targetAmount,
                'total_expected' => $totalExpected,
                'total_paid' => 0,
                'penalty_rate' => 0.05,
                'total_penalties' => 0,
            ]);

            // 5. Initialiser le premier cycle
            $this->createTontineCycle($tontineAccount);

            DB::commit();

            return response()->json([
                'message' => 'Compte tontine créé avec succès et prêt pour les collectes.',
                'data' => $account->load(['client', 'tontineAccount.activeCycle'])
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Client non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur création compte tontine', [
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du compte',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Afficher les détails d'un compte tontine
     */
    public function show(int $accountId): JsonResponse
    {
        try {
            $user = auth()->user();
            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $account = Account::with([
                'client',
                'tontineAccount.cycles' => function($q) {
                    $q->orderBy('cycle_number', 'desc');
                },
                'tontineAccount.activeCycle',
                'transactions' => function($q) {
                    $q->latest()->limit(20);
                },
                'createdBy',
                'activatedBy'
            ])
            ->whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine')
            ->findOrFail($accountId);

            // Statistiques du compte
            $stats = [
                'total_deposits' => $account->transactions()
                    ->where('transaction_type', 'deposit')
                    ->where('status', 'completed')
                    ->sum('amount'),
                'total_cycles' => $account->tontineAccount->cycles()->count(),
                'completed_cycles' => $account->tontineAccount->cycles()
                    ->where('status', 'completed')->count(),
                'active_cycles' => $account->tontineAccount->cycles()
                    ->where('status', 'active')->count(),
                'transaction_count' => $account->transactions()->count(),
            ];

            return response()->json([
                'data' => [
                    'account' => $account,
                    'stats' => $stats
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Compte non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur récupération compte', [
                'account_id' => $accountId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération du compte'
            ], 500);
        }
    }

    /**
     * Activer le compte tontine
     */
    public function activate(Request $request, int $accountId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'mobile_money_operator' => 'nullable|in:tmoney,flooz',
            'payment_reference' => 'nullable|string|max:100',
            'initial_deposit' => 'nullable|numeric|min:0',
        ]);

        if ($validated['payment_method'] === 'mobile_money' && !isset($validated['mobile_money_operator'])) {
            return response()->json([
                'message' => 'Veuillez sélectionner un opérateur Mobile Money.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $account = Account::with(['tontineAccount'])
                ->whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'suspended')
                ->lockForUpdate()
                ->findOrFail($accountId);

            // Activer le compte
            $account->update([
                'status' => 'active',
                'activated_at' => now(),
                'activated_by' => $user->id,
            ]);

            // Créer le premier cycle automatiquement
            $firstCycle = $this->createTontineCycle($account->tontineAccount);

            $depositTransaction = null;
            $newBalance = $account->balance;

            // Enregistrer le dépôt initial si fourni
            if (!empty($validated['initial_deposit']) && $validated['initial_deposit'] > 0) {
                $amount = $validated['initial_deposit'];
                $newBalance = $account->balance + $amount;

                $depositTransaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $amount,
                    'fee_amount' => 0,
                    'balance_before' => $account->balance,
                    'balance_after' => $newBalance,
                    'payment_method' => $validated['payment_method'],
                    'mobile_money_operator' => $validated['mobile_money_operator'] ?? null,
                    'payment_reference' => $validated['payment_reference'] ?? $this->generatePaymentReference($validated['payment_method']),
                    'description' => 'Dépôt initial à l\'activation',
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                $account->increment('balance', $amount);

                // Mettre à jour le cycle avec le dépôt initial
                $firstCycle->increment('collected_amount', $amount);
                $account->tontineAccount->increment('total_paid', $amount);
            }

            DB::commit();

            $message = 'Compte tontine activé avec succès.';
            if ($depositTransaction) {
                $message .= ' Dépôt initial de ' . number_format($validated['initial_deposit'], 0, ',', ' ') . ' FCFA enregistré.';
            }

            return response()->json([
                'message' => $message,
                'data' => [
                    'account' => $account->fresh()->load(['client', 'tontineAccount.activeCycle']),
                    'first_cycle' => $firstCycle,
                    'deposit_transaction' => $depositTransaction
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Compte non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur activation compte', [
                'account_id' => $accountId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'activation',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Traiter le dépôt sur compte tontine
     */
    public function deposit(Request $request, int $accountId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'mobile_money_operator' => 'nullable|in:tmoney,flooz',
            'payment_reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validated['payment_method'] === 'mobile_money' && !isset($validated['mobile_money_operator'])) {
            return response()->json([
                'message' => 'Veuillez sélectionner un opérateur Mobile Money.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $account = Account::with([
                'client',
                'tontineAccount.activeCycle',
                'tontineAccount.cycles'
            ])
            ->whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine')
            ->where('status', 'active')
            ->lockForUpdate()
            ->findOrFail($accountId);

            $tontine = $account->tontineAccount;
            $amount = $validated['amount'];
            $balanceBefore = $account->balance;

            // Vérifier que la tontine n'est pas complète
            $totalRemaining = $tontine->total_expected - $tontine->total_paid;

            if ($totalRemaining <= 0) {
                return response()->json([
                    'message' => 'Cette tontine est complète ! Total atteint : ' .
                        number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA'
                ], 422);
            }

            // Ajuster le montant si nécessaire
            if ($amount > $totalRemaining) {
                $amount = $totalRemaining;
            }

            // Récupérer ou créer le cycle actif
            $activeCycle = $tontine->activeCycle;
            if (!$activeCycle) {
                $activeCycle = $this->createTontineCycle($tontine);
            }

            // Répartition multi-cycles
            $cyclesAffected = $this->distributeTontineAmount($tontine, $activeCycle, $amount);

            // Créer la transaction
            $paymentReference = $validated['payment_reference'] ?? $this->generatePaymentReference($validated['payment_method']);
            $description = $validated['description'] ?? 'Cotisation tontine (Cycles: ' . implode(', ', array_column($cyclesAffected, 'cycle_number')) . ')';

            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $account->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'fee_amount' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'payment_method' => $validated['payment_method'],
                'mobile_money_operator' => $validated['mobile_money_operator'] ?? null,
                'payment_reference' => $paymentReference,
                'description' => $description,
                'status' => 'completed',
                'processed_by' => $user->id,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Mettre à jour le solde du compte
            $account->update([
                'balance' => $balanceBefore + $amount,
                'last_transaction_at' => now(),
            ]);

            // Mettre à jour le total payé de la tontine
            $tontine->increment('total_paid', $amount);

            DB::commit();

            $message = $this->generateMultiCycleMessage($amount, $cyclesAffected, $tontine->fresh());

            return response()->json([
                'message' => $message,
                'data' => [
                    'transaction' => $transaction,
                    'new_balance' => $account->fresh()->balance,
                    'cycles_affected' => $cyclesAffected,
                    'tontine_progress' => [
                        'total_paid' => $tontine->fresh()->total_paid,
                        'total_expected' => $tontine->total_expected,
                        'remaining' => $tontine->total_expected - $tontine->fresh()->total_paid,
                        'progress_percent' => round(($tontine->fresh()->total_paid / $tontine->total_expected) * 100, 2)
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Compte non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur dépôt tontine', [
                'account_id' => $accountId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors du dépôt',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Historique des transactions d'un compte
     */
    public function transactions(int $accountId, Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $account = Account::with('client')
                ->whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->findOrFail($accountId);

            $query = Transaction::where('account_id', $accountId)
                ->with(['processedBy']);

            // Filtres
            if ($request->filled('type')) {
                $query->where('transaction_type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $transactions = $query->latest('transaction_date')->paginate(30);

            return response()->json([
                'data' => [
                    'account' => $account,
                    'transactions' => $transactions->items()
                ],
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Compte non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur récupération transactions', [
                'account_id' => $accountId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des transactions'
            ], 500);
        }
    }

    /**
     * Recherche de comptes tontine pour dépôt rapide
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
        ]);

        try {
            $user = auth()->user();
            $query = $validated['query'];

            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $accounts = Account::with([
                'client',
                'tontineAccount.activeCycle',
                'tontineAccount.cycles' => function($q) {
                    $q->orderBy('cycle_number', 'desc')->limit(3);
                }
            ])
            ->whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine')
            ->where('status', 'active')
            ->where(function($q) use ($query) {
                $q->where('account_number', 'like', "%{$query}%")
                    ->orWhereHas('client', function($q2) use ($query) {
                        $q2->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('client_number', 'like', "%{$query}%")
                            ->orWhere('phone', 'like', "%{$query}%");
                    });
            })
            ->limit(10)
            ->get()
            ->map(function($account) {
                return $this->formatAccountForSearch($account);
            });

            return response()->json([
                'data' => $accounts
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur recherche comptes', [
                'query' => $validated['query'] ?? null,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    /**
     * Statistiques du tableau de bord agent
     */
    public function dashboard(): JsonResponse
    {
        try {
            $user = auth()->user();
            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $stats = [
                // Comptes
                'total_accounts' => Account::whereIn('client_id', $clientIds)
                    ->where('account_type', 'tontine')->count(),
                'active_accounts' => Account::whereIn('client_id', $clientIds)
                    ->where('account_type', 'tontine')
                    ->where('status', 'active')->count(),

                // Montants
                'total_balance' => Account::whereIn('client_id', $clientIds)
                    ->where('account_type', 'tontine')
                    ->where('status', 'active')
                    ->sum('balance'),

                // Transactions aujourd'hui
                'deposits_today' => Transaction::whereHas('account', function($q) use ($clientIds) {
                        $q->whereIn('client_id', $clientIds)
                          ->where('account_type', 'tontine');
                    })
                    ->where('transaction_type', 'deposit')
                    ->whereDate('transaction_date', today())
                    ->count(),

                'amount_today' => Transaction::whereHas('account', function($q) use ($clientIds) {
                        $q->whereIn('client_id', $clientIds)
                          ->where('account_type', 'tontine');
                    })
                    ->where('transaction_type', 'deposit')
                    ->whereDate('transaction_date', today())
                    ->sum('amount'),

                // Cette semaine
                'deposits_this_week' => Transaction::whereHas('account', function($q) use ($clientIds) {
                        $q->whereIn('client_id', $clientIds)
                          ->where('account_type', 'tontine');
                    })
                    ->where('transaction_type', 'deposit')
                    ->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),

                'amount_this_week' => Transaction::whereHas('account', function($q) use ($clientIds) {
                        $q->whereIn('client_id', $clientIds)
                          ->where('account_type', 'tontine');
                    })
                    ->where('transaction_type', 'deposit')
                    ->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->sum('amount'),
            ];

            return response()->json([
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération statistiques dashboard', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Générer un numéro de compte unique
     */
    private function generateAccountNumber(string $type): string
    {
        $prefix = $type === 'savings' ? 'SAV' : 'ACC';

        do {
            $number = $prefix . '-' . date('ym') . '-' . strtoupper(Str::random(6));
        } while (Account::where('account_number', $number)->exists());

        return $number;
    }

    /**
     * Générer une référence de transaction unique
     */
    private function generateTransactionReference(): string
    {
        do {
            $reference = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
        } while (Transaction::where('transaction_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Générer une référence de paiement
     */
    private function generatePaymentReference(string $method): string
    {
        $prefix = match($method) {
            'cash' => 'CASH',
            'bank_transfer' => 'BANK',
            'mobile_money' => 'MOMO',
            default => 'PAY',
        };

        do {
            $reference = $prefix . '-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
        } while (Transaction::where('payment_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Créer un cycle de tontine
     */
    private function createTontineCycle(TontineAccount $tontineAccount): TontineCycle
    {
        $cycleNumber = $tontineAccount->cycles()->count() + 1;

        if ($cycleNumber == 1) {
            $startDate = now();
        } else {
            $lastCycle = $tontineAccount->cycles()->latest('cycle_number')->first();
            $startDate = $lastCycle ? $lastCycle->end_date->copy()->addDay() : now();
        }

        switch ($tontineAccount->payment_frequency) {
            case 'daily':
                $endDate = $startDate->copy()->addDay();
                break;
            case 'weekly':
                $endDate = $startDate->copy()->addWeek();
                break;
            case 'monthly':
                $endDate = $startDate->copy()->addMonth();
                break;
            default:
                $endDate = $startDate->copy()->addMonth();
        }

        return TontineCycle::create([
            'tontine_account_id' => $tontineAccount->id,
            'cycle_number' => $cycleNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'target_amount' => $tontineAccount->tontine_amount,
            'collected_amount' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Obtenir ou créer le cycle suivant
     */
    private function getOrCreateNextCycle(TontineAccount $tontineAccount, TontineCycle $currentCycle): TontineCycle
    {
        $nextCycle = TontineCycle::where('tontine_account_id', $tontineAccount->id)
            ->where('cycle_number', $currentCycle->cycle_number + 1)
            ->first();

        if (!$nextCycle) {
            $nextCycle = $this->createTontineCycle($tontineAccount);
        }

        return $nextCycle;
    }

    /**
     * Distribuer un montant sur plusieurs cycles
     */
    private function distributeTontineAmount(TontineAccount $tontine, TontineCycle $startCycle, float $amount): array
    {
        $remainingAmount = $amount;
        $cyclesAffected = [];
        $currentCycle = $startCycle;

        while ($remainingAmount > 0) {
            $cycleRemaining = $currentCycle->target_amount - $currentCycle->collected_amount;

            if ($cycleRemaining <= 0) {
                $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                continue;
            }

            $amountForThisCycle = min($remainingAmount, $cycleRemaining);
            $newCollectedAmount = $currentCycle->collected_amount + $amountForThisCycle;

            $currentCycle->update([
                'collected_amount' => $newCollectedAmount,
            ]);

            $isCompleted = $newCollectedAmount >= $currentCycle->target_amount;
            $cyclesAffected[] = [
                'cycle_number' => $currentCycle->cycle_number,
                'amount' => $amountForThisCycle,
                'completed' => $isCompleted,
            ];

            $remainingAmount -= $amountForThisCycle;

            if ($isCompleted) {
                $currentCycle->update([
                    'status' => 'completed',
                    'payout_date' => now(),
                ]);

                if ($remainingAmount > 0) {
                    $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                }
            }
        }

        return $cyclesAffected;
    }

    /**
     * Générer un message détaillé pour les dépôts multi-cycles
     */
    private function generateMultiCycleMessage(float $totalAmount, array $cyclesAffected, TontineAccount $tontine): string
    {
        $nbCycles = count($cyclesAffected);
        $completedCycles = array_filter($cyclesAffected, fn($c) => $c['completed']);
        $nbCompleted = count($completedCycles);

        $message = '✅ Cotisation enregistrée : ' . number_format($totalAmount, 0, ',', ' ') . ' FCFA';

        if ($nbCycles === 1) {
            $cycle = $cyclesAffected[0];
            if ($cycle['completed']) {
                $message .= ' | 🎉 Cycle #' . $cycle['cycle_number'] . ' complété !';
            }
        } else {
            $message .= ' | 📈 Réparti sur ' . $nbCycles . ' cycle(s)';
            if ($nbCompleted > 0) {
                $message .= ' | 🎉 ' . $nbCompleted . ' cycle(s) complété(s) !';
            }
        }

        $progress = ($tontine->total_paid / $tontine->total_expected) * 100;
        $message .= ' | 📊 Progression : ' . number_format($progress, 1) . '%';

        if ($tontine->total_paid >= $tontine->total_expected) {
            $message .= ' | 🎊 FÉLICITATIONS ! Tontine complète !';
        }

        return $message;
    }

    /**
     * Formater un compte pour la recherche rapide
     */
    private function formatAccountForSearch(Account $account): array
    {
        $tontine = $account->tontineAccount;

        $data = [
            'id' => $account->id,
            'account_number' => $account->account_number,
            'account_type' => 'tontine',
            'balance' => $account->balance,
            'client' => [
                'name' => $account->client->first_name . ' ' . $account->client->last_name,
                'client_number' => $account->client->client_number,
                'phone' => $account->client->phone,
            ],
            'can_deposit' => true,
        ];

        // Informations tontine
        $data['tontine'] = [
            'tontine_amount' => $tontine->tontine_amount,
            'payment_frequency' => $tontine->payment_frequency,
            'cycle_duration_months' => $tontine->cycle_duration_months,
            'total_expected' => $tontine->total_expected,
            'total_paid' => $tontine->total_paid,
            'total_remaining' => $tontine->total_expected - $tontine->total_paid,
            'total_progress' => $tontine->total_expected > 0
                ? round(($tontine->total_paid / $tontine->total_expected) * 100, 2)
                : 0,
            'is_complete' => $tontine->total_paid >= $tontine->total_expected,
        ];

        // Vérifier si la tontine est complète
        if ($data['tontine']['is_complete']) {
            $data['can_deposit'] = false;
            $data['deposit_blocked_reason'] = 'Cette tontine est complète (objectif atteint)';
        }

        // Informations sur le cycle actif
        $activeCycle = $tontine->activeCycle;
        if ($activeCycle) {
            $data['tontine']['active_cycle'] = [
                'cycle_number' => $activeCycle->cycle_number,
                'target_amount' => $activeCycle->target_amount,
                'collected_amount' => $activeCycle->collected_amount,
                'remaining_amount' => $activeCycle->target_amount - $activeCycle->collected_amount,
                'progress_percent' => $activeCycle->target_amount > 0
                    ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2)
                    : 0,
                'start_date' => $activeCycle->start_date->format('d/m/Y'),
                'end_date' => $activeCycle->end_date->format('d/m/Y'),
            ];

            $remainingTotal = $tontine->total_expected - $tontine->total_paid;
            $data['tontine']['suggested_amount'] = min(
                $tontine->tontine_amount,
                $remainingTotal
            );
            $data['tontine']['max_deposit_amount'] = $remainingTotal;
        } else {
            $data['tontine']['active_cycle'] = null;
            $data['tontine']['message'] = 'Aucun cycle actif - Sera créé automatiquement lors du dépôt';
            $data['tontine']['suggested_amount'] = $tontine->tontine_amount;
            $data['tontine']['max_deposit_amount'] = $tontine->total_expected - $tontine->total_paid;
        }

        // Historique des derniers cycles
        $data['tontine']['recent_cycles'] = $tontine->cycles()
            ->latest('cycle_number')
            ->limit(3)
            ->get()
            ->map(function($cycle) {
                return [
                    'cycle_number' => $cycle->cycle_number,
                    'status' => $cycle->status,
                    'collected_amount' => $cycle->collected_amount,
                    'target_amount' => $cycle->target_amount,
                    'completion_rate' => $cycle->target_amount > 0
                        ? round(($cycle->collected_amount / $cycle->target_amount) * 100)
                        : 0,
                ];
            });

        return $data;
    }
}
