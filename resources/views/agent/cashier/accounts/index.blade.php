@extends('layouts.cashier')

@section('title', 'Comptes Tontine')
@section('page-title', 'Comptes Tontine')
@section('page-subtitle', 'Gestion des comptes tontine des clients')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête avec statistiques -->
    <div class="row g-4 mb-4">
        <!-- Stats Comptes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-blur-teal p-3 rounded-3 me-3 text-teal-accent">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">Total Comptes</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['total_accounts'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-blur-teal p-3 rounded-3 me-3 text-teal-accent" style="color: #00ffaa !important;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">Actifs</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['active_accounts'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-dark-soft p-3 rounded-3 me-3 text-warning">
                        <i class="fas fa-pause-circle fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">Suspendus</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['suspended_accounts'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-info-soft p-3 rounded-3 me-3 text-info">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">Solde Total (FCFA)</h6>
                        <h3 class="text-white fw-bold mb-0">{{ number_format($stats['total_balance'], 0, ',', ' ') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card glass-card border-0 shadow-lg mb-4">
        <div class="card-header bg-transparent border-bottom border-white-5 p-4">
            <h5 class="mb-0 text-white fw-bold"><i class="fas fa-filter me-2 text-teal-accent"></i>Recherche et Filtres</h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('caissier.accounts.index') }}">
                <div class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control custom-input" name="search" placeholder="Numéro compte, nom client..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select custom-input" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspendu/En attente d'activation</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Fermé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary-gradient w-100 h-100 fw-bold rounded-3">
                            <i class="fas fa-search me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card glass-card border-0 shadow-lg">
        <div class="card-header bg-transparent border-bottom border-white-5 p-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white fw-bold"><i class="fas fa-list me-2 text-teal-accent"></i>Liste des Comptes ({{ $accounts->total() }})</h5>
            <a href="{{ route('caissier.clients.register-with-tontine') }}" class="btn btn-outline-white btn-sm rounded-pill px-3">
                <i class="fas fa-plus me-1"></i> Nouveau Compte Express
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table custom-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>N° Compte</th>
                            <th>Client</th>
                            <th>Mise (FCFA)</th>
                            <th>Fréquence</th>
                            <th>Progression</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td class="text-teal-accent fw-bold">{{ $account->account_number }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-small me-2">
                                        {{ substr($account->client->first_name, 0, 1) }}{{ substr($account->client->last_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-white fw-bold">{{ $account->client->first_name }} {{ $account->client->last_name }}</div>
                                        <div class="text-white-50" style="font-size: 0.75rem;">{{ $account->client->client_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold">{{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }}</span>
                            </td>
                            <td>
                                <span class="badge bg-dark-soft text-white-50 border border-white-10">
                                    @lang('tontine.frequency.' . $account->tontineAccount->payment_frequency)
                                    {{ ucfirst($account->tontineAccount->payment_frequency) }}
                                </span>
                            </td>
                            <td style="width: 200px;">
                                @php
                                    $tontine = $account->tontineAccount;
                                    $percentage = $tontine->total_expected > 0 ? min(100, ($tontine->total_paid / $tontine->total_expected) * 100) : 0;
                                @endphp
                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.75rem;">
                                    <span class="text-white-50">{{ number_format($tontine->total_paid, 0, ',', ' ') }} F</span>
                                    <span class="text-teal-accent fw-bold">{{ number_format($percentage, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                                    <div class="progress-bar bg-teal-accent" role="progressbar" style="width: {{ $percentage }}%; background-color: #00d1b2;"></div>
                                </div>
                            </td>
                            <td>
                                @if($account->status == 'active')
                                    <span class="badge bg-success-soft text-success"><i class="fas fa-check-circle me-1"></i>Actif</span>
                                @elseif($account->status == 'suspended')
                                    <span class="badge bg-warning-soft text-warning"><i class="fas fa-pause-circle me-1"></i>En attente</span>
                                @else
                                    <span class="badge bg-danger-soft text-danger"><i class="fas fa-times-circle me-1"></i>{{ $account->status }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('caissier.accounts.show', $account->id) }}" class="btn btn-sm action-btn">
                                    <i class="fas fa-eye text-teal-accent"></i>
                                </a>
                                @if($account->status == 'active')
                                    <a href="{{ route('caissier.accounts.deposit.form', $account->id) }}" class="btn btn-sm action-btn">
                                        <i class="fas fa-arrow-down text-info"></i>
                                    </a>
                                @endif
                                @if($account->status == 'suspended')
                                    <a href="{{ route('caissier.accounts.activate', $account->id) }}" class="btn btn-sm action-btn border-warning">
                                        <i class="fas fa-bolt text-warning"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-wallet fa-3x text-white-50 mb-3"></i>
                                <h5 class="text-white">Aucun compte trouvé</h5>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
                <div class="p-4 border-top border-white-5 custom-pagination">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .glass-card {
        background: rgba(26, 35, 50, 0.7);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 20px;
    }

    .custom-input {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: white !important;
        border-radius: 12px;
        padding: 10px 16px;
    }
    .custom-input:focus {
        border-color: #00d1b2 !important;
        box-shadow: 0 0 0 4px rgba(0, 209, 178, 0.15) !important;
    }

    .btn-primary-gradient {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
        border: none;
        color: white;
        transition: all 0.3s;
    }
    .btn-primary-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 209, 178, 0.2);
    }

    .btn-outline-white {
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    .btn-outline-white:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    /* Table styles */
    .custom-table {
        color: white;
    }
    .custom-table thead th {
        background: rgba(0, 0, 0, 0.3) !important;
        color: #00d1b2;
        border-bottom: none;
        padding: 15px 20px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    .custom-table tbody td {
        background: transparent !important;
        border-color: rgba(255, 255, 255, 0.05);
        padding: 15px 20px;
        vertical-align: middle;
    }
    .custom-table tbody tr {
        transition: background 0.2s;
    }
    .custom-table tbody tr:hover td {
        background: rgba(255, 255, 255, 0.02) !important;
    }

    /* Badges */
    .bg-blur-teal { background: rgba(0, 209, 178, 0.1); }
    .text-teal-accent { color: #00d1b2; }
    .border-white-10 { border-color: rgba(255, 255, 255, 0.1) !important; }
    .border-teal-soft { border-color: rgba(0, 209, 178, 0.3) !important; }
    .bg-info-soft { background: rgba(0, 209, 178, 0.05); }
    .bg-dark-soft { background: rgba(0, 0, 0, 0.2); }
    .bg-success-soft { background: rgba(0, 255, 170, 0.1); }
    .bg-warning-soft { background: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background: rgba(255, 0, 0, 0.1); }

    .user-avatar-small {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(0, 209, 178, 0.2), rgba(0, 180, 216, 0.2));
        color: #00d1b2;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        border: 1px solid rgba(0, 209, 178, 0.3);
    }

    .action-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        margin-right: 5px;
    }
    .action-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }
</style>
@endsection
