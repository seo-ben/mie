<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Account;
use App\Models\SavingsAccount;
use App\Models\TontineAccount;
use App\Models\Transaction;
use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentClientController extends Controller
{
    /**
     * Liste des clients de l'agent connecté
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = $this->clientsVisibleTo($user)
            ->with(['accounts.tontineAccount', 'agency', 'loans.payments']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('client_number', 'like', "%{$search}%");
            });
        }

        // Filtre par status KYC
        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        // Filtre par statut d'enregistrement
        if ($request->filled('registration_status')) {
            $query->where('registration_status', $request->registration_status);
        }

        $clients = $query->latest()->paginate(20);

        // Statistiques rapides
        $stats = [
            'total' => (clone $this->clientsVisibleTo($user))->count(),
            'today' => (clone $this->clientsVisibleTo($user))->whereDate('created_at', today())->count(),
            'this_week' => (clone $this->clientsVisibleTo($user))->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'stats' => $stats
            ]
        ]);
    }

    private function clientsVisibleTo($user)
    {
        return Client::query()->where(function ($query) use ($user) {
            if ($user->role === 'caissier') {
                $query->where('agency_id', $user->agency_id);
            } else {
                $query->where('registered_by', $user->id);
            }
        });
    }

    /**
     * Créer un nouveau client (inscription terrain)
     */
    public function store(CreateClientRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $clientData = $request->validated();

            // Génération du numéro client
            $clientData['client_number'] = $this->generateClientNumber();

            // Informations d'enregistrement & Auto-approbation KYC au guichet caisse
            $clientData['registered_by'] = $user->id;
            $clientData['agency_id'] = $user->agency_id;
            $clientData['registration_channel'] = 'agent_assisted';
            $clientData['registration_status'] = 'approved';
            $clientData['kyc_status'] = 'approved';
            $clientData['kyc_approved_at'] = now();
            $clientData['kyc_approved_by'] = $user->id;
            $clientData['is_active'] = 1;

            // Hash du mot de passe (par défaut 12@4 si non fourni)
            if (!empty($clientData['password'])) {
                $clientData['password'] = Hash::make($clientData['password']);
            } else {
                $clientData['password'] = Hash::make('12@4');
            }

            // Upload de la photo de profil
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('clients/photos', 'public');
                $clientData['profile_photo_url'] = $path;
            }

            $client = Client::create($clientData);

            if ($request->boolean('create_tontine')) {
                $tontineAmount = (float) $request->input('initial_tontine_amount', 1000);
                $tontineAccount = Account::create([
                    'account_number' => $this->generateAccountNumber('tontine'),
                    'client_id' => $client->id,
                    'account_type' => 'tontine',
                    'status' => 'active',
                    'balance' => 0,
                    'activation_fee_paid' => true,
                    'activated_at' => now(),
                    'activated_by' => $user->id,
                    'created_by' => $user->id,
                ]);

                TontineAccount::create([
                    'account_id' => $tontineAccount->id,
                    'tontine_amount' => $tontineAmount,
                    'cycle_duration_months' => 12,
                    'payment_frequency' => 'daily',
                    'expected_monthly_payment' => $tontineAmount,
                    'total_expected' => $tontineAmount * 12 * 31,
                    'cycle_start_date' => now()->startOfMonth(),
                    'cycle_end_date' => now()->addMonths(12)->endOfMonth(),
                ]);
            }

            $savingsAccount = Account::create([
                'account_number' => $this->generateAccountNumber('savings'),
                'client_id' => $client->id,
                'account_type' => 'savings',
                'status' => 'active',
                'balance' => 0,
                'activation_fee_paid' => true,
                'activated_at' => now(),
                'activated_by' => $user->id,
                'created_by' => $user->id,
            ]);

            SavingsAccount::create([
                'account_id' => $savingsAccount->id,
                'interest_rate' => 0,
                'minimum_balance' => 0,
                'monthly_fee' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Client créé avec succès. Numéro client : {$client->client_number}",
                'data' => $client->load(['accounts', 'agency', 'loans.payments'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur création client', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'inscription du client',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Afficher un client spécifique
     */
    public function show(int $clientId): JsonResponse
    {
        try {
            $user = auth()->user();

            $client = $this->clientsVisibleTo($user)
                ->with([
                    'accounts.tontineAccount',
                    'accounts.transactions' => function($q) {
                        $q->latest()->limit(5);
                    },
                    'loans.payments',
                    'documents',
                    'agency',
                    'registeredBy',
                    'approvedBy'
                ])
                ->findOrFail($clientId);

            // Calcul du résumé financier
            $totalSavings = (float) $client->accounts->where('account_type', 'savings')->sum('balance');
            $totalTontine = (float) $client->accounts->where('account_type', 'tontine')->sum('balance');
            $activeLoans = $client->loans->whereIn('status', ['active', 'disbursed']);
            $activeLoansAmount = (float) $activeLoans->sum('approved_amount');
            $totalBorrowed = (float) $client->loans->whereIn('status', ['disbursed', 'active', 'completed'])->sum('approved_amount');
            $totalLoanRepaid = (float) $client->loans->whereIn('status', ['disbursed', 'active', 'completed'])->sum('total_paid');
            $totalLoanRemaining = max(0, $totalBorrowed - $totalLoanRepaid);

            $summary = [
                'total_savings' => $totalSavings,
                'total_tontine' => $totalTontine,
                'total_balance' => $totalSavings + $totalTontine,
                'active_loans_amount' => $activeLoansAmount,
                'total_borrowed' => $totalBorrowed,
                'total_loan_repaid' => $totalLoanRepaid,
                'total_loan_remaining' => $totalLoanRemaining,
                'total_loans_count' => $client->loans->count(),
                'active_loans_count' => $activeLoans->count(),
                'total_accounts' => $client->accounts->count(),
                'active_accounts' => $client->accounts->where('status', 'active')->count(),
            ];

            // Transactions récentes
            $recentTransactions = Transaction::whereHas('account', function($q) use ($clientId) {
                    $q->where('client_id', $clientId);
                })
                ->latest()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'client' => $client,
                    'summary' => $summary,
                    'loans' => $client->loans,
                    'recent_transactions' => $recentTransactions
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Client non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur récupération client', [
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération du client'
            ], 500);
        }
    }

    /**
     * Mettre à jour un client
     */
    public function update(UpdateClientRequest $request, int $clientId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            $client = $this->clientsVisibleTo($user)
                ->findOrFail($clientId);

            $updateData = $request->validated();

            // Gestion de la photo de profil
            if ($request->hasFile('profile_photo')) {
                // Supprimer l'ancienne photo
                if ($client->profile_photo_url) {
                    Storage::disk('public')->delete($client->profile_photo_url);
                }

                $updateData['profile_photo_url'] = $request->file('profile_photo')->store('clients/photos', 'public');
            }

            // Gestion du mot de passe uniquement si fourni
            if (!empty($updateData['password'] ?? null)) {
                $updateData['password'] = Hash::make($updateData['password']);
            } else {
                unset($updateData['password']);
            }

            // Mise à jour des données
            $client->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Client mis à jour avec succès',
                'data' => $client->fresh()->load(['accounts', 'agency'])
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Client non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur mise à jour client', [
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du client',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Liste des comptes en attente d'activation
     */
    public function pendingAccounts(int $clientId): JsonResponse
    {
        try {
            $user = auth()->user();

            $client = Client::where('registered_by', $user->id)
                ->with(['accounts' => function($q) {
                    $q->where('status', 'suspended');
                }])
                ->findOrFail($clientId);

            if ($client->accounts->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun compte en attente d\'activation',
                    'data' => [
                        'client' => $client,
                        'pending_accounts' => []
                    ]
                ]);
            }

            return response()->json([
                'data' => [
                    'client' => $client,
                    'pending_accounts' => $client->accounts
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Client non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur récupération comptes en attente', [
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des comptes'
            ], 500);
        }
    }

    /**
     * Activer les comptes d'un client après paiement
     */
    public function activateAccounts(Request $request, int $clientId): JsonResponse
    {
        $validated = $request->validate([
            'account_selected' => 'required|array|min:1',
            'accounts' => 'required|array',
            'accounts.*.account_id' => 'required|exists:accounts,id',
            'accounts.*.payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'accounts.*.payment_reference' => 'nullable|string|max:100',
            'accounts.*.amount_paid' => 'required|numeric|min:0',
            'accounts.*.initial_deposit' => 'nullable|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            $client = Client::where('registered_by', $user->id)
                ->findOrFail($clientId);

            $activatedAccounts = 0;
            $totalAmount = 0;
            $activatedAccountsDetails = [];

            foreach ($validated['accounts'] as $index => $accountData) {
                // Vérifier si le compte est sélectionné
                if (!isset($validated['account_selected'][$index])) {
                    continue;
                }

                $account = Account::where('id', $accountData['account_id'])
                    ->where('client_id', $clientId)
                    ->where('status', 'suspended')
                    ->first();

                if (!$account) {
                    continue;
                }

                // Activer le compte
                $account->update([
                    'status' => 'active',
                    'activated_at' => now(),
                    'activated_by' => $user->id
                ]);

                // Enregistrer la transaction de frais d'ouverture
                $feeTransaction = Transaction::create([
                    'account_id' => $account->id,
                    'transaction_number' => $this->generateTransactionNumber(),
                    'type' => 'fee',
                    'amount' => $accountData['amount_paid'],
                    'fee_amount' => $accountData['amount_paid'],
                    'description' => 'Frais d\'ouverture de compte',
                    'payment_method' => $accountData['payment_method'],
                    'reference' => $accountData['payment_reference'] ?? null,
                    'status' => 'completed',
                    'processed_by' => $user->id,
                    'processed_at' => now()
                ]);

                // Enregistrer le dépôt initial si fourni
                $depositTransaction = null;
                if (!empty($accountData['initial_deposit']) && $accountData['initial_deposit'] > 0) {
                    $newBalance = $account->balance + $accountData['initial_deposit'];

                    $depositTransaction = Transaction::create([
                        'account_id' => $account->id,
                        'transaction_number' => $this->generateTransactionNumber(),
                        'type' => 'deposit',
                        'amount' => $accountData['initial_deposit'],
                        'balance_after' => $newBalance,
                        'description' => 'Dépôt initial',
                        'payment_method' => $accountData['payment_method'],
                        'reference' => $accountData['payment_reference'] ?? null,
                        'status' => 'completed',
                        'processed_by' => $user->id,
                        'processed_at' => now()
                    ]);

                    // Mettre à jour le solde du compte
                    $account->increment('balance', $accountData['initial_deposit']);
                }

                $activatedAccounts++;
                $totalAmount += $accountData['amount_paid'] + ($accountData['initial_deposit'] ?? 0);

                $activatedAccountsDetails[] = [
                    'account' => $account->fresh(),
                    'fee_transaction' => $feeTransaction,
                    'deposit_transaction' => $depositTransaction
                ];
            }

            DB::commit();

            return response()->json([
                'message' => "{$activatedAccounts} compte(s) activé(s) avec succès",
                'data' => [
                    'activated_accounts_count' => $activatedAccounts,
                    'total_amount' => $totalAmount,
                    'accounts' => $activatedAccountsDetails
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Client non trouvé ou accès non autorisé'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur activation comptes', [
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'activation des comptes',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Recherche AJAX de clients de l'agent
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:name,phone,client_number'
        ]);

        try {
            $user = auth()->user();
            $query = $validated['query'];
            $type = $validated['type'] ?? 'name';

            $clients = Client::where('registered_by', $user->id)
                ->where(function($q) use ($query, $type) {
                    switch ($type) {
                        case 'phone':
                            $q->where('phone', 'like', "%{$query}%");
                            break;
                        case 'client_number':
                            $q->where('client_number', 'like', "%{$query}%");
                            break;
                        default:
                            $q->where('first_name', 'like', "%{$query}%")
                              ->orWhere('last_name', 'like', "%{$query}%");
                    }
                })
                ->with(['accounts' => function($q) {
                    $q->where('status', 'active');
                }])
                ->limit(20)
                ->get();

            return response()->json([
                'data' => $clients
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur recherche clients', [
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
     * Statistiques des clients de l'agent
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Statistiques des clients visibles (toute l'agence pour le caissier)
            $clientQuery = $this->clientsVisibleTo($user);
            $clientIds = (clone $clientQuery)->pluck('id');

            $stats = [
                'total_clients' => (clone $clientQuery)->count(),
                'new_today' => (clone $clientQuery)
                    ->whereDate('created_at', today())->count(),
                'new_this_week' => (clone $clientQuery)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'total_accounts' => Account::whereIn('client_id', $clientIds)->count(),
                'active_accounts' => Account::whereIn('client_id', $clientIds)
                    ->where('status', 'active')->count(),
                'total_savings' => Account::whereIn('client_id', $clientIds)
                    ->where('account_type', 'savings')->sum('balance'),
                'total_tontine' => Account::whereIn('client_id', $clientIds)
                    ->where('account_type', 'tontine')->sum('balance'),
            ];

            $stats['accounts_activated_today'] = Account::whereIn('client_id', $clientIds)
                ->whereDate('activated_at', today())->count();

            return response()->json([
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récupération statistiques', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    public function approveKyc(int $clientId): JsonResponse
    {
        $client = $this->clientsVisibleTo(auth()->user())->findOrFail($clientId);

        $client->update([
            'kyc_status' => 'approved',
        ]);

        return response()->json([
            'message' => 'KYC validé. Le client est éligible à l’étude de prêt.',
            'data' => $client->fresh(),
        ]);
    }

    /**
     * Générer un numéro de client unique
     */
    private function generateClientNumber(): string
    {
        do {
            $number = 'CLT-' . strtoupper(Str::random(3)) . '-' . date('ym') . rand(100, 999);
        } while (Client::where('client_number', $number)->exists());

        return $number;
    }

    private function generateAccountNumber(string $type): string
    {
        $prefix = $type === 'tontine' ? 'ACC' : 'SAV';
        do {
            $number = $prefix . '-' . date('ym') . '-' . strtoupper(Str::random(6));
        } while (Account::where('account_number', $number)->exists());

        return $number;
    }

    /**
     * Générer un numéro de transaction unique
     */
    private function generateTransactionNumber(): string
    {
        do {
            $number = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
        } while (Transaction::where('transaction_number', $number)->exists());

        return $number;
    }
}
