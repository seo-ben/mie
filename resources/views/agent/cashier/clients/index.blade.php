@extends('layouts.cashier')

@section('title', 'Répertoire Clients')
@section('page-title', 'Portefeuille Clients')
@section('page-subtitle', 'Consultation, enrôlement et validation KYC au guichet')

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Répertoire des Clients</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Clients & Adhérents</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.clients.register-with-tontine') }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                <i class="fas fa-user-plus me-1"></i> Nouveau Client Express
            </a>
        </div>
    </div>

    <!-- 4 KPIS PASTELS CLIENTÈLE -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="kpi-trend text-primary"><i class="fas fa-address-book"></i> Répertoire</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $stats['total'] }}</div>
                    <div class="kpi-label">Total Clients Agence</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-amber">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span class="kpi-trend text-warning"><i class="fas fa-shield"></i> À Valider</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-amber-700">{{ $stats['pending_kyc'] }}</div>
                    <div class="kpi-label">Dossiers KYC en Attente</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-badge-check"></i> 100% Conforme</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700">{{ $stats['approved'] }}</div>
                    <div class="kpi-label">Clients KYC Validés</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-calendar-day"></i> Aujourd'hui</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700">{{ $stats['today'] }}</div>
                    <div class="kpi-label">Inscriptions du Jour</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ZONE DE RECHERCHE ET FILTRES ÉLÉGANTE -->
    <div class="white-card p-4 mb-4">
        <form method="GET" action="{{ route('caissier.clients.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Recherche de Client</label>
                    <div class="search-box-wrapper w-100">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" class="topbar-search-input" name="search" placeholder="Nom, prénom, téléphone, n° client..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Statut KYC</label>
                    <select class="form-select rounded-3 border" style="padding: 9px 12px; font-size: 0.85rem;" name="kyc_status">
                        <option value="">Tous les statuts KYC</option>
                        <option value="pending" {{ request('kyc_status') == 'pending' ? 'selected' : '' }}>En attente de validation</option>
                        <option value="approved" {{ request('kyc_status') == 'approved' ? 'selected' : '' }}>Conforme (Approuvé)</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Adhésion</label>
                    <select class="form-select rounded-3 border" style="padding: 9px 12px; font-size: 0.85rem;" name="registration_status">
                        <option value="">Tous statuts</option>
                        <option value="pending" {{ request('registration_status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="approved" {{ request('registration_status') == 'approved' ? 'selected' : '' }}>Validé</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-success fw-bold w-100 py-2 rounded-3 fs-7">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLEAU DU RÉPERTOIRE CLIENTS -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-address-book text-emerald-600"></i>
                Liste des Adhérents ({{ $clients->total() }})
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>N° Client</th>
                        <th>Client & Enrôlement</th>
                        <th>Téléphone</th>
                        <th>Comptes Liés</th>
                        <th>Statut KYC</th>
                        <th class="text-end">Actions Guichet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark font-monospace fs-8">{{ $client->client_number }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-dark bg-light border" style="width: 36px; height: 36px; font-size: 0.82rem;">
                                    {{ strtoupper(substr($client->first_name, 0, 1)) }}{{ strtoupper(substr($client->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark fs-7">{{ $client->first_name }} {{ $client->last_name }}</div>
                                    <small class="text-muted fs-8">Inscrit le {{ $client->created_at->format('d/m/Y') }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-dark fs-8 font-monospace"><i class="fas fa-phone text-muted me-1"></i>{{ $client->phone }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 rounded-2 fs-8 fw-semibold">
                                {{ $client->accounts->count() }} Compte(s)
                            </span>
                        </td>
                        <td>
                            @if($client->kyc_status == 'approved')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                    <i class="fas fa-check-circle me-1"></i> Conforme
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                    <i class="fas fa-clock me-1"></i> En Attente
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('caissier.clients.show', $client->id) }}" class="btn btn-light border text-dark" title="Voir Fiche Client">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('caissier.accounts.create', $client->id) }}" class="btn btn-light border text-primary" title="Nouveau Sous-Compte">
                                    <i class="fas fa-plus"></i>
                                </a>
                                @if($client->kyc_status != 'approved')
                                <form action="{{ route('caissier.clients.approve-kyc', $client->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="button" class="btn btn-light border text-success" title="Valider le KYC"
                                            data-confirm="Confirmez-vous la conformité des pièces justificatives et du dossier KYC pour l'adhérent <strong>{{ addslashes($client->full_name) }}</strong> ?"
                                            data-confirm-title="Validation KYC Adhérent"
                                            data-confirm-subtitle="Dossier N° {{ $client->client_number }}"
                                            data-confirm-btn="Valider le KYC"
                                            data-confirm-class="btn-success"
                                            data-confirm-icon="fa-user-check"
                                            data-confirm-icon-bg="#ecfdf5"
                                            data-confirm-icon-color="#059669">
                                        <i class="fas fa-user-check"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">Aucun client trouvé dans le portefeuille</h6>
                                <p class="fs-8 text-muted mb-0">Modifiez vos critères de recherche ou enregistrez un nouveau client.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clients->hasPages())
            <div class="p-3 border-top bg-light d-flex justify-content-end">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
