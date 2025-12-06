<?php
namespace App\Http\Controllers\web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Account;
use App\Models\Agency;
use App\Models\Transaction;
use App\Models\User;
use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminClientController extends Controller
{
    /**
     * Liste des clients de l'agent
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Client::query()
            ->with(['accounts', 'agency', 'registeredBy']);

        // Filtrage selon le rôle
        if ($user->role !== 'administrateur_systeme') {
            $query->where('registered_by', $user->id);
        }

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

        // Filtre par agence (pour admin système)
        if ($request->filled('agency_id') && $user->role === 'administrateur_systeme') {
            $query->where('agency_id', $request->agency_id);
        }

        $clients = $query->latest()->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Formulaire de création d'un client
     */
    public function create()
    {
        $agencies = auth()->user()->role === 'administrateur_systeme'
            ? Agency::all()
            : collect([auth()->user()->agency]);

        return view('admin.clients.create', compact('agencies'));
    }

    /**
     * Créer un nouveau client (inscription terrain)
     */
    public function store(CreateClientRequest $request)
    {
        try {
            DB::beginTransaction();

            $clientData = $request->validated();

            // Génération du numéro client
            $clientData['client_number'] = $this->generateClientNumber();

            // Informations d'enregistrement
            $clientData['registered_by'] = auth()->id();
            $clientData['agency_id'] = $request->agency_id ?? auth()->user()->agency_id;
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

            return redirect()
                ->route('admin.clients.show', $client->id)
                ->with('success', 'Client créé avec succès. Vous pouvez maintenant créer ses comptes.');

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

        // Charger le client avec ses relations et l'utilisateur qui a approuvé le KYC
        $client = Client::with([
            'accounts',
            'loans',
            'documents',
            'agency',
            'registeredBy',
            'approvedBy' // relation vers l'utilisateur qui a approuvé le KYC
        ])->findOrFail($clientId);

        // Calcul du résumé financier
        $totalSavings = $client->accounts->where('account_type', 'savings')->sum('balance');
        $totalTontine = $client->accounts->where('account_type', 'tontine')->sum('balance');
        $activeLoansAmount = $client->loans->whereIn('status', ['active', 'disbursed'])->sum('approved_amount');
        $totalAccounts = $client->accounts->count();
        $activeAccounts = $client->accounts->where('status', 'active')->count();

        $summary = [
            'total_savings' => $totalSavings,
            'total_tontine' => $totalTontine,
            'active_loans_amount' => $activeLoansAmount,
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
        ];

        // dd($summary);

        return view('admin.clients.show', compact('client', 'summary'));
    }


    /**
     * Formulaire d'édition d'un client
     */
    public function edit($clientId)
    {
        $user = auth()->user();

        $client = Client::when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                return $q->where('registered_by', $user->id);
            })
            ->findOrFail($clientId);

        $agencies = auth()->user()->role === 'administrateur_systeme'
            ? Agency::all()
            : collect([auth()->user()->agency]);

        return view('admin.clients.edit', compact('client', 'agencies'));
    }

    /**
     * Mettre à jour un client
     */

    public function update(UpdateClientRequest $request, $clientId)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Récupérer le client selon le rôle de l'utilisateur
            $client = Client::when($user->role !== 'administrateur_systeme', function($query) use ($user) {
                    return $query->where('registered_by', $user->id);
                })
                ->findOrFail($clientId);

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
                ->route('admin.clients.show', $clientId)
                ->with('success', 'Client mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }


    // --- DÉSACTIVER LES COMPTES DU CLIENT ---
    public function deactivateAccounts(Client $client)
    {
        foreach ($client->accounts as $account) {
            $account->update(['status' => 'inactive']);
        }

        return redirect()
            ->route('admin.clients.show', $client->id)
            ->with('success', 'Les comptes du client ont été désactivés avec succès.');
    }

    /**
     * Page d'activation des comptes
     */
    public function activationForm($clientId)
    {
        $user = auth()->user();

        $client = Client::when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                return $q->where('registered_by', $user->id);
            })
            ->with(['accounts' => function($q) {
                $q->where('status', 'suspended');
            }])
            ->findOrFail($clientId);

        if ($client->accounts->isEmpty()) {
            return redirect()
                ->route('admin.clients.show', $clientId)
                ->with('info', 'Aucun compte en attente d\'activation.');
        }

        return view('admin.clients.activate', compact('client'));
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

            $client = Client::when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                    return $q->where('registered_by', $user->id);
                })
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
                    Transaction::create([
                        'account_id' => $account->id,
                        'transaction_number' => $this->generateTransactionNumber(),
                        'type' => 'deposit',
                        'amount' => $accountData['initial_deposit'],
                        'balance_after' => $account->balance + $accountData['initial_deposit'],
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
                ->route('admin.clients.show', $clientId)
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
     * Statistiques des clients de l'agent
     */
    public function stats()
    {
        $user = auth()->user();

        $query = Client::query();

        if ($user->role !== 'administrateur_systeme') {
            $query->where('registered_by', $user->id);
        }

        $stats = [
            'total_clients' => (clone $query)->count(),
            'active_clients' => (clone $query)->where('status', 'active')->count(),
            'kyc_approved' => (clone $query)->where('kyc_status', 'approved')->count(),
            'kyc_pending' => (clone $query)->where('kyc_status', 'pending')->count(),
            'kyc_rejected' => (clone $query)->where('kyc_status', 'rejected')->count(),
            'new_clients_this_month' => (clone $query)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
            'kyc_approved_this_month' => (clone $query)->where('kyc_status', 'approved')
                ->whereMonth('kyc_approved_at', now()->month)
                ->whereYear('kyc_approved_at', now()->year)->count(),
        ];

        // Statistiques des comptes
        $clientIds = $query->pluck('id');

        $stats['total_savings'] = Account::whereIn('client_id', $clientIds)
            ->where('account_type', 'savings')->sum('balance');

        $stats['savings_accounts'] = Account::whereIn('client_id', $clientIds)
            ->where('account_type', 'savings')->count();

        $stats['savings_total'] = Account::whereIn('client_id', $clientIds)
            ->where('account_type', 'savings')->sum('balance');

        $stats['tontine_accounts'] = Account::whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine')->count();

        $stats['tontine_total'] = Account::whereIn('client_id', $clientIds)
            ->where('account_type', 'tontine')->sum('balance');

        $stats['accounts_activated_this_month'] = Account::whereIn('client_id', $clientIds)
            ->whereMonth('activated_at', now()->month)
            ->whereYear('activated_at', now()->year)->count();

        // Statistiques des prêts
        $stats['loan_accounts'] = DB::table('loans')
            ->whereIn('client_id', $clientIds)
            ->whereIn('status', ['active', 'disbursed'])->count();

        $stats['loans_total'] = DB::table('loans')
            ->whereIn('client_id', $clientIds)
            ->whereIn('status', ['active', 'disbursed'])->sum('approved_amount');

        return view('admin.clients.stats', compact('stats'));
    }

    /**
     * Recherche AJAX de clients
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

        $clients = Client::when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                return $q->where('registered_by', $user->id);
            })
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
            ->with(['accounts'])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clients
        ]);
    }

    /**
     * Validation KYC
     */
    public function validateKyc($clientId)
    {
        $client = Client::with(['documents'])->findOrFail($clientId);

        return view('admin.clients.validate-kyc', compact('client'));
    }

    /**
     * Approuver KYC
     */
    public function approveKyc(Request $request, $clientId)
    {
        try {
            $client = Client::findOrFail($clientId);

            // $client->update([
            //     'kyc_status' => 'approved',
            //     'kyc_approved_at' => now(),
            //     'kyc_approved_by' => auth()->id(),
            //     'registration_status' => 'approved',
            //     'is_active' => true
            // ]);

            $client->update([
                'kyc_status' => 'approved',
                'kyc_approved_at' => now(),
                'kyc_approved_by' => auth()->id(),
                'registration_status' => 'approved',
            ]);


            return redirect()
                ->route('admin.clients.show', $clientId)
                ->with('success', 'KYC approuvé avec succès. Le client peut maintenant utiliser ses services.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de l\'approbation KYC: ' . $e->getMessage());
        }
    }

    /**
     * Rejeter KYC
     */
    public function rejectKyc(Request $request, $clientId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $client = Client::findOrFail($clientId);

            // $client->update([
            //     'kyc_status' => 'rejected',
            //     'registration_status' => 'rejected',
            //     'rejection_reason' => $request->rejection_reason,
            //     'is_active' => false
            // ]);

            $client->update([
                'kyc_status' => 'rejected',
                'registration_status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);


            return redirect()
                ->route('admin.clients.show', $clientId)
                ->with('success', 'KYC rejeté. Le client a été notifié.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors du rejet KYC: ' . $e->getMessage());
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
