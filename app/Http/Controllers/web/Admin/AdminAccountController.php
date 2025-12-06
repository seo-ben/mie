<?php

namespace App\Http\Controllers\web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\User;
use App\Models\SavingsAccount;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Log;

use function Illuminate\Log\log;

class AdminAccountController extends Controller
{

        /**
         * Liste tous les comptes
         */
        public function index(Request $request)
        {
            $user = auth()->user();

            $query = Account::with([
                'client',
                'savingsAccount',
                'tontineAccount' => function($q) {
                    $q->select('id', 'account_id', 'tontine_amount', 'payment_frequency', 'cycle_duration_months');
                }
            ]);

            // Filtrage selon le rôle
            if ($user->role !== 'administrateur_systeme') {
                $query->whereHas('client', function($q) use ($user) {
                    $q->where('registered_by', $user->id);
                });
            }

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

            // Filtre par type de compte
            if ($request->filled('account_type')) {
                $query->where('account_type', $request->account_type);
            }

            // Filtre par statut
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Pagination de 11 éléments par page
            $accounts = $query->latest()->paginate(11);

            // Statistiques
            $stats = [
                'total_accounts' => Account::count(),
                'active_accounts' => Account::active()->count(),
                'suspended_accounts' => Account::suspended()->count(),
                'pending_accounts' => Account::pendingActivation()->count(),
                'savings_accounts' => Account::savings()->count(),
                'tontine_accounts' => Account::tontine()->count(),
                'total_balance' => Account::active()->sum('balance'),
            ];

            return view('admin.accounts.index', compact('accounts', 'stats'));
        }

        /**
         * Afficher le formulaire de création de compte pour un client
         */
        public function create($clientId)
        {
            $client = Client::with(['accounts'])->findOrFail($clientId);

            // Vérifier que le client a un KYC approuvé
            if ($client->kyc_status !== 'approved') {
                return redirect()
                    ->route('admin.clients.show', $clientId)
                    ->with('error', 'Le client doit avoir un KYC approuvé avant de créer un compte.');
            }

            // Vérifier si le client a déjà un compte d'épargne
            $hasSavingsAccount = $client->accounts()->where('account_type', 'savings')->exists();

            return view('admin.accounts.create', compact('client', 'hasSavingsAccount'));
        }

        /**
         * Créer un nouveau compte - CORRIGÉ
         */
        public function store(Request $request, $clientId)
        {
            $request->validate([
                'account_type' => 'required|in:savings,tontine',
                // Validation pour compte épargne
                'interest_rate' => 'nullable|numeric|min:0|max:100',
                'minimum_balance' => 'nullable|numeric|min:0',
                'monthly_fee' => 'nullable|numeric|min:0',
                // Validation pour tontine - CORRIGÉ
                'target_amount' => 'required_if:account_type,tontine|nullable|numeric|min:200',
                'cycle_duration_months' => 'required_if:account_type,tontine|nullable|integer|min:1|max:24',
                'payment_frequency' => 'required_if:account_type,tontine|nullable|in:daily,weekly,monthly',
            ]);

            try {
                DB::beginTransaction();

                // 1️⃣ Créer le compte de base
                $account = Account::create([
                    'client_id' => $clientId,
                    'account_number' => 'ACC-' . strtoupper(uniqid()),
                    'account_type' => $request->account_type,
                    'status' => 'suspended',
                    'activation_fee' => $request->account_type === 'savings' ? 7000 : 0,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'activated_by' => auth()->id(),
                    'activated_at' => now(),
                    'balance' => 0,
                ]);

                // 2️⃣ Si c'est un compte d'épargne
                if ($request->account_type === 'savings') {
                    SavingsAccount::create([
                        'account_id' => $account->id,
                        'interest_rate' => $request->interest_rate ?? 2.5,
                        'minimum_balance' => $request->minimum_balance ?? 5000,
                        'monthly_fee' => $request->monthly_fee ?? 500,
                        'last_interest_date' => null,
                    ]);
                }

                // 3️⃣ Si c'est une tontine - LOGIQUE CORRIGÉE
                if ($request->account_type === 'tontine') {
                    $startDate = now();
                    $endDate = (clone $startDate)->addMonths((int) $request->cycle_duration_months);

                    // 🔹 Calcul du nombre de périodes selon la fréquence
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

                    // 🔹 CORRECTION MAJEURE :
                    // target_amount = ce que la personne VEUT payer par période
                    // total_expected = target_amount × nombre de périodes
                    $targetAmount = (float) $request->target_amount;
                    $totalExpected = $targetAmount * $totalPeriods;

                    // 🔹 Création du compte tontine
                    $tontineAccount = TontineAccount::create([
                        'account_id' => $account->id,
                        'tontine_amount' => $targetAmount, // Montant par période
                        'cycle_duration_months' => (int) $request->cycle_duration_months,
                        'payment_frequency' => $request->payment_frequency,
                        'expected_monthly_payment' => $targetAmount, // Le même que tontine_amount
                        'total_expected' => $totalExpected, // Total à payer sur toute la durée
                        'total_paid' => 0,
                        'penalty_rate' => 0.05, // 5% par défaut
                        'total_penalties' => 0,
                        'cycle_start_date' => $startDate,
                        'cycle_end_date' => $endDate,
                    ]);

                    // Créer le premier cycle automatiquement
                    $this->createTontineCycle($tontineAccount);
                }

                DB::commit();

                return redirect()
                    ->route('admin.clients.show', $clientId)
                    ->with('success', 'Compte créé avec succès.');

            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Erreur lors de la création du compte: ' . $e->getMessage());
            }
        }



        /**
         * Afficher les détails d'un compte
         */
        public function show($accountId)
        {
            $account = Account::with([
                'client',
                'savingsAccount',
                'tontineAccount.cycles',
                'transactions' => function($q) {
                    $q->latest()->limit(20);
                },
                'activatedBy',
                'createdBy'
            ])->findOrFail($accountId);

            // Statistiques du compte
            $stats = [
                'total_deposits' => $account->transactions()
                    ->where('transaction_type', 'deposit')
                    ->where('status', 'completed')
                    ->sum('amount'),
                'total_withdrawals' => $account->transactions()
                    ->where('transaction_type', 'withdrawal')
                    ->where('status', 'completed')
                    ->sum('amount'),
                'transaction_count' => $account->transactions()->count(),
                'last_transaction' => $account->last_transaction_at,
            ];

            return view('admin.accounts.show', compact('account', 'stats'));
        }

