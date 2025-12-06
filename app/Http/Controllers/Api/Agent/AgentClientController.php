<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Account;
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

        $query = Client::query()
            ->with(['accounts', 'agency'])
            ->where('registered_by', $user->id);

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
            'total' => Client::where('registered_by', $user->id)->count(),
            'pending_kyc' => Client::where('registered_by', $user->id)->where('kyc_status', 'pending')->count(),
            'approved' => Client::where('registered_by', $user->id)->where('kyc_status', 'approved')->count(),
            'today' => Client::where('registered_by', $user->id)->whereDate('created_at', today())->count(),
        ];

        return response()->json([
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

            // Informations d'enregistrement
            $clientData['registered_by'] = $user->id;
            $clientData['agency_id'] = $user->agency_id;
            $clientData['registration_channel'] = 'agent_assisted';
            $clientData['registration_status'] = 'pending';
            $clientData['kyc_status'] = 'pending';

            // Hash du mot de passe
            if (isset($clientData['password'])) {
                $clientData['password'] = Hash::make($clientData['password']);
            }

            // Upload de la photo de profil
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('clients/photos', 'public');
                $clientData['profile_photo_url'] = $path;
            }

            $client = Client::create($clientData);

            DB::commit();

            return response()->json([
                'message' => "Client créé avec succès. Numéro client : {$client->client_number}",
                'data' => $client->load(['accounts', 'agency'])
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

            $client = Client::with([
                'accounts.transactions' => function($q) {
                    $q->latest()->limit(5);
                },
                'loans',
                'documents',
                'agency',
                'registeredBy',
                'approvedBy'
            ])
            ->where('registered_by', $user->id)
            ->findOrFail($clientId);

            // Calcul du résumé financier
            $totalSavings = $client->accounts->where('account_type', 'savings')->sum('balance');
            $totalTontine = $client->accounts->where('account_type', 'tontine')->sum('balance');
            $activeLoansAmount = $client->loans->whereIn('status', ['active', 'disbursed'])->sum('approved_amount');
            $totalAccounts = $client->accounts->count();
            $activeAccounts = $client->accounts->where('status', 'active')->count();
            $suspendedAccounts = $client->accounts->where('status', 'suspended')->count();

            $summary = [
                'total_savings' => $totalSavings,
                'total_tontine' => $totalTontine,
                'total_balance' => $totalSavings + $totalTontine,
                'active_loans_amount' => $activeLoansAmount,
                'total_accounts' => $totalAccounts,
                'active_accounts' => $activeAccounts,
                'suspended_accounts' => $suspendedAccounts,
            ];

            // Transactions récentes
            $recentTransactions = Transaction::whereHas('account', function($q) use ($clientId) {
                    $q->where('client_id', $clientId);
                })
                ->latest()
                ->limit(10)
                ->get();

            return response()->json([
                'data' => [
                    'client' => $client,
                    'summary' => $summary,
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

            $client = Client::where('registered_by', $user->id)
                ->findOrFail($clientId);

            // Empêcher la modification si le KYC est déjà approuvé
            if ($client->kyc_status === 'approved') {
                return response()->json([
                    'message' => 'Impossible de modifier un client dont le KYC est déjà approuvé'
                ], 403);
            }

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

            // Statistiques des clients
            $stats = [
                'total_clients' => Client::where('registered_by', $user->id)->count(),
                'kyc_approved' => Client::where('registered_by', $user->id)
                    ->where('kyc_status', 'approved')->count(),
                'kyc_pending' => Client::where('registered_by', $user->id)
                    ->where('kyc_status', 'pending')->count(),
                'kyc_rejected' => Client::where('registered_by', $user->id)
                    ->where('kyc_status', 'rejected')->count(),
                'new_today' => Client::where('registered_by', $user->id)
                    ->whereDate('created_at', today())->count(),
                'new_this_week' => Client::where('registered_by', $user->id)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'new_this_month' => Client::where('registered_by', $user->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)->count(),
            ];

            // Statistiques des comptes des clients de l'agent
            $clientIds = Client::where('registered_by', $user->id)->pluck('id');

            $stats['total_accounts'] = Account::whereIn('client_id', $clientIds)->count();
            $stats['active_accounts'] = Account::whereIn('client_id', $clientIds)
                ->where('status', 'active')->count();
            $stats['suspended_accounts'] = Account::whereIn('client_id', $clientIds)
                ->where('status', 'suspended')->count();

            $stats['total_savings'] = Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'savings')->sum('balance');
            $stats['total_tontine'] = Account::whereIn('client_id', $clientIds)
                ->where('account_type', 'tontine')->sum('balance');

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
