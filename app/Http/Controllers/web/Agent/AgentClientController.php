<?php
namespace App\Http\Controllers\Web\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentClientController extends Controller
{
    /**
     * Liste des clients de l'agent connecté
     */
    public function index(Request $request)
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

        if (request()->routeIs('caissier.*')) {
            return view('agent.cashier.clients.index', compact('clients', 'stats'));
        }

        return view('agent.clients.index', compact('clients', 'stats'));
    }

    /**
     * Formulaire de création d'un client
     */
    public function create()
    {
        return view('agent.clients.create');
    }

    /**
     * Créer un nouveau client (inscription terrain)
     */
    public function store(CreateClientRequest $request)
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
            $clientData['is_active'] = '1';

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

            DB::commit();

            return redirect()
                ->route('agent.clients.show', $client->id)
                ->with('success', 'Client créé avec succès. Numéro client : ' . $client->client_number);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de l\'inscription du client: ' . $e->getMessage());
        }
    }

    /**
     * Afficher un client spécifique
     */
    public function show($clientId)
    {
        $user = auth()->user();

        // Charger le client avec ses relations
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

        if (request()->routeIs('caissier.*')) {
            return view('agent.cashier.clients.show', compact('client', 'summary', 'recentTransactions'));
        }

        return view('agent.clients.show', compact('client', 'summary', 'recentTransactions'));
    }

    /**
     * Formulaire d'édition d'un client
     */
    public function edit($clientId)
    {
        $user = auth()->user();

        $client = Client::where('registered_by', $user->id)
            ->findOrFail($clientId);

        // Empêcher la modification si le KYC est déjà approuvé
        if ($client->kyc_status === 'approved') {
            return redirect()
                ->route('agent.clients.show', $clientId)
                ->with('warning', 'Impossible de modifier un client dont le KYC est déjà approuvé.');
        }

        if (request()->routeIs('caissier.*')) {
            return view('agent.cashier.clients.edit', compact('client'));
        }

        return view('agent.clients.edit', compact('client'));
    }

    /**
     * Mettre à jour un client
     */
    public function update(UpdateClientRequest $request, $clientId)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Récupérer le client
            $client = Client::where('registered_by', $user->id)
                ->findOrFail($clientId);

            // Empêcher la modification si le KYC est déjà approuvé
            if ($client->kyc_status === 'approved') {
                return redirect()
                    ->route('agent.clients.show', $clientId)
                    ->with('error', 'Impossible de modifier un client dont le KYC est déjà approuvé.');
            }

            $updateData = $request->validated();

            // Gestion de la photo de profil
            if ($request->hasFile('profile_photo')) {
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

            return redirect()
                ->route('agent.clients.show', $clientId)
                ->with('success', 'Client mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Page d'activation des comptes
     */
    public function activationForm($clientId)
    {
        $user = auth()->user();

        $client = Client::where('registered_by', $user->id)
            ->with(['accounts' => function($q) {
                $q->where('status', 'suspended');
            }])
            ->findOrFail($clientId);

        if ($client->accounts->isEmpty()) {
            return redirect()
                ->route('agent.clients.show', $clientId)
                ->with('info', 'Aucun compte en attente d\'activation.');
        }

        return view('agent.clients.activate', compact('client'));
    }

    /**
     * Activer les comptes d'un client après paiement
     */
    public function activateAccounts(Request $request, $clientId)
    {
        $request->validate([
            'account_selected' => 'required|array|min:1',
            'accounts' => 'required|array',
            'accounts.*.account_id' => 'required|exists:accounts,id',
            'accounts.*.payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'accounts.*.payment_reference' => 'nullable|string',
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

            foreach ($request->accounts as $index => $accountData) {
                // Vérifier si le compte est sélectionné
                if (!isset($request->account_selected[$index])) {
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
                Transaction::create([
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
                if (!empty($accountData['initial_deposit']) && $accountData['initial_deposit'] > 0) {
                    $newBalance = $account->balance + $accountData['initial_deposit'];

                    Transaction::create([
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
            }

            DB::commit();

            return redirect()
                ->route('agent.clients.show', $clientId)
                ->with('success', "{$activatedAccounts} compte(s) activé(s) avec succès. Total encaissé: " . number_format($totalAmount, 0, ',', ' ') . " FCFA");

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de l\'activation des comptes: ' . $e->getMessage());
        }
    }

    /**
     * Recherche AJAX de clients de l'agent
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'in:name,phone,client_number'
        ]);

        $user = auth()->user();
        $query = $request->get('query');
        $type = $request->get('type', 'name');

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
            ->get()
            ->map(function($client) {
                return [
                    'id' => $client->id,
                    'client_number' => $client->client_number,
                    'full_name' => $client->first_name . ' ' . $client->last_name,
                    'phone' => $client->phone,
                    'kyc_status' => $client->kyc_status,
                    'accounts' => $client->accounts->map(function($account) {
                        return [
                            'id' => $account->id,
                            'account_number' => $account->account_number,
                            'account_type' => $account->account_type,
                            'balance' => $account->balance,
                        ];
                    })
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    /**
     * Statistiques des clients de l'agent
     */
    public function stats()
    {
        $user = auth()->user();

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

        return view('agent.clients.stats', compact('stats'));
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