        /**
         * Afficher le formulaire d'édition
         */
        public function edit($accountId)
        {
            $account = Account::with(['client', 'savingsAccount', 'tontineAccount'])
                ->findOrFail($accountId);

            // On ne peut pas éditer un compte actif
            if ($account->status === 'active') {
                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('error', 'Impossible de modifier un compte actif.');
            }

            return view('admin.accounts.edit', compact('account'));
        }

        /**
         * Mettre à jour un compte
         */
        public function update(Request $request, $accountId)
        {
            $account = Account::with(['savingsAccount', 'tontineAccount'])
                ->findOrFail($accountId);

            // Vérifier que le compte n'est pas actif
            if ($account->status === 'active') {
                return redirect()
                    ->back()
                    ->with('error', 'Impossible de modifier un compte actif.');
            }

            $request->validate([
                'interest_rate' => 'nullable|numeric|min:0|max:100',
                'minimum_balance' => 'nullable|numeric|min:0',
                'monthly_fee' => 'nullable|numeric|min:0',
                'tontine_amount' => 'nullable|numeric|min:1000',
                'cycle_duration_months' => 'nullable|integer|min:1|max:24',
                'payment_frequency' => 'nullable|in:daily,weekly,monthly',
            ]);

            try {
                DB::beginTransaction();

                if ($account->account_type === 'savings' && $account->savingsAccount) {
                    $account->savingsAccount->update([
                        'interest_rate' => $request->interest_rate,
                        'minimum_balance' => $request->minimum_balance,
                        'monthly_fee' => $request->monthly_fee,
                    ]);
                } elseif ($account->account_type === 'tontine' && $account->tontineAccount) {
                    $expectedMonthlyPayment = $this->calculateExpectedPayment(
                        $request->tontine_amount,
                        $request->cycle_duration_months,
                        $request->payment_frequency
                    );

                    $account->tontineAccount->update([
                        'tontine_amount' => $request->tontine_amount,
                        'cycle_duration_months' => $request->cycle_duration_months,
                        'payment_frequency' => $request->payment_frequency,
                        'expected_monthly_payment' => $expectedMonthlyPayment,
                        'total_expected' => $request->tontine_amount,
                    ]);
                }

                DB::commit();

                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('success', 'Compte mis à jour avec succès.');

            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
            }
        }

        /**
         * Suspendre un compte
         */
        public function suspend(Request $request, $accountId)
        {
            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            try {
                $account = Account::findOrFail($accountId);

                $account->update([
                    'status' => 'suspended',
                    'suspension_reason' => $request->reason,
                    'suspended_at' => now(),
                    'suspended_by' => auth()->id(),
                ]);

                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('success', 'Compte suspendu avec succès.');

            } catch (\Exception $e) {
                return redirect()
                    ->back()
                    ->with('error', 'Erreur lors de la suspension: ' . $e->getMessage());
            }
        }

        /**
         * Réactiver un compte suspendu
         */
        public function reactivate($accountId)
        {
            try {
                $account = Account::findOrFail($accountId);

                if ($account->status !== 'suspended') {
                    return redirect()
                        ->back()
                        ->with('error', 'Seul un compte suspendu peut être réactivé.');
                }

                $account->update([
                    'status' => 'active',
                    'suspension_reason' => null,
                    'activation_fee' => $this->getActivationFee($account->account_type),
                    'activated_at' => now(),
                    'activated_by' => auth()->id(),
                    'suspended_at' => null,
                    'suspended_by' => null,
                ]);

                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('success', 'Compte réactivé avec succès.');

            } catch (\Exception $e) {
                return redirect()
                    ->back()
                    ->with('error', 'Erreur lors de la réactivation: ' . $e->getMessage());
            }
        }

        /**
         * Historique des transactions d'un compte
         */
        public function transactions($accountId, Request $request)
        {
            $account = Account::with('client')->findOrFail($accountId);

            $query = Transaction::where('account_id', $accountId)
                ->with(['processedBy', 'validatedBy']);

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

            $transactions = $query->latest('transaction_date')->paginate(50);

            return view('admin.accounts.transactions', compact('account', 'transactions'));
        }

        // =============== MÉTHODES PRIVÉES ===============

        /**
         * Générer un numéro de compte unique
         */
        private function generateAccountNumber($type): string
        {
            $prefix = $type === 'savings' ? 'SAV' : 'TON';

            do {
                $number = $prefix . '-' . date('ym') . '-' . strtoupper(Str::random(6));
            } while (Account::where('account_number', $number)->exists());

            return $number;
        }

        /**
         * Obtenir les frais d'activation selon le type
         */
        private function getActivationFee($type): float
        {
            return $type === 'savings' ? 7000 : 0;
        }

        /**
         * Calculer le paiement mensuel attendu pour une tontine
         */
        private function calculateExpectedPayment($amount, $months, $frequency): float
        {
            $totalPayments = $months;

            switch ($frequency) {
                case 'daily':
                    $totalPayments = $months * 30;
                    break;
                case 'weekly':
                    $totalPayments = $months * 4;
                    break;
                case 'monthly':
                    $totalPayments = $months;
                    break;
            }

            return round($amount / $totalPayments, 2);
        }

