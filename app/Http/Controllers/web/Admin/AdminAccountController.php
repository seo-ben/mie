<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Client;
use App\Models\User;
use App\Models\SavingsAccount;
use App\Models\TontineAccount;
use App\Models\TontineCycle;
use App\Models\Transaction;
use App\Models\SystemParameter;
use App\Models\AuditLog;
use App\Models\CashierSession;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use function Illuminate\Log\log;

class AdminAccountController extends Controller
{

        /**
         * Liste tous les comptes
         */
        public function index(Request $request)
        {
            /** @var User $user */
            $user = Auth::user();

            $query = Account::with([
                'client',
                'savingsAccount',
                'tontineAccount' => function($q) {
                    $q->select('id', 'account_id', 'tontine_amount', 'payment_frequency', 'cycle_duration_months');
                }
            ]);

            // Filtrage par Agence (Isolation Multi-Agence)
            if ($user->role !== 'administrateur_systeme' && $user->role !== 'administrateur_reglementaire') {
                $query->whereHas('client', function($q) use ($user) {
                    $q->where('agency_id', $user->agency_id);
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

            // Récupérer les frais d'activation via les paramètres système
            $savingsActivationFee = SystemParameter::where('parameter_key', 'savings_account_activation_fee')->value('parameter_value') ?? 7000;
            
            // Logique de fallback pour les frais de tontine
            $tontineCarnetFee = SystemParameter::where('parameter_key', 'tontine_carnet_fee')->value('parameter_value');
            if (!$tontineCarnetFee) {
                $tontineCarnetFee = SystemParameter::where('parameter_key', 'tontine_300_activation_fee')->value('parameter_value') ?? 1000;
            }

            // Vérifier si le client a déjà un compte d'épargne
            $hasSavingsAccount = $client->accounts->where('account_type', 'savings')->count() > 0;

            return view('admin.accounts.create', compact('client', 'hasSavingsAccount', 'savingsActivationFee', 'tontineCarnetFee'));
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
                // 0ï¸â£ Vérification KYC pour les comptes épargne
                $client = Client::findOrFail($clientId);
                if ($request->account_type === 'savings' && $client->kyc_status !== 'approved') {
                     throw new \Exception('Le client doit avoir un KYC approuvé pour ouvrir un compte d\'épargne.');
                }

                DB::beginTransaction();

                // 1ï¸â£ Récupérer les frais d'activation via les paramètres système
                $activationFee = 0;
                if ($request->account_type === 'savings') {
                    $activationFee = SystemParameter::where('parameter_key', 'savings_account_activation_fee')->value('parameter_value') ?? 7000;
                } elseif ($request->account_type === 'tontine') {
                    // Utilisation du paramètre correct selon la configuration système (tontine_300_activation_fee ou tontine_carnet_fee)
                    // On privilégie tontine_carnet_fee si présent, sinon on cherche tontine_300_activation_fee, sinon défaut 1000
                    $activationFee = SystemParameter::where('parameter_key', 'tontine_carnet_fee')->value('parameter_value');
                    
                    if (!$activationFee) {
                         $activationFee = SystemParameter::where('parameter_key', 'tontine_300_activation_fee')->value('parameter_value') ?? 1000;
                    }
                }

                // 2ï¸â£ Créer le compte de base
                $account = Account::create([
                    'client_id' => $clientId,
                    'account_number' => 'ACC-' . strtoupper(uniqid()),
                    'account_type' => $request->account_type,
                    'status' => 'active', // Directement actif car les frais sont payés à la création
                    'activation_fee' => $activationFee,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'activated_by' => auth()->id(),
                    'activated_at' => now(),
                    'balance' => 0, // Le solde reste à 0 car les frais vont dans les revenus
                ]);

                // 3ï¸â£ Enregistrer les frais comme transaction
                Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'deposit',
                    'amount' => $activationFee,
                    'fee_amount' => $activationFee,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'payment_method' => 'cash',
                    'description' => ($request->account_type === 'savings' ? 'Frais d\'activation de compte d\'épargne' : 'Frais de carnet tontine'),
                    'status' => 'completed',
                    'processed_by' => auth()->id(),
                    'cashier_session_id' => $this->getCurrentSessionId(),
                    'agency_id' => auth()->user()->agency_id ?? $account->client->agency_id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // 4ï¸â£ Si c'est un compte d'épargne
                if ($request->account_type === 'savings') {
                    SavingsAccount::create([
                        'account_id' => $account->id,
                        'interest_rate' => $request->interest_rate ?? 2.5,
                        'minimum_balance' => $request->minimum_balance ?? 5000,
                        'monthly_fee' => $request->monthly_fee ?? 500,
                        'last_interest_date' => null,
                    ]);
                }

                // 5ï¸â£ Si c'est une tontine
                if ($request->account_type === 'tontine') {
                    $startDate = now();
                    $endDate = (clone $startDate)->addMonths((int) $request->cycle_duration_months);

                    // ð¹ Calcul du nombre de périodes (Règles institutionnelles : 31j par mois / 52s par an)
                    $totalPeriods = match ($request->payment_frequency) {
                        'daily' => (int) $request->cycle_duration_months * 31,
                        'weekly' => (int) round(((int) $request->cycle_duration_months * 52) / 12),
                        'monthly' => (int) $request->cycle_duration_months,
                        default => 0,
                    };

                    $targetAmount = (float) $request->target_amount;
                    $totalExpected = $targetAmount * $totalPeriods;

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

                    $this->createTontineCycle($tontineAccount);
                }

                DB::commit();

                return redirect()
                    ->route('admin.clients.show', $clientId)
                    ->with('success', 'Compte créé avec succès. Frais d\'activation de ' . number_format($activationFee, 0, ',', ' ') . ' FCFA enregistrés.');

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
         * Récupérer l'ID de la session de caisse active
         */
        private function getCurrentSessionId()
        {
            return CashierSession::where('user_id', auth()->id())
                ->where('status', 'open')
                ->value('id');
        }

        /**
         * Générer un numéro de compte unique
         */
        private function generateAccountNumber($type): string
        {
            $prefix = $type === 'savings' ? 'SAV' : 'ACC';

            do {
                $number = $prefix . '-' . date('ym') . '-' . strtoupper(Str::random(6));
            } while (Account::where('account_number', $number)->exists());

            return $number;
        }

        /**
         * Obtenir les frais d'activation selon le type
         */
        public function getActivationFee($type)
        {
            if ($type === 'savings') {
                return SystemParameter::where('parameter_key', 'savings_account_activation_fee')->value('parameter_value') ?? 7000;
            } elseif ($type === 'tontine') {
                return SystemParameter::where('parameter_key', 'tontine_carnet_fee')->value('parameter_value') ?? 1000;
            }
            return 0;
        }

        /**
         * Calculer le paiement mensuel attendu pour une tontine
         */
        private function calculateExpectedPayment($amount, $months, $frequency): float
        {
            $totalPayments = $months;

            switch ($frequency) {
                case 'daily':
                    $totalPayments = $months * 31; // Règle institutionnelle : 31 jours / mois
                    break;
                case 'weekly':
                    $totalPayments = (int) round(($months * 52) / 12); // Règle institutionnelle : 52 semaines / an
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
                $lastCycle = $tontineAccount->cycles()->latest('cycle_number')->first();
                $startDate = $lastCycle ? $lastCycle->end_date->copy()->addDay() : now();
            }

            // Un cycle représente un "mois" de tontine, soit 31 jours selon la règle institutionnelle
            $daysInCycle = 31;
            
            // Calculer la durée du cycle selon la fréquence
            switch ($tontineAccount->payment_frequency) {
                case 'daily':
                    $endDate = $startDate->copy()->addDays($daysInCycle);
                    $targetAmount = $tontineAccount->tontine_amount * $daysInCycle;
                    break;
                case 'weekly':
                    $endDate = $startDate->copy()->addMonths(1); // 1 mois calendaire
                    // 52 semaines / 12 mois = ~4.33 semaines par cycle
                    $targetAmount = $tontineAccount->tontine_amount * (52 / 12);
                    break;
                case 'monthly':
                    $endDate = $startDate->copy()->addMonths(1);
                    $targetAmount = $tontineAccount->tontine_amount;
                    break;
                default:
                    $endDate = $startDate->copy()->addMonths(1);
                    $targetAmount = $tontineAccount->tontine_amount;
            }

            return TontineCycle::create([
                'tontine_account_id' => $tontineAccount->id,
                'cycle_number' => $cycleNumber,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'target_amount' => $targetAmount,
                'collected_amount' => 0,
                'payout_amount' => 0,
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
                'exclude_account_id' => 'nullable|exists:accounts,id',
                'type' => 'nullable|in:source,destination'
            ]);

            $user = auth()->user();
            $query = $request->get('query');
            $excludeId = $request->get('exclude_account_id');
            $type = $request->get('type');

            $sourceAccount = null;
            if ($type === 'destination' && $excludeId) {
                $sourceAccount = Account::find($excludeId);
            }

            $accounts = Account::with(['client'])
                ->where('status', 'active')
                ->when($type === 'source', function($q) {
                    $q->where('account_type', 'tontine');
                })
                ->when($type === 'destination', function($q) use ($sourceAccount) {
                    $q->where('account_type', 'savings');
                    if ($sourceAccount) {
                        $q->where('client_id', $sourceAccount->client_id);
                    }
                })
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
         * Afficher le formulaire de transfert
         */
        public function transferForm(Request $request)
        {
            $sourceAccount = null;
            if ($request->has('account_id')) {
                $sourceAccount = Account::with('client')->find($request->account_id);
            }

            return view('admin.accounts.transfer', compact('sourceAccount'));
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

                if ($sourceAccount->client_id !== $destinationAccount->client_id) {
                    throw new \Exception('Le transfert ne peut être effectué qu\'entre les comptes d\'un même client.');
                }

                if ($sourceAccount->account_type !== 'tontine') {
                    throw new \Exception('Le compte source doit être un compte Tontine.');
                }

                if ($destinationAccount->account_type !== 'savings') {
                    throw new \Exception('Le compte destinataire doit être un compte Épargne.');
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
                    'payment_method' => 'system',
                    'payment_reference' => $transferReference,
                    'description' => $request->description ??
                        'Transfert vers ' . $destinationAccount->client->first_name . ' ' .
                        $destinationAccount->client->last_name . ' (' . $destinationAccount->account_number . ')',
                    'related_account_id' => $destinationAccount->id,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'cashier_session_id' => $this->getCurrentSessionId(),
                    'agency_id' => $user->agency_id,
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
                    'payment_method' => 'system',
                    'payment_reference' => $transferReference,
                    'description' => $request->description ??
                        'Transfert reçu de ' . $sourceAccount->client->first_name . ' ' .
                        $sourceAccount->client->last_name . ' (' . $sourceAccount->account_number . ')',
                    'related_account_id' => $sourceAccount->id,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'cashier_session_id' => $this->getCurrentSessionId(),
                    'agency_id' => $user->agency_id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // Mettre à jour le solde destinataire
                $destinationAccount->increment('balance', $request->amount);
                $destinationAccount->update(['last_transaction_at' => now()]);

                DB::commit();

                return redirect()
                    ->route('admin.accounts.transfer.details', $debitTransaction->id)
                    ->with('success',
                        'Transfert effectué avec succès. Montant: ' .
                        number_format($request->amount, 0, ',', ' ') . ' FCFA. Frais: ' .
                        number_format($transferFee, 0, ',', ' ') . ' FCFA'
                    )
                    ->with('print_receipt', route('admin.receipt.print', $debitTransaction->id));

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

            // â CALCUL CORRECT DU MONTANT MAXIMUM
            // Le client peut recevoir au maximum le solde MOINS les frais de 1%
            // Formule: maxAmount + (maxAmount * 0.01) = balance
            // maxAmount = balance / 1.01

            $minimumBalance = 0;

            if ($account->account_type === 'savings' && $account->savingsAccount) {
                $minimumBalance = $account->savingsAccount->minimum_balance ?? 0;
            }

            // Solde disponible pour retrait (après avoir gardé le solde minimum)
            $availableBalance = max(0, $account->balance - $minimumBalance);

            // Récupération des paramètres de frais
            $savingsFeePercentage = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_percentage')->value('parameter_value') ?? 2.0);
            $savingsFeeFixed = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_fixed')->value('parameter_value') ?? 0);
            
            $tontineFeePercentage = (float) (SystemParameter::where('parameter_key', 'tontine_withdrawal_fee_percentage')->value('parameter_value') ?? 3.0);
            $tontineFeeFixed = (float) (SystemParameter::where('parameter_key', 'tontine_withdrawal_fee_fixed')->value('parameter_value') ?? 0);

            // Déterminer les frais applicables pour ce compte
            if ($account->account_type === 'tontine') {
                $tontine = $account->tontineAccount;
                $mise = (float) $tontine->tontine_amount;
                $freq = $tontine->payment_frequency;
                
                // Pour le calcul du max, on simplifie car c'est par tranche. 
                // Pour être sûr, on prend au moins une mise de frais.
                $maxWithdrawal = max(0, $availableBalance - $mise);
                
                $feePercentage = 0;
                $feeFixed = $mise;
                $feeLabel = ($freq === 'daily') ? 'Règle 1/31' : (($freq === 'weekly') ? 'Règle 1/52' : '1 mise par retrait');
            } else {
                $feePercentage = $savingsFeePercentage;
                $feeFixed = $savingsFeeFixed;
                $maxWithdrawal = floor(max(0, ($availableBalance - $feeFixed) / (1 + ($feePercentage / 100))));
                $feeLabel = "{$feePercentage}% + " . number_format($feeFixed, 0, ',', ' ') . "F";
            }

            return view('admin.accounts.withdrawal-form', compact(
                'account', 
                'maxWithdrawal', 
                'minimumBalance',
                'feePercentage',
                'feeFixed',
                'feeLabel'
            ));
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

                // â NOUVELLE LOGIQUE DE CALCUL VIA PARAMÈTRES
                $amountToGive = $request->amount; // Ce que le client reçoit

                // Calculer les frais de retrait via les paramètres système
                if ($request->filled('withdrawal_fee')) {
                    $withdrawalFee = (float) $request->withdrawal_fee;
                } else {
                    // Sélection des paramètres selon le type de compte (tontine ou savings)
                    if ($account->account_type === 'tontine') {
                        $tontine = $account->tontineAccount;
                        $mise = (float) $tontine->tontine_amount;
                        
                        if ($tontine->payment_frequency === 'daily') {
                            $nbDaysTotal = $amountToGive / ($mise ?: 1);
                            $nbCommissions = ceil($nbDaysTotal / 31);
                            $withdrawalFee = $nbCommissions * $mise;
                        } elseif ($tontine->payment_frequency === 'weekly') {
                            $nbWeeksTotal = $amountToGive / ($mise ?: 1);
                            $nbCommissions = ceil($nbWeeksTotal / 52);
                            $withdrawalFee = $nbCommissions * $mise;
                        } else {
                            $withdrawalFee = $mise;
                        }
                    } else {
                        $feePercentage = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_percentage')->value('parameter_value') ?? 2.0);
                        $feeFixed = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_fixed')->value('parameter_value') ?? 0);
                        $withdrawalFee = round(($amountToGive * ($feePercentage / 100)) + $feeFixed);
                    }
                }

                // â Vérifier la session de caisse
                $sessionId = $this->getCurrentSessionId();
                // if (!$sessionId) {
                //     throw new \Exception('Vous devez avoir une session de caisse ouverte pour effectuer un retrait.');
                // }

                // â TOTAL À DÉBITER = Montant + Frais
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

                // â Créer la transaction de retrait avec la bonne structure
                $transaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $amountToGive, // â Montant que le client reçoit
                    'fee_amount' => $withdrawalFee, // â Frais stockés dans fee_amount
                    'withdrawal_fee' => $withdrawalFee, // â Aussi dans withdrawal_fee pour compatibilité
                    'net_amount' => $amountToGive, // Le montant net = ce que le client reçoit
                    'balance_before' => $account->balance,
                    'balance_after' => $account->balance - $totalDebit, // â Débit = montant + frais
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $this->generatePaymentReference($request->payment_method),
                    'description' => $request->description ?? 'Retrait de fonds',
                    'recipient_name' => $request->recipient_name,
                    'recipient_phone' => $request->recipient_phone,
                    'recipient_id' => $request->recipient_id,
                    'status' => 'completed',
                    'processed_by' => auth()->id(),
                    'cashier_session_id' => $this->getCurrentSessionId(),
                    'agency_id' => auth()->user()->agency_id,
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                // â Mettre à jour le solde du compte (débit total)
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

                // â Message de succès détaillé
                $message = 'â Retrait effectué avec succès<br>' .
                        'ð° Montant remis au client: ' . number_format($amountToGive, 0, ',', ' ') . ' FCFA<br>' .
                        'ð³ Frais de retrait: ' . number_format($withdrawalFee, 0, ',', ' ') . ' FCFA<br>' .
                        'ð Total débité du compte: ' . number_format($totalDebit, 0, ',', ' ') . ' FCFA<br>' .
                        'ð¼ Nouveau solde: ' . number_format($newBalance, 0, ',', ' ') . ' FCFA';

                if ($account->account_type === 'tontine' && $newBalance == 0) {
                    $message .= '<br>â ï¸ Le compte de tontine a été automatiquement suspendu.';
                }

                return redirect()
                    ->route('admin.accounts.show', $accountId)
                    ->with('success', $message)
                    ->with('print_receipt', route('admin.receipt.print', $transaction->id));

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

                // â NE PLUS BLOQUER si pas de cycle actif
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

                // â Vérifier la session de caisse
                $sessionId = $this->getCurrentSessionId();
                if (!$sessionId) {
                    throw new \Exception('Vous devez avoir une session de caisse ouverte pour effectuer un dépôt.');
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

                    // ð VÉRIFICATION : Ne pas dépasser le total attendu de la tontine
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

                    // Calcul des Pénalités de Retard Opérationnel
                    $penaltyAmount = 0;
                    if ($activeCycle && now()->gt($activeCycle->end_date)) {
                        $daysLate = now()->diffInDays($activeCycle->end_date);
                        $penaltyRate = DB::table('system_parameters')->where('parameter_key', 'tontine_late_penalty_rate')->value('parameter_value') ?? 0.01;
                        $penaltyAmount = $tontine->tontine_amount * $penaltyRate * $daysLate;
                        
                        if ($penaltyAmount > 0) {
                            $tontine->increment('total_penalties', $penaltyAmount);
                        }
                    }

                    // ð¥ GESTION MULTI-CYCLES & PROVISION POUR ALÉAS
                    $remainingAmount = $amount; // Montant restant à répartir
                    $cyclesAffected = []; // Pour le message de retour
                    $currentCycle = $activeCycle;
                    
                    // Calcul de la Provision pour Aléas (Fonds de Solidarité)
                    $solidarityRate = DB::table('system_parameters')->where('parameter_key', 'solidarity_fund_rate')->value('parameter_value') ?? 0.005;
                    $solidarityAmount = $amount * $solidarityRate;
                    $tontine->increment('solidarity_fund_total', $solidarityAmount);

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
                        'cashier_session_id' => $this->getCurrentSessionId(),
                        'agency_id' => auth()->user()->agency_id,
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

                    // ð GÉNÉRER LE MESSAGE DE SUCCÈS
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
                        'cashier_session_id' => $this->getCurrentSessionId(),
                        'agency_id' => auth()->user()->agency_id,
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
                    ->with('success', $message)
                    ->with('print_receipt', route('admin.receipt.print', $transaction->id));

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

            $message = 'â Cotisation enregistrée : ' . number_format($totalAmount, 0, ',', ' ') . ' FCFA';

            if ($nbCycles === 1) {
                // Un seul cycle affecté
                $cycle = $cyclesAffected[0];
                if ($cycle['completed']) {
                    $message .= '<br>ð Cycle #' . $cycle['cycle_number'] . ' complété !';
                } else {
                    // Calculer le restant
                    $remaining = $tontine->tontine_amount - $cycle['amount'];
                    $message .= '<br>ð Cycle #' . $cycle['cycle_number'] . ' : reste ' .
                            number_format($remaining, 0, ',', ' ') . ' FCFA';
                }
            } else {
                // Plusieurs cycles affectés
                $message .= '<br>ð Réparti sur ' . $nbCycles . ' cycle(s) :';

                foreach ($cyclesAffected as $cycle) {
                    $status = $cycle['completed'] ? 'â Complété' : 'En cours';
                    $message .= '<br>&nbsp;&nbsp;• Cycle #' . $cycle['cycle_number'] . ' : ' .
                            number_format($cycle['amount'], 0, ',', ' ') . ' FCFA ' . $status;
                }

                if ($nbCompleted > 0) {
                    $message .= '<br><br>ð ' . $nbCompleted . ' cycle(s) complété(s) !';
                }
            }

            // Progression globale
            $progress = ($tontine->total_paid / $tontine->total_expected) * 100;
            $message .= '<br><br>ð Progression totale : ' . number_format($progress, 1) . '% (' .
                    number_format($tontine->total_paid, 0, ',', ' ') . ' / ' .
                    number_format($tontine->total_expected, 0, ',', ' ') . ' FCFA)';

            // Si tontine complète
            if ($tontine->total_paid >= $tontine->total_expected) {
                $message .= '<br><br>ð <strong>FÉLICITATIONS ! La tontine est complète !</strong>';
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

            $loans = Loan::select('id', 'loan_number', 'client_id', 'status', 'total_amount_due', 'total_paid')
                ->with('client:id,first_name,last_name,phone,client_number')
                ->whereIn('status', ['approved', 'disbursed', 'active'])
                ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                    $q->whereHas('client', function($q2) use ($user) {
                        $q2->where('registered_by', $user->id);
                    });
                })
                ->where(function ($q) use ($query) {
                    $q->where('loan_number', 'like', "%{$query}%")
                        ->orWhereHas('client', function($q2) use ($query) {
                            $q2->where('first_name', 'like', "%{$query}%")
                                ->orWhere('last_name', 'like', "%{$query}%")
                                ->orWhere('phone', 'like', "%{$query}%")
                                ->orWhere('client_number', 'like', "%{$query}%");
                        });
                })
                ->limit(5)
                ->get();

            $accountResults = $accounts->map(function($account) {
                return $this->formatAccountForSearch($account);
            });

            $loanResults = $loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'account_number' => $loan->loan_number,
                    'account_type' => 'loan',
                    'balance' => collect([$loan->total_amount_due ?? 0, 0])->max() - ($loan->total_paid ?? 0),
                    'client' => [
                        'id' => $loan->client_id,
                        'name' => $loan->client->full_name,
                        'client_number' => $loan->client->client_number ?? '',
                        'phone' => $loan->client->phone ?? '',
                        'kyc_status' => $loan->client->kyc_status ?? 'N/A',
                    ],
                    'can_deposit' => true,
                    'can_withdraw' => false,
                    'deposit_url' => route('admin.accounts.quick-deposit.process', $loan->id) . '?type=loan',
                    'schedule_url' => route('admin.loans.schedule', $loan->id)
                ];
            });

            $results = $accountResults->concat($loanResults);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);
        }

        /**
         * Formater un compte pour la recherche
         * NOUVEAU : Centralise la logique de formatage
         */
        private function formatAccountForSearch($account): array
        {
            $data = [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'account_type' => $account->account_type,
                'balance' => $account->balance,
                'client_id' => $account->client_id,
                'client' => [
                    'id' => $account->client_id,
                    'name' => $account->client->first_name . ' ' . $account->client->last_name,
                    'client_number' => $account->client->client_number,
                    'phone' => $account->client->phone,
                    'kyc_status' => $account->client->kyc_status,
                ],
                'can_deposit' => true,
                'can_withdraw' => $account->balance > 0,
                'deposit_url' => route('admin.accounts.quick-deposit.process', $account->id),
                'withdrawal_url' => route('admin.accounts.quick-withdrawal.process', $account->id),
            ];

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
                    'institutional_fee' => $tontine->tontine_amount, // La règle du 1/31
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

                if ($request->query('type') !== 'loan') {
                    $baseAccount = Account::with([
                        'client',
                        'savingsAccount',
                        'tontineAccount.activeCycle',
                        'tontineAccount.cycles'
                    ])->lockForUpdate()->findOrFail($accountId);

                    // Vérifications de sécurité
                    if ($baseAccount->status !== 'active') {
                        throw new \Exception('Le compte n\'est pas actif.');
                    }

                    // Vérifier les permissions utilisateur
                    if (auth()->user()->role !== 'administrateur_systeme') {
                        if ($baseAccount->client->registered_by !== auth()->id()) {
                            throw new \Exception('Vous n\'avez pas la permission d\'accéder à ce compte.');
                        }
                    }
                }

                // â Vérifier la session de caisse
                $sessionId = $this->getCurrentSessionId();
                if (!$sessionId) {
                    throw new \Exception('Vous devez avoir une session de caisse ouverte pour effectuer un dépôt.');
                }

                $amount = $request->amount;
                $paymentReference = $request->payment_reference ?: $this->generatePaymentReference($request->payment_method);
                
                if ($request->query('type') !== 'loan') {
                    $balanceBefore = $baseAccount->balance;
                    $balanceAfter = $balanceBefore + $amount;
                    $description = $request->description ?? $this->getDefaultDescription($baseAccount);
                } else {
                    $description = $request->description;
                }

                // === TRAITEMENT SELON LE TYPE DE COMPTE ===
                if ($request->query('type') === 'loan') {
                    $loan = Loan::with(['client', 'payments' => function($q) {
                        $q->whereIn('status', ['pending', 'overdue', 'partial'])->orderBy('payment_number');
                    }])->lockForUpdate()->findOrFail($accountId);

                    // Transaction Globale
                    $transaction = Transaction::create([
                        'transaction_reference' => $this->generateTransactionReference(),
                        'loan_id' => $loan->id,
                        'transaction_type' => 'loan_repayment',
                        'amount' => $amount,
                        'payment_method' => $request->payment_method,
                        'payment_reference' => $paymentReference,
                        'description' => "Remboursement prêt {$loan->loan_number} " . ($description ? " - ".$description : ""),
                        'status' => 'completed',
                        'processed_by' => auth()->id(),
                        'agency_id' => auth()->user()->agency_id ?? 1,
                        'cashier_session_id' => $sessionId,
                        'processed_at' => now(),
                        'transaction_date' => now(),
                    ]);

                    // ð¥ RÉPARTITION INTELLIGENTE SUR LES ÉCHÉANCES
                    $remainingToDistribute = $amount;
                    foreach ($loan->payments as $payment) {
                        if ($remainingToDistribute <= 0) break;

                        $penaltyAmount = $payment->penalty_amount;
                        if (now()->gt($payment->due_date) && !in_array($payment->status, ['paid'])) {
                            $daysLate = now()->diffInDays($payment->due_date);
                            $penaltyRate = SystemParameter::where('parameter_key', 'loan_late_penalty_rate')->value('parameter_value') ?? 0.01;
                            $penaltyAmount = ($payment->expected_amount * $penaltyRate) * $daysLate;
                        }

                        $amountNeededForThisPayment = ($payment->expected_amount + $penaltyAmount) - $payment->paid_amount;
                        if ($amountNeededForThisPayment <= 0) continue;

                        $payForThis = min($remainingToDistribute, $amountNeededForThisPayment);
                        $remainingToDistribute -= $payForThis;
                        $newPaidAmount = $payment->paid_amount + $payForThis;
                        
                        $status = ($newPaidAmount >= ($payment->expected_amount + $penaltyAmount)) ? 'paid' : 'partial';

                        $payment->update([
                            'paid_amount' => $newPaidAmount,
                            'penalty_amount' => $penaltyAmount,
                            'payment_method' => $request->payment_method,
                            'payment_reference' => $transaction->payment_reference,
                            'payment_notes' => $description,
                            'paid_date' => now(),
                            'status' => $status,
                            'processed_by' => auth()->id(),
                            'processed_at' => now(),
                        ]);
                    }

                    if ($remainingToDistribute > 0) {
                        \App\Models\LoanPayment::create([
                            'loan_id' => $loan->id,
                            'payment_number' => \App\Models\LoanPayment::where('loan_id', $loan->id)->max('payment_number') + 1,
                            'due_date' => now(),
                            'expected_amount' => 0,
                            'principal_amount' => 0,
                            'interest_amount' => 0,
                            'paid_date' => now(),
                            'paid_amount' => $remainingToDistribute,
                            'payment_method' => $request->payment_method,
                            'payment_reference' => $transaction->payment_reference,
                            'payment_notes' => $description,
                            'processed_by' => auth()->id(),
                            'status' => 'paid',
                            'processed_at' => now(),
                        ]);
                    }

                    // Mettre à jour les totaux du prêt
                    $loan->increment('total_paid', $amount);
                    
                    $reductionPrincipal = min($loan->outstanding_principal, $amount);
                    $reductionInterest = min($loan->outstanding_interest, $amount - $reductionPrincipal);
                    
                    $loan->decrement('outstanding_principal', $reductionPrincipal);
                    $loan->decrement('outstanding_interest', $reductionInterest);

                    if ($loan->total_paid >= $loan->total_amount_due) {
                        $loan->update(['status' => 'completed']);
                    }

                    $message = 'â Remboursement de prêt enregistré avec succès.<br>' .
                              'ð° Montant: ' . number_format($amount, 0, ',', ' ') . ' FCFA<br>' .
                              'ð Reste à payer: ' . number_format(max(0, $loan->total_amount_due - $loan->total_paid), 0, ',', ' ') . ' FCFA';
                    $newBalance = max(0, $loan->total_amount_due - $loan->total_paid);
                    $transRef = $transaction->transaction_reference;
                    $transId = $transaction->id;
                } else {
                    $account = clone $baseAccount;
                    
                    // === TRAITEMENT SELON LE TYPE DE COMPTE ===
                    if ($account->account_type === 'tontine') {
                        $result = $this->processTontineDeposit($account, $amount, $request, $paymentReference, $description, $sessionId);
                        $message = $result['message'];
                        $transRef = $result['transaction_reference'];
                        $transId = $result['transaction_id'];
                    } else {
                        $result = $this->processSavingsDeposit($account, $amount, $request, $paymentReference, $description, $sessionId);
                        $message = $result['message'];
                        $transRef = $result['transaction_reference'];
                        $transId = $result['transaction_id'];
                    }
                    $newBalance = $account->fresh()->balance;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'receipt_url' => route('admin.receipt.print', $transId),
                    'data' => [
                        'new_balance' => $newBalance,
                        'transaction_reference' => $transRef,
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
        private function processSavingsDeposit(Account $account, float $amount, Request $request, string $paymentReference, string $description, string $sessionId): array
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
                'cashier_session_id' => $sessionId,
                'processed_at' => now(),
                'transaction_date' => now(),
            ]);

            $account->update([
                'balance' => $balanceAfter,
                'last_transaction_at' => now(),
            ]);

            return [
                'transaction_reference' => $transaction->transaction_reference,
                'transaction_id' => $transaction->id,
                'message' => 'â Dépôt enregistré avec succès.<br>' .
                            'ð° Montant: ' . number_format($amount, 0, ',', ' ') . ' FCFA<br>' .
                            'ð Nouveau solde: ' . number_format($balanceAfter, 0, ',', ' ') . ' FCFA'
            ];
        }

        /**
         * NOUVEAU : Traiter un dépôt tontine avec logique multi-cycles
         */
        private function processTontineDeposit(Account $account, float $amount, Request $request, string $paymentReference, string $description, string $sessionId): array
        {
            $tontine = $account->tontineAccount;
            $balanceBefore = $account->balance;

            // ð VÉRIFICATION : Ne pas dépasser le total attendu
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

            // ð¥ RÉPARTITION MULTI-CYCLES
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
                'cashier_session_id' => $sessionId,
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
                'transaction_id' => $transaction->id,
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
     * Page de retrait rapide (recherche + retrait sur la même page)
     */
    public function retraitform()
    {
        $savingsFeePercentage = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_percentage')->value('parameter_value') ?? 2.0);
        $savingsFeeFixed = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_fixed')->value('parameter_value') ?? 0);
        
        $tontineFeePercentage = (float) (SystemParameter::where('parameter_key', 'tontine_withdrawal_fee_percentage')->value('parameter_value') ?? 3.0);
        $tontineFeeFixed = (float) (SystemParameter::where('parameter_key', 'tontine_withdrawal_fee_fixed')->value('parameter_value') ?? 0);
        
        return view('admin.accounts.retrait', compact(
            'savingsFeePercentage', 
            'savingsFeeFixed', 
            'tontineFeePercentage', 
            'tontineFeeFixed'
        ));
    }    

        /**
         * Recherche AJAX de comptes pour retrait rapide
         */
        public function quickWithdrawalSearch(Request $request)
        {
            $request->validate([
                'query' => 'required|string|min:2',
            ]);

            $user = auth()->user();
            $query = $request->get('query');

            $accounts = Account::with([
                'client',
                'savingsAccount',
                'tontineAccount.activeCycle',
            ])
            ->where('status', 'active')
            ->where('balance', '>', 0) // Uniquement ceux qui ont du solde
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
         * Traiter le retrait rapide via AJAX
         */
        public function processQuickWithdrawal(Request $request, $accountId)
        {
            $request->validate([
                'amount' => 'required|numeric|min:100',
                'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
                'mobile_money_operator' => 'nullable|in:tmoney,flooz',
                'payment_reference' => 'nullable|string|max:100',
                'description' => 'nullable|string|max:500',
            ]);

            try {
                DB::beginTransaction();

                $account = Account::with(['client', 'savingsAccount', 'tontineAccount'])
                    ->lockForUpdate()
                    ->findOrFail($accountId);

                if ($account->status !== 'active') {
                    throw new \Exception('Le compte n\'est pas actif.');
                }

                // // â Vérifier la session de caisse
                $sessionId = $this->getCurrentSessionId();
                // if (!$sessionId) {
                //     throw new \Exception('Vous devez avoir une session de caisse ouverte pour effectuer un retrait.');
                // }

                $amount = (float) $request->amount;

                // ð¹ Calcul des frais de retrait selon le type de compte (Règle Proportionnelle 1/31)
                if ($account->account_type === 'tontine') {
                    $tontine = $account->tontineAccount;
                    $mise = (float) $tontine->tontine_amount;
                    
                    if ($tontine->payment_frequency === 'daily') {
                        // Pour le quotidien : 1 jour par tranche de 31 jours (mois de tontine)
                        $nbDaysTotal = $amount / $mise;
                        $nbCommissions = ceil($nbDaysTotal / 31);
                        $feeAmount = $nbCommissions * $mise;
                    } elseif ($tontine->payment_frequency === 'weekly') {
                        // Pour l'hebdo (annuel) : 1 semaine par an/cycle
                        // Si le montant dépasse la mise annuelle d'un cycle (52 semaines)
                        $nbWeeksTotal = $amount / $mise;
                        $nbCommissions = ceil($nbWeeksTotal / 52);
                        $feeAmount = $nbCommissions * $mise;
                    } else {
                        // Par défaut ou mensuel
                        $feeAmount = $mise;
                    }
                    
                    $feePercentage = 0;
                    $feeFixed = $feeAmount;
                } else {
                    $feePercentage = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_percentage')->value('parameter_value') ?? 2.0);
                    $feeFixed = (float) (SystemParameter::where('parameter_key', 'savings_withdrawal_fee_fixed')->value('parameter_value') ?? 0);
                    $feeAmount = round(($amount * ($feePercentage / 100)) + $feeFixed);
                }
                
                $totalDeduction = $amount + $feeAmount;

                if ($account->balance < $totalDeduction) {
                    throw new \Exception('Solde insuffisant pour cette opération (incluant les frais de ' . number_format($feeAmount, 0, ',', ' ') . ' FCFA). Solde requis: ' . number_format($totalDeduction, 0, ',', ' ') . ' FCFA');
                }

                $balanceBefore = $account->balance;
                $balanceAfter = $balanceBefore - $totalDeduction;

                $paymentReference = $request->payment_reference ?: $this->generatePaymentReference($request->payment_method);
                $description = $request->description ?: 'Retrait rapide au guichet';

                $transaction = Transaction::create([
                    'transaction_reference' => $this->generateTransactionReference(),
                    'account_id' => $account->id,
                    'transaction_type' => 'withdrawal',
                    'amount' => $amount,
                    'fee_amount' => $feeAmount,
                    'withdrawal_fee' => $feeAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'payment_method' => $request->payment_method,
                    'mobile_money_operator' => $request->mobile_money_operator,
                    'payment_reference' => $paymentReference,
                    'description' => $description,
                    'status' => 'completed',
                    'processed_by' => auth()->id(),
                    'cashier_session_id' => $this->getCurrentSessionId(),
                    'processed_at' => now(),
                    'transaction_date' => now(),
                ]);

                $account->update([
                    'balance' => $balanceAfter,
                    'last_transaction_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'ð¸ Retrait de ' . number_format($amount, 0, ',', ' ') . ' FCFA exécuté avec succès.<br>' .
                                 'ð§¾ Commission Institutionnelle : ' . number_format($feeAmount, 0, ',', ' ') . ' FCFA' . ($account->account_type === 'tontine' ? ' (Règle 1/31)' : " ({$feePercentage}%)") . '<br>' .
                                 'ð Nouveau solde : ' . number_format($balanceAfter, 0, ',', ' ') . ' FCFA',
                    'receipt_url' => route('admin.receipt.print', $transaction->id),
                    'data' => [
                        'transaction_reference' => $transaction->transaction_reference,
                        'new_balance' => $balanceAfter,
                        'fee_amount' => $feeAmount
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du retrait: ' . $e->getMessage()
                ], 500);
            }
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

        /**
         * Impression d'un reçu de transaction pour l'admin
         */
        public function printReceipt(Transaction $transaction)
        {
            $transaction->load(['account.client', 'processedBy', 'agency']);
            
            $clientName = 'N/A';
            
            if ($transaction->account) {
                $clientName = $transaction->account->client->full_name;
            } elseif (str_starts_with($transaction->payment_reference, 'LOAN-')) {
                // Rechercher le client via le prêt
                $loanNumber = str_replace('LOAN-', '', $transaction->payment_reference);
                $loan = Loan::where('loan_number', $loanNumber)->with('client')->first();
                if ($loan) {
                    $clientName = $loan->client->full_name;
                }
            }
            
            return view('agent.cashier.receipt', compact('transaction', 'clientName'));
        }
    }


