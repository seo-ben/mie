<?php
namespace App\Http\Controllers\web\Shared;

use App\Http\Controllers\Controller;
use App\Services\RoleBasedDataService;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;

class SharedDataController extends Controller
{
    public function __construct(
        private RoleBasedDataService $dataService
    ) {}

    /**
     * Données clients selon le rôle
     */
    public function clients(Request $request)
    {
        $user = auth()->user();
        $clients = $this->dataService->getAccessibleClients($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $clients,
            'permissions' => $this->dataService->getUserPermissions($user, 'clients')
        ]);
    }

    /**
     * Données des comptes selon le rôle
     */
    public function accounts(Request $request)
    {
        $user = auth()->user();
        $accounts = $this->dataService->getAccessibleAccounts($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $accounts,
            'permissions' => $this->dataService->getUserPermissions($user, 'accounts')
        ]);
    }

    /**
     * Données des transactions selon le rôle
     */
    public function transactions(Request $request)
    {
        $user = auth()->user();
        $transactions = $this->dataService->getAccessibleTransactions($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'permissions' => $this->dataService->getUserPermissions($user, 'transactions')
        ]);
    }

    /**
     * Données des prêts selon le rôle
     */
    public function loans(Request $request)
    {
        $user = auth()->user();
        $loans = $this->dataService->getAccessibleLoans($user, $request->all());

        return response()->json([
            'success' => true,
            'data' => $loans,
            'permissions' => $this->dataService->getUserPermissions($user, 'loans')
        ]);
    }

    /**
     * Statistiques basées sur le rôle
     */
    public function roleBasedStats()
    {
        $user = auth()->user();
        $stats = $this->dataService->getRoleBasedStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'scope' => $this->dataService->getUserScope($user)
        ]);
    }

    /**
     * Options de recherche disponibles
     */
    public function searchOptions()
    {
        $user = auth()->user();

        $options = [
            'agencies' => [],
            'client_statuses' => [
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'approved', 'label' => 'Approuvé'],
                ['value' => 'rejected', 'label' => 'Rejeté']
            ],
            'account_types' => [
                ['value' => 'savings', 'label' => 'Épargne'],
                ['value' => 'tontine', 'label' => 'Tontine']
            ],
            'transaction_types' => [
                ['value' => 'deposit', 'label' => 'Dépôt'],
                ['value' => 'withdrawal', 'label' => 'Retrait'],
                ['value' => 'transfer', 'label' => 'Transfert'],
                ['value' => 'fee', 'label' => 'Frais'],
                ['value' => 'interest', 'label' => 'Intérêts'],
                ['value' => 'penalty', 'label' => 'Pénalité'],
                ['value' => 'payout', 'label' => 'Redistribution']
            ],
            'loan_statuses' => [
                ['value' => 'pending', 'label' => 'En attente'],
                ['value' => 'under_review', 'label' => 'En cours d\'évaluation'],
                ['value' => 'approved', 'label' => 'Approuvé'],
                ['value' => 'rejected', 'label' => 'Rejeté'],
                ['value' => 'disbursed', 'label' => 'Décaissé'],
                ['value' => 'active', 'label' => 'Actif'],
                ['value' => 'completed', 'label' => 'Remboursé'],
                ['value' => 'defaulted', 'label' => 'En défaut']
            ],
            'payment_methods' => [
                ['value' => 'cash', 'label' => 'Espèces'],
                ['value' => 'mobile_money', 'label' => 'Mobile Money'],
                ['value' => 'bank_transfer', 'label' => 'Virement bancaire'],
                ['value' => 'system', 'label' => 'Système']
            ]
        ];

        // Ajouter les agences selon les permissions
        if (in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire'])) {
            $options['agencies'] = Agency::select('id', 'name', 'code')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($agency) => [
                    'value' => $agency->id,
                    'label' => $agency->name . ' (' . $agency->code . ')'
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }

    /**
     * Données de configuration pour l'interface
     */
    public function appConfig()
    {
        $user = auth()->user();

        $config = [
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'role' => $user->role,
                'agency_id' => $user->agency_id,
                'agency_name' => $user->agency->name ?? null,
                'permissions' => $this->getUserPermissionSummary($user)
            ],
            'app' => [
                'name' => config('app.name'),
                'version' => '1.0.0',
                'currency' => 'FCFA',
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone')
            ],
            'limits' => [
                'max_file_size' => '10MB',
                'max_loan_amount' => 5000000,
                'min_loan_amount' => 10000,
                'max_transaction_amount' => 10000000
            ],
            'features' => [
                'mobile_money_enabled' => true,
                'biometric_enabled' => true,
                'offline_mode_enabled' => true,
                'multi_language' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Données de référence (lookup data)
     */
    public function referenceData()
    {
        $data = [
            'countries' => [
                ['code' => 'TG', 'name' => 'Togo'],
                ['code' => 'BJ', 'name' => 'Bénin'],
                ['code' => 'BF', 'name' => 'Burkina Faso']
            ],
            'regions_togo' => [
                'Maritime', 'Plateaux', 'Centrale', 'Kara', 'Savanes'
            ],
            'id_types' => [
                ['value' => 'cni', 'label' => 'Carte Nationale d\'Identité'],
                ['value' => 'passport', 'label' => 'Passeport'],
                ['value' => 'driving_license', 'label' => 'Permis de Conduire'],
                ['value' => 'other', 'label' => 'Autre']
            ],
            'professions' => [
                'Commerçant', 'Agriculteur', 'Artisan', 'Fonctionnaire',
                'Enseignant', 'Chauffeur', 'Couturier/Couturière',
                'Coiffeur/Coiffeuse', 'Mécanicien', 'Maçon', 'Autre'
            ],
            'mobile_operators' => [
                ['code' => 'MTN', 'name' => 'MTN Togo', 'prefixes' => ['90', '91', '96', '97']],
                ['code' => 'MOOV', 'name' => 'Moov Togo', 'prefixes' => ['93', '94', '95', '98', '99']],
                ['code' => 'ORANGE', 'name' => 'Orange Togo', 'prefixes' => ['92']]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Lookup rapide pour autocomplétion
     */
    public function quickSearch(Request $request)
    {
        $request->validate([
            'type' => 'required|in:clients,accounts,loans,users',
            'query' => 'required|string|min:2',
            'limit' => 'nullable|integer|max:20'
        ]);

        $user = auth()->user();
        $query = $request->get('query');
        $type = $request->get('type');
        $limit = $request->get('limit', 10);

        $results = [];

        switch ($type) {
            case 'clients':
                $clientIds = $this->dataService->getAccessibleClientIds($user);
                $results = Client::whereIn('id', $clientIds)
                    ->where(function($q) use ($query) {
                        $q->where('first_name', 'LIKE', "%{$query}%")
                          ->orWhere('last_name', 'LIKE', "%{$query}%")
                          ->orWhere('phone', 'LIKE', "%{$query}%")
                          ->orWhere('client_number', 'LIKE', "%{$query}%");
                    })
                    ->limit($limit)
                    ->get(['id', 'client_number', 'first_name', 'last_name', 'phone'])
                    ->map(fn($client) => [
                        'id' => $client->id,
                        'text' => $client->full_name . ' (' . $client->client_number . ')',
                        'subtitle' => $client->phone
                    ]);
                break;

            case 'accounts':
                $accountIds = Account::whereIn('client_id', $this->dataService->getAccessibleClientIds($user))
                    ->pluck('id');
                $results = Account::whereIn('id', $accountIds)
                    ->with('client')
                    ->where('account_number', 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->get()
                    ->map(fn($account) => [
                        'id' => $account->id,
                        'text' => $account->account_number . ' - ' . ucfirst($account->account_type),
                        'subtitle' => $account->client->full_name
                    ]);
                break;

            case 'loans':
                $results = Loan::whereHas('client', function($q) use ($user) {
                        $clientIds = $this->dataService->getAccessibleClientIds($user);
                        $q->whereIn('id', $clientIds);
                    })
                    ->with('client')
                    ->where('loan_number', 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->get()
                    ->map(fn($loan) => [
                        'id' => $loan->id,
                        'text' => $loan->loan_number . ' - ' . number_format($loan->approved_amount, 0) . ' FCFA',
                        'subtitle' => $loan->client->full_name . ' (' . $loan->status . ')'
                    ]);
                break;

            case 'users':
                if (!in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire', 'gestionnaire_superviseur'])) {
                    break;
                }

                $results = User::where(function($q) use ($query) {
                        $q->where('first_name', 'LIKE', "%{$query}%")
                          ->orWhere('last_name', 'LIKE', "%{$query}%")
                          ->orWhere('email', 'LIKE', "%{$query}%");
                    })
                    ->when($user->role !== 'administrateur_systeme', function($q) use ($user) {
                        $q->where('agency_id', $user->agency_id);
                    })
                    ->limit($limit)
                    ->get(['id', 'first_name', 'last_name', 'email', 'role'])
                    ->map(fn($usr) => [
                        'id' => $usr->id,
                        'text' => $usr->full_name,
                        'subtitle' => $usr->email . ' (' . $usr->role . ')'
                    ]);
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    private function getUserPermissionSummary($user)
    {
        $permissions = $this->dataService->getUserPermissions($user, 'global');

        return [
            'can_create_clients' => $permissions['can_create'] ?? false,
            'can_approve_kyc' => in_array($user->role, ['gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_approve_loans' => in_array($user->role, ['gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_validate_transactions' => in_array($user->role, ['agent_agence', 'gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_generate_reports' => in_array($user->role, ['gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire']),
            'can_manage_users' => in_array($user->role, ['administrateur_systeme', 'administrateur_reglementaire']),
            'can_configure_system' => $user->role === 'administrateur_systeme',
            'scope' => $permissions['scope'] ?? 'individual'
        ];
    }
}
