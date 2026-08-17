@extends('layouts.cashier')

@section('title', 'Liste des Clients')
@section('page-title', 'Tous les Clients')
@section('page-subtitle', 'Gestion de vos clients')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête avec statistiques -->
    <div class="row gal-4 mb-4">
        <!-- Stats Clients -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-blur-teal p-3 rounded-3 me-3 text-teal-accent">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">Total Clients</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['total'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-info-soft p-3 rounded-3 me-3 text-info">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">KYC En Attente</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['pending_kyc'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-blur-teal p-3 rounded-3 me-3" style="color: #00ffaa;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">KYC Approuvés</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['approved'] }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card glass-card h-100 border-0 shadow-lg">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-dark-soft p-3 rounded-3 me-3 text-warning">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 mb-1">Aujourd'hui</h6>
                        <h3 class="text-white fw-bold mb-0">{{ $stats['today'] }}</h3>
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
            <form method="GET" action="{{ route('caissier.clients.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control custom-input" name="search" placeholder="Nom, téléphone, n° client..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select custom-input" name="kyc_status">
                            <option value="">Tous les statuts KYC</option>
                            <option value="pending" {{ request('kyc_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="approved" {{ request('kyc_status') == 'approved' ? 'selected' : '' }}>Approuvé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select custom-input" name="registration_status">
                            <option value="">Tous les statuts d'inscription</option>
                            <option value="pending" {{ request('registration_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="approved" {{ request('registration_status') == 'approved' ? 'selected' : '' }}>Approuvé</option>
                        </select>
                    </div>
                    <div class="col-md-2">
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
            <h5 class="mb-0 text-white fw-bold"><i class="fas fa-list me-2 text-teal-accent"></i>Liste des Clients ({{ $clients->total() }})</h5>
            <a href="{{ route('caissier.clients.register-with-tontine') }}" class="btn btn-outline-white btn-sm rounded-pill px-3">
                <i class="fas fa-plus me-1"></i> Nouveau Client
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table custom-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>N° Client</th>
                            <th>Nom Complet</th>
                            <th>Téléphone</th>
                            <th>Comptes</th>
                            <th>KYC</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td class="text-teal-accent fw-bold">{{ $client->client_number }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-small me-2">
                                        {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-white fw-bold">{{ $client->first_name }} {{ $client->last_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-white-50">{{ $client->phone }}</td>
                            <td>
                                <span class="badge bg-blur-teal text-teal-accent border border-teal-soft">{{ $client->accounts->count() }} Compte(s)</span>
                            </td>
                            <td>
                                @if($client->kyc_status == 'approved')
                                    <span class="badge bg-success-soft text-success"><i class="fas fa-check me-1"></i>Approuvé</span>
                                @else
                                    <span class="badge bg-warning-soft text-warning"><i class="fas fa-clock me-1"></i>En attente</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('caissier.clients.show', $client->id) }}" class="btn btn-sm action-btn">
                                    <i class="fas fa-eye text-teal-accent"></i>
                                </a>
                                <a href="{{ route('caissier.accounts.create', $client->id) }}" class="btn btn-sm action-btn" title="Ouvrir un compte">
                                    <i class="fas fa-plus-circle text-info"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-users-slash fa-3x text-white-50 mb-3"></i>
                                <h5 class="text-white">Aucun client trouvé</h5>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($clients->hasPages())
                <div class="p-4 border-top border-white-5 custom-pagination">
                    {{ $clients->links() }}
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
    .border-teal-soft { border-color: rgba(0, 209, 178, 0.3) !important; }
    .bg-info-soft { background: rgba(0, 209, 178, 0.05); }
    .bg-dark-soft { background: rgba(0, 0, 0, 0.2); }
    .bg-success-soft { background: rgba(0, 255, 170, 0.1); }
    .bg-warning-soft { background: rgba(255, 193, 7, 0.1); }

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
