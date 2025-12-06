<?php

namespace App\Http\Controllers\web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentAccountController extends Controller
{
    /**
     * Liste de tous les comptes tontine de l'agent
     */
    public function index(Request $request)
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
            'suspended_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'suspended')->count(),
            'total_balance' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')
                ->sum('balance'),
        ];

        return view('agent.accounts.index', compact('accounts', 'stats'));
    }

    /**
     * Afficher le formulaire de création de compte tontine
     */
    public function create($clientId)
    {
        $user = auth()->user();

        // Vérifier que le client appartient à l'agent
        $client = Client::where('id', $clientId)
            ->where('registered_by', $user->id)
            ->with(['accounts'])
            ->firstOrFail();

        // Vérifier que le KYC est approuvé
        if ($client->kyc_status !== 'approved') {
            return redirect()
                ->route('agent.clients.show', $clientId)
                ->with('error', 'Le client doit avoir un KYC approuvé avant de créer un compte.');
        }

        return view('agent.accounts.create', compact('client'));
    }

    /**
     * Créer un nouveau compte tontine
     */
    public function store(Request $request, $clientId)
    {
        $request->validate([
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

            if ($client->kyc_status !== 'approved') {
                throw new \Exception('Le KYC du client doit être approuvé.');
            }

            // 1. Créer le compte de base
            $account = Account::create([
                'client_id' => $clientId,
                'account_number' => $this->generateAccountNumber('tontine'),
                'account_type' => 'tontine',
                'status' => 'suspended',
                'activation_fee' => 0,
                'balance' => 0,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            // 2. Calcul du nombre de périodes selon la fréquence
            $startDate = now();
            $endDate = (clone $startDate)->addMonths((int) $request->cycle_duration_months);

            $totalPeriods = 0;
            switch ($request->payment_frequency) {
                case 'daily':
                    $totalPeriods = $startDate->diffInDays($endDate);
                    break;
                case 'weekly':
                    $totalPeriods = $startDate->diffInWeeks($endDate);
                    break;
                case 'monthly':
                    $totalPeriods = (int) $request->cycle_duration_months;
                    break;
            }

            // 3. Calcul des montants
            $targetAmount = (float) $request->target_amount;
            $totalExpected = $targetAmount * $totalPeriods;

            // 4. Création du compte tontine
            $tontineAccount = TontineAccount::create([
                'account_id' => $account->id,
                'tontine_amount' => $targetAmount,
                'cycle_duration_months' => (int) $request->cycle_duration_months,
                'payment_frequency' => $request->payment_frequency,
                'expected_monthly_payment' => $targetAmount,
                'total_expected' => $totalExpected,
                'total_paid' => 0,
                'penalty_rate' => 0.05,
                'total_penalties' => 0,
                'cycle_start_date' => $startDate,
                'cycle_end_date' => $endDate,
            ]);

            DB::commit();

            return redirect()
                ->route('agent.accounts.show', $account->id)
                ->with('success', 'Compte tontine créé avec succès. Le compte doit être activé avant utilisation.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du compte: ' . $e->getMessage());
        }
    }

    /**
     * Afficher les détails d'un compte tontine
     */
    public function show($accountId)
    {
        $user = auth()->user();

        // Récupérer les IDs des clients de l'agent
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

        return view('agent.accounts.show', compact('account', 'stats'));
    }

    /**
     * Formulaire d'activation du compte tontine
     */
    public function activateForm($accountId)
    {
        $user = auth()->user();
        $clientIds = Client::where('registered_by', $user->id)->pluck('id');

        $account = Account::with(['client', 'tontineAccount'])
            ->whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine')
            ->where('status', 'suspended')
            ->findOrFail($accountId);

        return view('agent.accounts.activate', compact('account'));
    }

    /**
     * Activer le compte tontine
     */
    public function activate(Request $request, $accountId)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'mobile_money_operator' => 'nullable|in:tmoney,flooz',
            'payment_reference' => 'nullable|string|max:100',
            'initial_deposit' => 'nullable|numeric|min:0',
        ]);

        if ($request->payment_method === 'mobile_money' && !$request->filled('mobile_money_operator')) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Veuillez sélectionner un opérateur Mobile Money.');
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
            $this->createTontineCycle($account->tontineAccount);

            // Enregistrer le dépôt initial si fourni
            if (!empty($request->initial_deposit) && $request->initial_deposit > 0) {
                $amount = $request->initial_deposit;
                $balanceAfter = $account->balance + $amount;

                Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $amount,
                    'fee_amount' => 0,
                    'balance_before' => $account->balance,
                    'balance_after' => $balanceAfter,
                    'payment_method' => $request->payment_method,
                    'mobile_money_operator' => $request->mobile_money_operator,
                    'payment_reference' => $request->payment_reference ?? $this->generatePaymentReference($request->payment_method),
                    'description' => 'Dépôt initial à l\'activation',
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                $account->increment('balance', $amount);

                // Mettre à jour le cycle avec le dépôt initial
                $activeCycle = $account->tontineAccount->activeCycle;
                if ($activeCycle) {
                    $activeCycle->increment('collected_amount', $amount);
                }

                $account->tontineAccount->increment('total_paid', $amount);
            }

            DB::commit();

            $message = 'Compte tontine activé avec succès.';
            if (!empty($request->initial_deposit) && $request->initial_deposit > 0) {
                $message .= ' Dépôt initial de ' . number_format($request->initial_deposit, 0, ',', ' ') . ' FCFA enregistré.';
            }

            return redirect()
                ->route('agent.accounts.show', $accountId)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de l\'activation: ' . $e->getMessage());
        }
    }

    /**
     * Formulaire de dépôt sur compte tontine
     */
    public function depositForm($accountId)
    {
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
        ->findOrFail($accountId);

        $tontine = $account->tontineAccount;
        $activeCycle = $tontine->activeCycle;

        $data = [
            'account' => $account,
            'suggestedAmount' => $tontine->tontine_amount,
            'activeCycle' => $activeCycle,
            'remainingAmount' => $activeCycle ? ($activeCycle->target_amount - $activeCycle->collected_amount) : 0,
            'totalRemaining' => $tontine->total_expected - $tontine->total_paid,
        ];

        return view('agent.accounts.deposit', $data);
    }

    /**
     * Traiter le dépôt sur compte tontine (avec logique multi-cycles)
     */
    public function processDeposit(Request $request, $accountId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'mobile_money_operator' => 'nullable|in:tmoney,flooz',
            'payment_reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($request->payment_method === 'mobile_money' && !$request->filled('mobile_money_operator')) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Veuillez sélectionner un opérateur Mobile Money.');
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
            $amount = $request->amount;
            $balanceBefore = $account->balance;

            // Vérifier que la tontine n'est pas complète
            $totalRemaining = $tontine->total_expected - $tontine->total_paid;

            if ($totalRemaining <= 0) {
                throw new \Exception(
                    'Cette tontine est complète ! Total atteint : ' .
                    number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA'
                );
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
            $paymentReference = $request->payment_reference ?: $this->generatePaymentReference($request->payment_method);
            $description = $request->description ?: 'Cotisation tontine (Cycles: ' . implode(', ', array_column($cyclesAffected, 'cycle_number')) . ')';

            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $account->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'fee_amount' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'payment_method' => $request->payment_method,
                'mobile_money_operator' => $request->mobile_money_operator,
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

            return redirect()
                ->route('agent.accounts.show', $accountId)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors du dépôt: ' . $e->getMessage());
        }
    }

    /**
     * Historique des transactions d'un compte
     */
    public function transactions($accountId, Request $request)
    {
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

        return view('agent.accounts.transactions', compact('account', 'transactions'));
    }

    // =============== MÉTHODES PRIVÉES ===============

    /**
     * Générer un numéro de compte unique
     */
    private function generateAccountNumber($type): string
    {
        $prefix = 'TON';

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
    private function generatePaymentReference($method): string
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
                $message .= '<br>🎉 Cycle #' . $cycle['cycle_number'] . ' complété !';
            }
        } else {
            $message .= '<br>📈 Réparti sur ' . $nbCycles . ' cycle(s)';
            if ($nbCompleted > 0) {
                $message .= '<br>🎉 ' . $nbCompleted . ' cycle(s) complété(s) !';
            }
        }

        $progress = ($tontine->total_paid / $tontine->total_expected) * 100;
        $message .= '<br><br>📊 Progression totale : ' . number_format($progress, 1) . '%';

        if ($tontine->total_paid >= $tontine->total_expected) {
            $message .= '<br><br>🎊 <strong>FÉLICITATIONS ! La tontine est complète !</strong>';
        }

        return $message;
    }

    // =============== DÉPÔT RAPIDE ===============

    /**
     * Page de dépôt rapide (recherche + dépôt sur la même page)
     */
    public function quickDepositForm()
    {
        return view('agent.accounts.quick-deposit');
    }

    /**
     * Recherche AJAX de comptes tontine pour dépôt rapide
     */
    public function quickDepositSearch(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $user = auth()->user();
        $query = $request->get('query');

        // Récupérer les IDs des clients de l'agent
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
        ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts->map(function($account) {
                return $this->formatAccountForSearch($account);
            })
        ]);
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
            'deposit_url' => route('agent.accounts.quick-deposit.process', $account->id),
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

    /**
     * Traiter le dépôt rapide
     */
    public function processQuickDeposit(Request $request, $accountId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'mobile_money_operator' => 'nullable|in:tmoney,flooz',
            'payment_reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($request->payment_method === 'mobile_money' && !$request->filled('mobile_money_operator')) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner un opérateur Mobile Money.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Récupérer les IDs des clients de l'agent
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
            $amount = $request->amount;
            $balanceBefore = $account->balance;

            // Vérifier que la tontine n'est pas complète
            $totalRemaining = $tontine->total_expected - $tontine->total_paid;

            if ($totalRemaining <= 0) {
                throw new \Exception(
                    'Cette tontine est complète ! Total atteint : ' .
                    number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA'
                );
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
            $paymentReference = $request->payment_reference ?: $this->generatePaymentReference($request->payment_method);
            $description = $request->description ?: 'Cotisation tontine rapide (Cycles: ' . implode(', ', array_column($cyclesAffected, 'cycle_number')) . ')';

            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $account->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'fee_amount' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'payment_method' => $request->payment_method,
                'mobile_money_operator' => $request->mobile_money_operator,
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
                'success' => true,
                'message' => $message,
                'data' => [
                    'new_balance' => $account->fresh()->balance,
                    'transaction_reference' => $transaction->transaction_reference,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du dépôt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques du tableau de bord agent
     */
    public function dashboard()
    {
        $user = auth()->user();
        $clientIds = Client::where('registered_by', $user->id)->pluck('id');

        $stats = [
            // Comptes
            'total_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')->count(),
            'active_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'active')->count(),
            'suspended_accounts' => Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')
                ->where('status', 'suspended')->count(),

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

        return view('agent.accounts.dashboard', compact('stats'));
    }
}