        /**
         * Créer un cycle de tontine (version améliorée)
         */
        private function createTontineCycle(TontineAccount $tontineAccount): TontineCycle
        {
            $cycleNumber = $tontineAccount->cycles()->count() + 1;

            // Calculer les dates du cycle
            if ($cycleNumber == 1) {
                $startDate = now();
            } else {
                // Pour les cycles suivants, partir de la fin du cycle précédent
                $lastCycle = $tontineAccount->cycles()->latest('cycle_number')->first();
                $startDate = $lastCycle ? $lastCycle->end_date->copy()->addDay() : now();
            }

            // Calculer la durée du cycle selon la fréquence
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
                'target_amount' => $tontineAccount->tontine_amount, // Montant par période
                'collected_amount' => 0,
                'status' => 'active',
            ]);
        }

        /**
         * Recherche AJAX de comptes pour le transfert
         */
        public function searchAccounts(Request $request)
        {
            $request->validate([
                'query' => 'required|string|min:2',
                'exclude_account_id' => 'nullable|exists:accounts,id'
            ]);

            $user = auth()->user();
            $query = $request->get('query');
            $excludeId = $request->get('exclude_account_id');

            $accounts = Account::with(['client'])
                ->where('status', 'active')
                ->when($excludeId, function($q) use ($excludeId) {
                    $q->where('id', '!=', $excludeId);
                })
                ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                    $q->whereHas('client', function($q2) use ($user) {
                        $q2->where('registered_by', $user->id);
                    });
                })
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
                    return [
                        'id' => $account->id,
                        'account_number' => $account->account_number,
                        'account_type' => $account->account_type,
                        'balance' => $account->balance,
                        'client' => [
                            'name' => $account->client->first_name . ' ' . $account->client->last_name,
                            'client_number' => $account->client->client_number,
                            'phone' => $account->client->phone,
                        ]
                    ];
                })
            ]);
        }

        /**
         * Traiter le transfert d'argent
         */
        public function processTransfer(Request $request)
        {
            $request->validate([
                'source_account_id' => 'required|exists:accounts,id',
                'destination_account_id' => 'required|exists:accounts,id|different:source_account_id',
                'amount' => 'required|numeric|min:100',
                'description' => 'nullable|string|max:500',
                'transfer_fee' => 'nullable|numeric|min:0',
            ]);

            try {
                DB::beginTransaction();

                $user = auth()->user();

                // Récupérer les comptes
                $sourceAccount = Account::with('client')->findOrFail($request->source_account_id);
                $destinationAccount = Account::with('client')->findOrFail($request->destination_account_id);

                // Vérifications de sécurité
                if ($sourceAccount->status !== 'active') {
                    throw new \Exception('Le compte source n\'est pas actif.');
                }

                if ($destinationAccount->status !== 'active') {
                    throw new \Exception('Le compte destinataire n\'est pas actif.');
                }

                // Calculer les frais de transfert (0.5% ou montant personnalisé)
                $transferFee = $request->filled('transfer_fee')
                    ? $request->transfer_fee
                    : round($request->amount * 0.005, 2); // 0.5%

                $totalDebit = $request->amount + $transferFee;

                // Vérifier le solde suffisant
                if ($sourceAccount->balance < $totalDebit) {
                    throw new \Exception(
                        'Solde insuffisant. Disponible: ' . number_format($sourceAccount->balance, 0, ',', ' ') .
                        ' FCFA. Requis: ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA (incluant frais de ' .
                        number_format($transferFee, 0, ',', ' ') . ' FCFA)'
                    );
                }

                // Générer une référence de transfert unique
                $transferReference = $this->generateTransferReference();

                // 1. Débit du compte source
                $debitTransaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $sourceAccount->id,
                    'transaction_type' => 'transfer_out',
                    'amount' => $request->amount,
                    'fee_amount' => $transferFee,
                    'balance_before' => $sourceAccount->balance,
                    'balance_after' => $sourceAccount->balance - $totalDebit,
                    'payment_method' => 'internal_transfer',
                    'payment_reference' => $transferReference,
                    'description' => $request->description ??
                        'Transfert vers ' . $destinationAccount->client->first_name . ' ' .
                        $destinationAccount->client->last_name . ' (' . $destinationAccount->account_number . ')',
                    'related_account_id' => $destinationAccount->id,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // Mettre à jour le solde source
                $sourceAccount->decrement('balance', $totalDebit);
                $sourceAccount->update(['last_transaction_at' => now()]);

                // 2. Crédit du compte destinataire
                $creditTransaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $destinationAccount->id,
                    'transaction_type' => 'transfer_in',
                    'amount' => $request->amount,
                    'fee_amount' => 0,
                    'balance_before' => $destinationAccount->balance,
                    'balance_after' => $destinationAccount->balance + $request->amount,
                    'payment_method' => 'internal_transfer',
                    'payment_reference' => $transferReference,
                    'description' => $request->description ??
                        'Transfert reçu de ' . $sourceAccount->client->first_name . ' ' .
                        $sourceAccount->client->last_name . ' (' . $sourceAccount->account_number . ')',
                    'related_account_id' => $sourceAccount->id,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // Mettre à jour le solde destinataire
                $destinationAccount->increment('balance', $request->amount);
                $destinationAccount->update(['last_transaction_at' => now()]);

                DB::commit();

                return redirect()
                    ->route('admin.accounts.transfer-details', $debitTransaction->id)
                    ->with('success',
                        'Transfert effectué avec succès. Montant: ' .
                        number_format($request->amount, 0, ',', ' ') . ' FCFA. Frais: ' .
                        number_format($transferFee, 0, ',', ' ') . ' FCFA'
                    );

            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Erreur lors du transfert: ' . $e->getMessage());
            }
        }

        /**
         * Historique des transferts
         */
        public function transferHistory(Request $request)
        {
            $user = auth()->user();

            $query = Transaction::with(['account.client', 'relatedAccount.client', 'processedBy'])
                ->whereIn('transaction_type', ['transfer_in', 'transfer_out'])
                ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                    $q->whereHas('account.client', function($q2) use ($user) {
                        $q2->where('registered_by', $user->id);
                    });
                });

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('account.client', function($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('client_number', 'like', "%{$search}%");
                    });
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            if ($request->filled('type')) {
                $query->where('transaction_type', $request->type);
            }

            $transfers = $query->latest('transaction_date')->paginate(20);

            // Statistiques
            $stats = [
                'total_transfers' => (clone $query)->count(),
                'total_amount_sent' => (clone $query)->where('transaction_type', 'transfer_out')->sum('amount'),
                'total_amount_received' => (clone $query)->where('transaction_type', 'transfer_in')->sum('amount'),
                'total_fees_collected' => (clone $query)->where('transaction_type', 'transfer_out')->sum('fee_amount'),
                'transfers_today' => (clone $query)->whereDate('transaction_date', today())->count(),
            ];

            return view('admin.accounts.transfer-history', compact('transfers', 'stats'));
        }

        /**
         * Détails d'un transfert spécifique
         */
        public function transferDetails($transactionId)
        {
            $user = auth()->user();

            $transaction = Transaction::with([
                'account.client',
                'relatedAccount.client',
                'processedBy'
            ])
            ->whereIn('transaction_type', ['transfer_in', 'transfer_out'])
            ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                $q->whereHas('account.client', function($q2) use ($user) {
                    $q2->where('registered_by', $user->id);
                });
            })
            ->findOrFail($transactionId);

            // Récupérer la transaction liée (l'autre côté du transfert)
            $relatedTransaction = Transaction::where('payment_reference', $transaction->payment_reference)
                ->where('id', '!=', $transaction->id)
                ->with(['account.client'])
                ->first();

            return view('admin.accounts.transfer-details', compact('transaction', 'relatedTransaction'));
        }

        /**
         * Générer une référence de transfert unique
         */
        private function generateTransferReference(): string
        {
            do {
                $reference = 'TRF-' . date('YmdHis') . '-' . strtoupper(Str::random(6));
            } while (Transaction::where('payment_reference', $reference)->exists());

            return $reference;
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
         * Afficher le formulaire de retrait
         */
        public function withdrawalForm($accountId)
        {
            $account = Account::with(['client', 'savingsAccount', 'tontineAccount'])
                ->findOrFail($accountId);

            // Vérifier que le compte est actif
            if ($account->status !== 'active') {
                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('error', 'Impossible de faire un retrait sur un compte non actif.');
            }

            // Vérifier le solde disponible
            if ($account->balance <= 0) {
                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('error', 'Le solde du compte est insuffisant pour effectuer un retrait.');
            }

            // ✅ CALCUL CORRECT DU MONTANT MAXIMUM
            // Le client peut recevoir au maximum le solde MOINS les frais de 1%
            // Formule: maxAmount + (maxAmount * 0.01) = balance
            // maxAmount = balance / 1.01

            $minimumBalance = 0;

            if ($account->account_type === 'savings' && $account->savingsAccount) {
                $minimumBalance = $account->savingsAccount->minimum_balance ?? 0;
            }

            // Solde disponible pour retrait (après avoir gardé le solde minimum)
            $availableBalance = max(0, $account->balance - $minimumBalance);

            // ✅ Montant maximum que le client peut recevoir
            // On résout: maxAmount + (maxAmount * 0.01) <= availableBalance
            // maxAmount * 1.01 <= availableBalance
            // maxAmount <= availableBalance / 1.01
            $maxWithdrawal = floor($availableBalance / 1.01);

            return view('admin.accounts.withdrawal-form', compact('account', 'maxWithdrawal', 'minimumBalance'));
        }

        /**
         * Traiter le retrait
         */
        public function processWithdrawal(Request $request, $accountId)
        {
            $request->validate([
                'amount' => 'required|numeric|min:100',
                'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
                'withdrawal_fee' => 'nullable|numeric|min:0',
                'description' => 'nullable|string|max:500',
                'recipient_name' => 'required|string|max:255',
                'recipient_phone' => 'nullable|string|max:20',
                'recipient_id' => 'nullable|string|max:50',
            ]);

            try {
                DB::beginTransaction();

                $account = Account::with(['client', 'savingsAccount', 'tontineAccount'])
                    ->lockForUpdate()
                    ->findOrFail($accountId);

                // Vérifier que le compte est actif
                if ($account->status !== 'active') {
                    throw new \Exception('Le compte n\'est pas actif.');
                }

                // ✅ NOUVELLE LOGIQUE DE CALCUL
                $amountToGive = $request->amount; // Ce que le client reçoit

                // Calculer les frais de retrait (1% ou montant personnalisé)
                $withdrawalFee = $request->filled('withdrawal_fee')
                    ? $request->withdrawal_fee
                    : round($amountToGive * 0.01, 2); // 1%

                // ✅ TOTAL À DÉBITER = Montant + Frais
                $totalDebit = $amountToGive + $withdrawalFee;

                // VALIDATION SELON LE TYPE DE COMPTE
                if ($account->account_type === 'savings') {
                    // Pour l'épargne : vérifier le solde disponible
                    if ($account->balance < $totalDebit) {
                        throw new \Exception(
                            'Solde insuffisant pour effectuer ce retrait avec les frais. ' .
                            'Disponible: ' . number_format($account->balance, 0, ',', ' ') . ' FCFA. ' .
                            'Requis (montant + frais): ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA ' .
                            '(dont ' . number_format($withdrawalFee, 0, ',', ' ') . ' FCFA de frais)'
                        );
                    }

                    $minimumBalance = $account->savingsAccount->minimum_balance ?? 0;
                    $balanceAfter = $account->balance - $totalDebit;

                    // Avertissement si on passe sous le solde minimum
                    if ($balanceAfter < $minimumBalance && $balanceAfter > 0) {
                        \Log::warning("Retrait sous le solde minimum pour le compte {$account->account_number}");
                    }

                } elseif ($account->account_type === 'tontine') {
                    // Pour la tontine : vérifier le solde disponible
                    if ($account->balance < $totalDebit) {
                        throw new \Exception(
                            'Solde insuffisant pour effectuer ce retrait avec les frais. ' .
                            'Disponible: ' . number_format($account->balance, 0, ',', ' ') . ' FCFA. ' .
                            'Requis (montant + frais): ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA ' .
                            '(dont ' . number_format($withdrawalFee, 0, ',', ' ') . ' FCFA de frais)'
                        );
                    }

                    $balanceAfter = $account->balance - $totalDebit;
                    $willBeSuspended = ($balanceAfter == 0);

                    if ($willBeSuspended) {
                        session()->flash('warning', 'Attention: Ce retrait videra le compte qui sera automatiquement suspendu.');
                    }
                }

                // ✅ Créer la transaction de retrait avec la bonne structure
                $transaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $amountToGive, // ✅ Montant que le client reçoit
                    'fee_amount' => $withdrawalFee, // ✅ Frais stockés dans fee_amount
                    'withdrawal_fee' => $withdrawalFee, // ✅ Aussi dans withdrawal_fee pour compatibilité
                    'net_amount' => $amountToGive, // Le montant net = ce que le client reçoit
                    'balance_before' => $account->balance,
                    'balance_after' => $account->balance - $totalDebit, // ✅ Débit = montant + frais
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $this->generatePaymentReference($request->payment_method),
                    'description' => $request->description ?? 'Retrait de fonds',
                    'recipient_name' => $request->recipient_name,
                    'recipient_phone' => $request->recipient_phone,
                    'recipient_id' => $request->recipient_id,
                    'status' => 'completed',
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // ✅ Mettre à jour le solde du compte (débit total)
                $newBalance = $account->balance - $totalDebit;
                $account->update([
                    'balance' => $newBalance,
                    'last_transaction_at' => now(),
                ]);

                // RÈGLE SPÉCIALE TONTINE : Suspendre si le solde est à zéro
                if ($account->account_type === 'tontine' && $newBalance == 0) {
                    $account->update([
                        'status' => 'suspended',
                        'suspension_reason' => 'Compte vidé suite à un retrait total des fonds',
                        'suspended_at' => now(),
                        'suspended_by' => auth()->id(),
                    ]);

                    // Mettre à jour le cycle de tontine si applicable
                    if ($account->tontineAccount && $account->tontineAccount->activeCycle) {
                        $account->tontineAccount->activeCycle->update([
                            'status' => 'completed',
                            'completion_date' => now(),
                        ]);
                    }
                }

                DB::commit();

                // ✅ Message de succès détaillé
                $message = '✅ Retrait effectué avec succès<br>' .
                        '💰 Montant remis au client: ' . number_format($amountToGive, 0, ',', ' ') . ' FCFA<br>' .
                        '💳 Frais de retrait: ' . number_format($withdrawalFee, 0, ',', ' ') . ' FCFA<br>' .
                        '📊 Total débité du compte: ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA<br>' .
                        '💼 Nouveau solde: ' . number_format($newBalance, 0, ',', ' ') . ' FCFA';

                if ($account->account_type === 'tontine' && $newBalance == 0) {
                    $message .= '<br>⚠️ Le compte de tontine a été automatiquement suspendu.';
                }

                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('success', $message);

            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Erreur lors du retrait: ' . $e->getMessage());
            }
        }

        /**
         * Historique des retraits
         */
        public function withdrawalHistory(Request $request)
        {
            $user = auth()->user();

            $query = Transaction::with(['account.client', 'processedBy'])
                ->where('transaction_type', 'withdrawal')
                ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                    $q->whereHas('account.client', function($q2) use ($user) {
                        $q2->where('registered_by', $user->id);
                    });
                });

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhereHas('account.client', function($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('client_number', 'like', "%{$search}%");
                    });
                });
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $withdrawals = $query->latest('transaction_date')->paginate(20);

            // Statistiques
            $stats = [
                'total_withdrawals' => (clone $query)->count(),
                'total_amount' => (clone $query)->sum('amount'),
                'total_fees' => (clone $query)->sum('fee_amount'),
                'total_net' => (clone $query)->sum('net_amount'),
                'withdrawals_today' => (clone $query)->whereDate('transaction_date', today())->count(),
                'amount_today' => (clone $query)->whereDate('transaction_date', today())->sum('amount'),
            ];

            return view('admin.accounts.withdrawal-history', compact('withdrawals', 'stats'));
        }

        /**
         * Générer une référence de paiement selon la méthode
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
         * Afficher le formulaire de dépôt (CORRIGÉ - ne bloque plus si pas de cycle actif)
         */
        public function depositForm($accountId)
        {
            $account = Account::with([
                'client',
                'savingsAccount',
                'tontineAccount.activeCycle',
                'tontineAccount.cycles'
            ])->findOrFail($accountId);

            if ($account->status !== 'active') {
                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('error', 'Impossible de faire un dépôt sur un compte non actif.');
            }

            $data = [
                'account' => $account,
                'suggestedAmount' => 0,
                'activeCycle' => null,
                'remainingAmount' => 0,
                'progressPercent' => 0,
                'totalProgress' => 0,
            ];

            if ($account->account_type === 'tontine' && $account->tontineAccount) {
                $tontine = $account->tontineAccount;

                // ✅ NE PLUS BLOQUER si pas de cycle actif
                // Si aucun cycle actif, on crée automatiquement lors du dépôt
                $activeCycle = $tontine->activeCycle;

                if ($activeCycle) {
                    $data['activeCycle'] = $activeCycle;
                    $data['remainingAmount'] = $activeCycle->target_amount - $activeCycle->collected_amount;
                    $data['progressPercent'] = $activeCycle->target_amount > 0
                        ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2)
                        : 0;

                    // Suggérer le montant attendu ou le montant restant du cycle
                    $data['suggestedAmount'] = min(
                        $tontine->tontine_amount,
                        $data['remainingAmount']
                    );
                } else {
                    // Pas de cycle actif : suggérer le montant de base
                    $data['suggestedAmount'] = $tontine->tontine_amount;
                    $data['remainingAmount'] = $tontine->total_expected - $tontine->total_paid;
                }

                // Progression globale de la tontine
                $data['totalProgress'] = $tontine->total_expected > 0
                    ? round(($tontine->total_paid / $tontine->total_expected) * 100, 2)
                    : 0;

                $data['totalRemaining'] = $tontine->total_expected - $tontine->total_paid;

            } else {
                $data['suggestedAmount'] = 5000;
            }

            return view('admin.accounts.deposit-form', $data);
        }


        /**
         * Traiter le dépôt unifié avec gestion multi-cycles pour la tontine
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

                $account = Account::with([
                    'client',
                    'savingsAccount',
                    'tontineAccount.activeCycle',
                    'tontineAccount.cycles'
                ])->lockForUpdate()->findOrFail($accountId);

                if ($account->status !== 'active') {
                    throw new \Exception('Le compte n\'est pas actif.');
                }

                $amount = $request->amount;
                $balanceBefore = $account->balance;
                $balanceAfter = $balanceBefore + $amount;

                $paymentReference = $request->payment_reference ?: $this->generatePaymentReference($request->payment_method);

                $description = $request->description;
                if (!$description) {
                    if ($account->account_type === 'savings') {
                        $description = 'Dépôt sur compte d\'épargne';
                    } else {
                        $description = 'Cotisation tontine';
                    }
                }

                if ($account->account_type === 'tontine') {
                    $tontine = $account->tontineAccount;

                    // 🔒 VÉRIFICATION : Ne pas dépasser le total attendu de la tontine
                    $totalRemaining = $tontine->total_expected - $tontine->total_paid;

                    if ($totalRemaining <= 0) {
                        throw new \Exception(
                            'Cette tontine est complète ! Total atteint : ' .
                            number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA'
                        );
                    }

                    // Si le montant dépasse ce qui reste à payer, ajuster automatiquement
                    if ($amount > $totalRemaining) {
                        // Message d'information
                        session()->flash('info',
                            'Montant ajusté de ' . number_format($amount, 0, ',', ' ') .
                            ' à ' . number_format($totalRemaining, 0, ',', ' ') .
                            ' FCFA (montant restant pour compléter la tontine)'
                        );
                        $amount = $totalRemaining;
                        $balanceAfter = $balanceBefore + $amount;
                    }

                    // Récupérer ou créer le cycle actif
                    $activeCycle = $tontine->activeCycle;
                    if (!$activeCycle) {
                        // Si aucun cycle actif, en créer un nouveau automatiquement
                        $activeCycle = $this->createTontineCycle($tontine);
                    }

                    // 🔥 GESTION MULTI-CYCLES
                    $remainingAmount = $amount; // Montant restant à répartir
                    $cyclesAffected = []; // Pour le message de retour
                    $currentCycle = $activeCycle;

                    while ($remainingAmount > 0) {
                        // Calculer combien on peut mettre dans le cycle actuel
                        $cycleRemaining = $currentCycle->target_amount - $currentCycle->collected_amount;

                        if ($cycleRemaining <= 0) {
                            // Ce cycle est déjà complet, passer au suivant
                            $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                            continue;
                        }

                        // Montant à verser sur ce cycle (le minimum entre ce qui reste et l'objectif)
                        $amountForThisCycle = min($remainingAmount, $cycleRemaining);

                        // Mettre à jour le cycle
                        $newCollectedAmount = $currentCycle->collected_amount + $amountForThisCycle;
                        $currentCycle->update([
                            'collected_amount' => $newCollectedAmount,
                        ]);

                        // Enregistrer les infos du cycle affecté
                        $cyclesAffected[] = [
                            'cycle_number' => $currentCycle->cycle_number,
                            'amount' => $amountForThisCycle,
                            'completed' => $newCollectedAmount >= $currentCycle->target_amount
                        ];

                        // Soustraire du montant restant
                        $remainingAmount -= $amountForThisCycle;

                        // Si le cycle est complété
                        if ($newCollectedAmount >= $currentCycle->target_amount) {
                            $currentCycle->update([
                                'status' => 'completed',
                                'payout_date' => now(),
                            ]);

                            // Préparer le cycle suivant (si encore de l'argent à verser)
                            if ($remainingAmount > 0) {
                                $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                            }
                        }
                    }

                    // Créer la transaction UNIQUE pour tout le dépôt
                    $transaction = Transaction::create([
                        'transaction_reference' => $this->generateTransactionReference(),
                        'account_id' => $account->id,
                        'transaction_type' => 'deposit',
                        'amount' => $amount,
                        'fee_amount' => 0,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'payment_method' => $request->payment_method,
                        'mobile_money_operator' => $request->mobile_money_operator,
                        'payment_reference' => $paymentReference,
                        'description' => $description . ' (Multi-cycles: ' . implode(', ', array_column($cyclesAffected, 'cycle_number')) . ')',
                        'status' => 'completed',
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                        'transaction_date' => now(),
                    ]);

                    // Mettre à jour le solde du compte
                    $account->update([
                        'balance' => $balanceAfter,
                        'last_transaction_at' => now(),
                    ]);

                    // Mettre à jour le total payé de la tontine
                    $tontine->update([
                        'total_paid' => $tontine->total_paid + $amount,
                    ]);

                    // 📊 GÉNÉRER LE MESSAGE DE SUCCÈS
                    $message = $this->generateMultiCycleMessage($amount, $cyclesAffected, $tontine);

                } else {
                    // ===== COMPTE D'ÉPARGNE (INCHANGÉ) =====
                    $transaction = Transaction::create([
                        'transaction_reference' => $this->generateTransactionReference(),
                        'account_id' => $account->id,
                        'transaction_type' => 'deposit',
                        'amount' => $amount,
                        'fee_amount' => 0,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'payment_method' => $request->payment_method,
                        'mobile_money_operator' => $request->mobile_money_operator,
                        'payment_reference' => $paymentReference,
                        'description' => $description,
                        'status' => 'completed',
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                        'transaction_date' => now(),
                    ]);

                    $account->update([
                        'balance' => $balanceAfter,
                        'last_transaction_at' => now(),
                    ]);

                    $message = 'Dépôt enregistré avec succès. Nouveau solde: ' .
                            number_format($balanceAfter, 0, ',', ' ') . ' FCFA';
                }

                DB::commit();

                return redirect()
                    ->route('admin.accounts.show', $accountId)
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
         * Obtenir ou créer le cycle suivant
         */
        private function getOrCreateNextCycle(TontineAccount $tontineAccount, TontineCycle $currentCycle): TontineCycle
        {
            // Chercher le cycle suivant
            $nextCycle = TontineCycle::where('tontine_account_id', $tontineAccount->id)
                ->where('cycle_number', $currentCycle->cycle_number + 1)
                ->first();

            // Si pas trouvé, en créer un nouveau
            if (!$nextCycle) {
                $nextCycle = $this->createTontineCycle($tontineAccount);
            }

            return $nextCycle;
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
                // Un seul cycle affecté
                $cycle = $cyclesAffected[0];
                if ($cycle['completed']) {
                    $message .= '<br>🎉 Cycle #' . $cycle['cycle_number'] . ' complété !';
                } else {
                    // Calculer le restant
                    $remaining = $tontine->tontine_amount - $cycle['amount'];
                    $message .= '<br>📊 Cycle #' . $cycle['cycle_number'] . ' : reste ' .
                            number_format($remaining, 0, ',', ' ') . ' FCFA';
                }
            } else {
                // Plusieurs cycles affectés
                $message .= '<br>📈 Réparti sur ' . $nbCycles . ' cycle(s) :';

                foreach ($cyclesAffected as $cycle) {
                    $status = $cycle['completed'] ? '✓ Complété' : 'En cours';
                    $message .= '<br>&nbsp;&nbsp;• Cycle #' . $cycle['cycle_number'] . ' : ' .
                            number_format($cycle['amount'], 0, ',', ' ') . ' FCFA ' . $status;
                }

                if ($nbCompleted > 0) {
                    $message .= '<br><br>🎉 ' . $nbCompleted . ' cycle(s) complété(s) !';
                }
            }

            // Progression globale
            $progress = ($tontine->total_paid / $tontine->total_expected) * 100;
            $message .= '<br><br>📊 Progression totale : ' . number_format($progress, 1) . '% (' .
                    number_format($tontine->total_paid, 0, ',', ' ') . ' / ' .
                    number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA)';

            // Si tontine complète
            if ($tontine->total_paid >= $tontine->total_expected) {
                $message .= '<br><br>🎊 <strong>FÉLICITATIONS ! La tontine est complète !</strong>';
            }
            return $message;
        }

        /**
         * Historique des dépôts (épargne + tontine)
         */
        public function depositHistory(Request $request)
        {
            $user = auth()->user();

            $query = Transaction::with(['account.client', 'processedBy'])
                ->whereIn('transaction_type', ['deposit', 'deposit'])
                ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                    $q->whereHas('account.client', function($q2) use ($user) {
                        $q2->where('registered_by', $user->id);
                    });
                });

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('account.client', function($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('client_number', 'like', "%{$search}%");
                    });
                });
            }

            if ($request->filled('account_type')) {
                $query->whereHas('account', function($q) use ($request) {
                    $q->where('account_type', $request->account_type);
                });
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $deposits = $query->latest('transaction_date')->paginate(20);

            // Statistiques
            $stats = [
                'total_deposits' => (clone $query)->count(),
                'total_amount' => (clone $query)->sum('amount'),
                'savings_deposits' => (clone $query)->where('transaction_type', 'deposit')->count(),
                'tontine_contributions' => (clone $query)->where('transaction_type', 'deposit')->count(),
                'deposits_today' => (clone $query)->whereDate('transaction_date', today())->count(),
                'amount_today' => (clone $query)->whereDate('transaction_date', today())->sum('amount'),
            ];

            return view('admin.accounts.deposit-history', compact('deposits', 'stats'));
        }


        /**
         * Page de dépôt rapide (recherche + dépôt sur la même page)
         */
        public function depotform()
        {
            // $this->authorize('deposit', Account::class);

            return view('admin.accounts.depot');
        }

        /**
         * Recherche AJAX de comptes pour dépôt rapide (AMÉLIORÉE)
         */
        public function quickDepositSearch(Request $request)
        {
            // $this->authorize('deposit', Account::class);

            $request->validate([
                'query' => 'required|string|min:2',
            ]);

            $user = auth()->user();
            $query = $request->get('query');

            // Construction de la requête avec filtres de permission
            $accounts = Account::with([
                'client',
                'savingsAccount',
                'tontineAccount.activeCycle',
                'tontineAccount.cycles' => function($q) {
                    $q->orderBy('cycle_number', 'desc')->limit(3);
                }
            ])
            ->where('status', 'active')
            ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                $q->whereHas('client', function($q2) use ($user) {
                    $q2->where('registered_by', $user->id);
                });
            })
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
         * Formater un compte pour la recherche
         * NOUVEAU : Centralise la logique de formatage
         */
        private function formatAccountForSearch(Account $account): array
        {
            $data = [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
                'balance' => $account->balance,
                'client' => [
                    'name' => $account->client->first_name . ' ' . $account->client->last_name,
                    'client_number' => $account->client->client_number,
                    'phone' => $account->client->phone,
                ],
                'can_deposit' => true,
                'deposit_url' => route('admin.accounts.quick-deposit.process', $account->id),            ];

            // === LOGIQUE ÉPARGNE ===
            if ($account->account_type === 'savings' && $account->savingsAccount) {
                $data['savings'] = [
                    'interest_rate' => $account->savingsAccount->interest_rate,
                    'minimum_balance' => $account->savingsAccount->minimum_balance,
                    'monthly_fee' => $account->savingsAccount->monthly_fee,
                    'suggested_amount' => 5000, // Montant par défaut
                ];
            }

            // === LOGIQUE TONTINE ===
            if ($account->account_type === 'tontine' && $account->tontineAccount) {
                $tontine = $account->tontineAccount;

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
                        'is_complete' => $activeCycle->collected_amount >= $activeCycle->target_amount,
                    ];

                    // Calcul du montant suggéré intelligent
                    $remainingInCycle = $activeCycle->target_amount - $activeCycle->collected_amount;
                    $remainingTotal = $tontine->total_expected - $tontine->total_paid;

                    $data['tontine']['suggested_amount'] = min(
                        $tontine->tontine_amount, // Montant standard
                        $remainingTotal // Ne pas dépasser le total
                    );

                    $data['tontine']['max_deposit_amount'] = $remainingTotal;
                } else {
                    // Pas de cycle actif : sera créé automatiquement
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
            }

            return $data;
        }


        /**
         * Traiter le dépôt unifié avec logique multi-cycles
         * AMÉLIORÉ : Meilleure gestion des erreurs et messages
         */
        public function processQuickDeposit(Request $request, $accountId)
        {
            // $this->authorize('deposit', Account::class);

            $request->validate([
                'amount' => 'required|numeric|min:100',
                'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
                'mobile_money_operator' => 'nullable|in:tmoney,flooz',
                'payment_reference' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:500',
            ]);

            // Validation opérateur Mobile Money
            if ($request->payment_method === 'mobile_money' && !$request->filled('mobile_money_operator')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veuillez sélectionner un opérateur Mobile Money.'
                ], 422);
            }

            try {
                DB::beginTransaction();

                $account = Account::with([
                    'client',
                    'savingsAccount',
                    'tontineAccount.activeCycle',
                    'tontineAccount.cycles'
                ])->lockForUpdate()->findOrFail($accountId);

                // Vérifications de sécurité
                if ($account->status !== 'active') {
                    throw new \Exception('Le compte n\'est pas actif.');
                }

                // Vérifier les permissions utilisateur
                if (auth()->user()->role !== 'administrateur_systeme') {
                    if ($account->client->registered_by !== auth()->id()) {
                        throw new \Exception('Vous n\'avez pas la permission d\'accéder à ce compte.');
                    }
                }

                $amount = $request->amount;
                $balanceBefore = $account->balance;
                $balanceAfter = $balanceBefore + $amount;

                $paymentReference = $request->payment_reference ?: $this->generatePaymentReference($request->payment_method);
                $description = $request->description ?? $this->getDefaultDescription($account);

                // === TRAITEMENT SELON LE TYPE DE COMPTE ===
                if ($account->account_type === 'tontine') {
                    $result = $this->processTontineDeposit($account, $amount, $request, $paymentReference, $description);
                    $message = $result['message'];
                } else {
                    $result = $this->processSavingsDeposit($account, $amount, $request, $paymentReference, $description);
                    $message = $result['message'];
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'new_balance' => $account->fresh()->balance,
                        'transaction_reference' => $result['transaction_reference'],
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
         * NOUVEAU : Traiter un dépôt sur compte d'épargne
         */
        private function processSavingsDeposit(Account $account, float $amount, Request $request, string $paymentReference, string $description): array
        {
            $balanceBefore = $account->balance;
            $balanceAfter = $balanceBefore + $amount;

            $transaction = Transaction::create([
                'transaction_reference' => $this->generateTransactionReference(),
                'account_id' => $account->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'fee_amount' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $request->payment_method,
                'mobile_money_operator' => $request->mobile_money_operator,
                'payment_reference' => $paymentReference,
                'description' => $description,
                'status' => 'completed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            $account->update([
                'balance' => $balanceAfter,
                'last_transaction_at' => now(),
            ]);

            return [
                'transaction_reference' => $transaction->transaction_reference,
                'message' => '✅ Dépôt enregistré avec succès.<br>' .
                            '💰 Montant: ' . number_format($amount, 0, ',', ' ') . ' FCFA<br>' .
                            '📊 Nouveau solde: ' . number_format($balanceAfter, 0, ',', ' ') . ' FCFA'
            ];
        }

        /**
         * NOUVEAU : Traiter un dépôt tontine avec logique multi-cycles
         */
        private function processTontineDeposit(Account $account, float $amount, Request $request, string $paymentReference, string $description): array
        {
            $tontine = $account->tontineAccount;
            $balanceBefore = $account->balance;

            // 🔒 VÉRIFICATION : Ne pas dépasser le total attendu
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

            // 🔥 RÉPARTITION MULTI-CYCLES
            $cyclesAffected = $this->distributeTontineAmount($tontine, $activeCycle, $amount);

            // Créer la transaction UNIQUE
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
                'description' => $description . ' (Cycles: ' . implode(', ', array_column($cyclesAffected, 'cycle_number')) . ')',
                'status' => 'completed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            // Mettre à jour le compte et la tontine
            $account->update([
                'balance' => $balanceBefore + $amount,
                'last_transaction_at' => now(),
            ]);

            $tontine->update([
                'total_paid' => $tontine->total_paid + $amount,
            ]);

            // Générer le message détaillé
            $message = $this->generateMultiCycleMessage($amount, $cyclesAffected, $tontine->fresh());

            return [
                'transaction_reference' => $transaction->transaction_reference,
                'message' => $message,
                'cycles_affected' => $cyclesAffected,
            ];
        }

        /**
         * NOUVEAU : Distribuer un montant sur plusieurs cycles
         */
        private function distributeTontineAmount(TontineAccount $tontine, TontineCycle $startCycle, float $amount): array
        {
            $remainingAmount = $amount;
            $cyclesAffected = [];
            $currentCycle = $startCycle;

            while ($remainingAmount > 0) {
                // Montant restant pour ce cycle
                $cycleRemaining = $currentCycle->target_amount - $currentCycle->collected_amount;

                if ($cycleRemaining <= 0) {
                    // Cycle déjà complet, passer au suivant
                    $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                    continue;
                }

                // Montant à verser sur ce cycle
                $amountForThisCycle = min($remainingAmount, $cycleRemaining);

                // Mettre à jour le cycle
                $newCollectedAmount = $currentCycle->collected_amount + $amountForThisCycle;
                $currentCycle->update([
                    'collected_amount' => $newCollectedAmount,
                ]);

                // Enregistrer les infos
                $isCompleted = $newCollectedAmount >= $currentCycle->target_amount;
                $cyclesAffected[] = [
                    'cycle_number' => $currentCycle->cycle_number,
                    'amount' => $amountForThisCycle,
                    'completed' => $isCompleted,
                    'new_collected' => $newCollectedAmount,
                    'target' => $currentCycle->target_amount,
                ];

                // Soustraire du montant restant
                $remainingAmount -= $amountForThisCycle;

                // Si cycle complété
                if ($isCompleted) {
                    $currentCycle->update([
                        'status' => 'completed',
                        'payout_date' => now(),
                    ]);

                    // Préparer le cycle suivant si nécessaire
                    if ($remainingAmount > 0) {
                        $currentCycle = $this->getOrCreateNextCycle($tontine, $currentCycle);
                    }
                }
            }

            return $cyclesAffected;
        }

        /**
         * Obtenir la description par défaut selon le type de compte
         */
        private function getDefaultDescription(Account $account): string
        {
            if ($account->account_type === 'savings') {
                return 'Dépôt sur compte d\'épargne';
            } else {
                return 'Cotisation tontine';
            }
        }

}


