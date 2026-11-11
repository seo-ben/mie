@extends('layouts.cashier')

@section('title', 'Comptes Tontine & Épargne')
@section('page-title', 'Comptes Tontine & Épargne')
@section('page-subtitle', 'Supervision des carnets et comptes de souscription des adhérents')

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Comptes Tontine & Épargne</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Comptes Clients</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.clients.register-with-tontine') }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Nouveau Compte Express
            </a>
        </div>
    </div>

    <!-- 4 KPIS PASTELS DES COMPTES -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <span class="kpi-trend text-primary"><i class="fas fa-piggy-bank"></i> Carnets</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $stats['total_accounts'] }}</div>
                    <div class="kpi-label">Total Comptes Clients</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-check"></i> Opérationnels</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700">{{ $stats['active_accounts'] }}</div>
                    <div class="kpi-label">Comptes Actifs en Caisse</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-amber">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <span class="kpi-trend text-warning"><i class="fas fa-exclamation-triangle"></i> À Suivre</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-amber-700">{{ $stats['suspended_accounts'] }}</div>
                    <div class="kpi-label">Comptes Suspendus / Attente</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-coins"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-vault"></i> Épargne & Tontine</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700 fs-4">{{ number_format($stats['total_balance'], 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Solde Cumulé Adhérents</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECHERCHE & FILTRAGE ÉLÉGANT -->
    <div class="white-card p-4 mb-4">
        <form method="GET" action="{{ route('caissier.accounts.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Recherche de Compte</label>
                    <div class="search-box-wrapper w-100">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" class="topbar-search-input" name="search" placeholder="Numéro de compte, nom client..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold text-dark fs-8 mb-1">Statut du Compte</label>
                    <select class="form-select rounded-3 border" style="padding: 9px 12px; font-size: 0.85rem;" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspendu / En attente</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Fermé</option>
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

    <!-- TABLEAU CORPORATIF BLANC DES COMPTES -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-piggy-bank text-emerald-600"></i>
                Liste des Comptes Tontine & Épargne ({{ $accounts->total() }})
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>N° Compte</th>
                        <th>Client Titulaire</th>
                        <th>Mise / Épargne</th>
                        <th>Fréquence</th>
                        <th>Progression Cotisation</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark font-monospace fs-8">{{ $account->account_number }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-dark bg-light border" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                    {{ strtoupper(substr($account->client->first_name ?? 'C', 0, 1)) }}{{ strtoupper(substr($account->client->last_name ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark fs-7">{{ $account->client->first_name }} {{ $account->client->last_name }}</div>
                                    <small class="text-muted fs-8 font-monospace">{{ $account->client->client_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($account->tontineAccount)
                                <div class="fw-bold text-dark fs-7">{{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} FCFA</div>
                                <small class="text-muted fs-8">Mise unitaire</small>
                            @else
                                <div class="fw-bold text-emerald-600 fs-7">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</div>
                                <small class="text-muted fs-8">Solde disponible</small>
                            @endif
                        </td>
                        <td>
                            @if($account->tontineAccount)
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2 fs-8 fw-semibold">
                                    {{ ucfirst($account->tontineAccount->payment_frequency) }}
                                </span>
                            @else
                                <span class="badge bg-light text-muted border px-2 py-1 rounded-2 fs-8">Épargne Libre</span>
                            @endif
                        </td>
                        <td style="min-width: 170px;">
                            @if($account->tontineAccount)
                                @php
                                    $tontine = $account->tontineAccount;
                                    $percentage = $tontine->total_expected > 0 ? min(100, ($tontine->total_paid / $tontine->total_expected) * 100) : 0;
                                @endphp
                                <div class="d-flex justify-content-between align-items-center fs-8 mb-1">
                                    <span class="text-muted">{{ number_format($tontine->total_paid, 0, ',', ' ') }} F</span>
                                    <span class="fw-bold text-dark">{{ number_format($percentage, 0) }}%</span>
                                </div>
                                <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 4px;">
                                    <div class="progress-bar bg-success" style="width: {{ $percentage }}%;"></div>
                                </div>
                            @else
                                <span class="text-muted fs-8"><i class="fas fa-check text-success me-1"></i> Compte Actif</span>
                            @endif
                        </td>
                        <td>
                            @if($account->status == 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                    <i class="fas fa-circle-check me-1"></i> Actif
                                </span>
                            @elseif($account->status == 'suspended')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                    <i class="fas fa-pause-circle me-1"></i> En attente
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                    {{ ucfirst($account->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('caissier.accounts.show', $account->id) }}" class="btn btn-light border text-dark" title="Détail du Compte">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($account->status == 'active')
                                    <a href="{{ route('caissier.accounts.deposit.form', $account->id) }}" class="btn btn-light border text-success" title="Dépôt direct">
                                        <i class="fas fa-arrow-down"></i>
                                    </a>
                                @endif
                                @if($account->status == 'suspended')
                                    <a href="{{ route('caissier.accounts.activate', $account->id) }}" class="btn btn-light border text-warning" title="Activer">
                                        <i class="fas fa-bolt"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="fas fa-wallet fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">Aucun compte trouvé</h6>
                                <p class="fs-8 text-muted mb-0">Modifiez vos critères de recherche ou ouvrez un nouveau carnet.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
            <div class="p-3 border-top bg-light d-flex justify-content-end">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
