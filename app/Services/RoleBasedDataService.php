<?php
namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use App\Http\Middleware\RoleMiddleware;

class RoleBasedDataService
{
    public function getAccessibleClients(User $user, array $filters = [])
    {
        $query = Client::query();

        // Filtrage selon le rôle
        switch ($user->role) {
            case 'agent_terrain':
            case 'agent_agence':
                // Agent voit seulement ses clients
                $query->where('registered_by', $user->id);
                break;

            case 'gestionnaire_superviseur':
            case 'gestionnaire_credit':
                // Gestionnaire voit tous les clients de son agence
                $query->where('agency_id', $user->agency_id);
                break;

            case 'administrateur_systeme':
            case 'administrateur_reglementaire':
                // Admin voit tout - pas de filtre
                break;

            default:
                // Par sécurité, aucun accès
                $query->whereRaw('1 = 0');
        }

        // Appliquer les filtres de recherche
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('client_number', 'like', "%{$search}%");
            });
        }

        if (isset($filters['kyc_status'])) {
            $query->where('kyc_status', $filters['kyc_status']);
        }

        if (isset($filters['agency_id']) && $this->canFilterByAgency($user)) {
            $query->where('agency_id', $filters['agency_id']);
        }

        return $query->with(['agency', 'accounts'])
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function getAccessibleAccounts(User $user, array $filters = [])
    {
        $query = Account::with(['client', 'transactions']);

        // Filtrage selon le rôle via les clients
        $clientIds = $this->getAccessibleClientIds($user);
        $query->whereIn('client_id', $clientIds);

        if (isset($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getAccessibleTransactions(User $user, array $filters = [])
    {
        $query = Transaction::with(['account.client']);

        // Filtrage par comptes accessibles
        $accountIds = Account::whereIn('client_id', $this->getAccessibleClientIds($user))->pluck('id');
        $query->whereIn('account_id', $accountIds);

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('transaction_date', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function getAccessibleLoans(User $user, array $filters = [])
    {
        $query = Loan::with(['client.agency']);

        // Filtrage par clients accessibles
        $clientIds = $this->getAccessibleClientIds($user);
        $query->whereIn('client_id', $clientIds);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['risk_level'])) {
            $query->where('risk_level', $filters['risk_level']);
        }

        return $query->orderBy('application_date', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function getRoleBasedStatistics(User $user)
    {
        $clientIds = $this->getAccessibleClientIds($user);
        $accountIds = Account::whereIn('client_id', $clientIds)->pluck('id');

        return [
            'clients' => [
                'total' => count($clientIds),
                'kyc_approved' => Client::whereIn('id', $clientIds)->where('kyc_status', 'approved')->count(),
                'active' => Client::whereIn('id', $clientIds)->where('is_active', true)->count()
            ],
            'accounts' => [
                'total' => count($accountIds),
                'total_balance' => Account::whereIn('id', $accountIds)->sum('balance'),
                'savings_count' => Account::whereIn('id', $accountIds)->where('account_type', 'savings')->count(),
                'tontine_count' => Account::whereIn('id', $accountIds)->where('account_type', 'tontine')->count()
            ],
            'loans' => [
                'total_applications' => Loan::whereIn('client_id', $clientIds)->count(),
                'active_loans' => Loan::whereIn('client_id', $clientIds)->whereIn('status', ['active', 'disbursed'])->count(),
                'total_outstanding' => Loan::whereIn('client_id', $clientIds)->whereIn('status', ['active', 'disbursed'])->sum('outstanding_principal')
            ],
            'transactions' => [
                'this_month' => Transaction::whereIn('account_id', $accountIds)
                    ->whereMonth('transaction_date', now()->month)
                    ->where('status', 'completed')
                    ->sum('amount'),
                'count_this_month' => Transaction::whereIn('account_id', $accountIds)
                    ->whereMonth('transaction_date', now()->month)
                    ->where('status', 'completed')
                    ->count()
            ]
        ];
    }

    public function getUserPermissions(User $user, string $resource)
    {
        $permissions = [
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => false,
            'scope' => 'none'
        ];

        switch ($user->role) {
            case 'agent_terrain':
            case 'agent_agence':
                $permissions['can_view'] = true;
                $permissions['can_create'] = in_array($resource, ['clients', 'transactions']);
                $permissions['can_edit'] = in_array($resource, ['clients']);
                $permissions['scope'] = 'own';
                break;

            case 'gestionnaire_superviseur':
            case 'gestionnaire_credit':
                $permissions['can_view'] = true;
                $permissions['can_create'] = true;
                $permissions['can_edit'] = true;
                $permissions['can_approve'] = true;
                $permissions['scope'] = 'agency';
                break;

            case 'administrateur_systeme':
            case 'administrateur_reglementaire':
                $permissions['can_view'] = true;
                $permissions['can_create'] = true;
                $permissions['can_edit'] = true;
                $permissions['can_delete'] = true;
                $permissions['can_approve'] = true;
                $permissions['scope'] = 'global';
                break;
        }

        return $permissions;
    }

    public function getUserScope(User $user)
    {
        switch ($user->role) {
            case 'agent_terrain':
            case 'agent_agence':
                return [
                    'type' => 'individual',
                    'description' => 'Accès limité aux données personnelles'
                ];

            case 'gestionnaire_superviseur':
            case 'gestionnaire_credit':
                return [
                    'type' => 'agency',
                    'description' => 'Accès aux données de l\'agence: ' . ($user->agency->name ?? 'N/A')
                ];

            case 'administrateur_systeme':
            case 'administrateur_reglementaire':
                return [
                    'type' => 'global',
                    'description' => 'Accès complet à toutes les données du système'
                ];

            default:
                return [
                    'type' => 'none',
                    'description' => 'Aucun accès autorisé'
                ];
        }
    }

    private function getAccessibleClientIds(User $user)
    {
        switch ($user->role) {
            case 'agent_terrain':
            case 'agent_agence':
                return Client::where('registered_by', $user->id)->pluck('id')->toArray();

            case 'gestionnaire_superviseur':
            case 'gestionnaire_credit':
                return Client::where('agency_id', $user->agency_id)->pluck('id')->toArray();

            case 'administrateur_systeme':
            case 'administrateur_reglementaire':
                return Client::pluck('id')->toArray();

            default:
                return [];
        }
    }

    private function canFilterByAgency(User $user)
    {
        return in_array($user->role, [
            'administrateur_systeme', 
            'administrateur_reglementaire'
        ]);
    }
}