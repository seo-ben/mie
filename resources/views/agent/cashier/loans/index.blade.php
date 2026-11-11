@extends('layouts.cashier')

@section('title', 'Gestion des Prêts & Crédits')
@section('page-title', 'Portefeuille des Prêts au Guichet')
@section('page-subtitle', 'Suivi des engagements, décaissements et remboursements')

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Portefeuille des Prêts & Crédits</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Gestion des Prêts</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('caissier.loans.apply.form') }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                <i class="fas fa-file-circle-plus me-1"></i> Nouvelle Demande de Prêt
            </a>
            <a href="{{ route('caissier.loans.disbursement') }}" class="btn btn-light border fw-bold px-3 py-2 rounded-3 fs-7 text-dark">
                <i class="fas fa-money-bill-transfer me-1 text-emerald-600"></i> Décaissements
            </a>
        </div>
    </div>

    <!-- 4 KPIS PASTELS CRÉDITS (INSPIRATION DASHBOARD REFERENCE) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-folder-closed"></i>
                    </div>
                    <span class="kpi-trend text-primary"><i class="fas fa-chart-simple"></i> Portefeuille</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $metrics->total }}</div>
                    <div class="kpi-label">Total Dossiers Prêts</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-amber">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span class="kpi-trend text-warning"><i class="fas fa-hand-holding-dollar"></i> À Décaisser</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-amber-700">{{ $metrics->approved }}</div>
                    <div class="kpi-label">Prêts Approuvés</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-arrow-trend-up"></i> Recouvrement</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700">{{ $metrics->disbursed }}</div>
                    <div class="kpi-label">Prêts Décaissés / Actifs</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-check-double"></i> 100% Soldés</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700">{{ $metrics->completed }}</div>
                    <div class="kpi-label">Prêts Soldés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BARRE D'ACTION ET FILTRES RECHERCHE -->
    <div class="white-card p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="filter-segment-group">
                <a href="{{ route('caissier.loans.index') }}" class="filter-segment-pill {{ $status === 'all' ? 'active' : '' }}">
                    Tous <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $metrics->total }}</span>
                </a>
                <a href="{{ route('caissier.loans.index') }}?status=approved" class="filter-segment-pill {{ $status === 'approved' ? 'active' : '' }}">
                    Approuvés <span class="badge bg-warning-subtle text-warning ms-1">{{ $metrics->approved }}</span>
                </a>
                <a href="{{ route('caissier.loans.index') }}?status=disbursed" class="filter-segment-pill {{ $status === 'disbursed' ? 'active' : '' }}">
                    Décaissés <span class="badge bg-success-subtle text-success ms-1">{{ $metrics->disbursed }}</span>
                </a>
                <a href="{{ route('caissier.loans.index') }}?status=completed" class="filter-segment-pill {{ $status === 'completed' ? 'active' : '' }}">
                    Soldés <span class="badge bg-info-subtle text-info ms-1">{{ $metrics->completed }}</span>
                </a>
            </div>

            <form action="{{ route('caissier.loans.index') }}" method="GET" class="d-flex gap-2">
                <div class="search-box-wrapper" style="width: 260px;">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="topbar-search-input" placeholder="N° prêt, nom adhérent...">
                </div>
                @if(request('search'))
                    <a href="{{ route('caissier.loans.index') }}" class="btn btn-sm btn-light border"><i class="fas fa-xmark"></i></a>
                @endif
            </form>
        </div>
    </div>

    <!-- LISTE CORPORATE DES PRÊTS -->
    <div class="white-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>N° Dossier</th>
                        <th>Adhérent (Client)</th>
                        <th>Montant Accordé</th>
                        <th>Total Dû</th>
                        <th>Remboursé / Progression</th>
                        <th>Statut</th>
                        <th class="text-end">Actions Guichet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        @php
                            $paidPct = $loan->total_amount_due > 0 ? min(100, round(($loan->total_paid / $loan->total_amount_due) * 100)) : 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-dark font-monospace fs-8">{{ $loan->loan_number }}</div>
                                <small class="text-muted fs-8">{{ $loan->created_at->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center fw-bold text-dark" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                        {{ strtoupper(substr($loan->client->first_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-7">{{ $loan->client->full_name }}</div>
                                        <small class="text-muted fs-8 font-monospace">{{ $loan->client->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-7 font-numeric">{{ number_format($loan->approved_amount ?? $loan->requested_amount, 0, ',', ' ') }} FCFA</div>
                                <small class="text-muted fs-8">{{ $loan->duration_months }} mois à {{ $loan->interest_rate }}%</small>
                            </td>
                            <td>
                                <span class="fw-bold text-dark font-numeric fs-7">{{ number_format($loan->total_amount_due, 0, ',', ' ') }} FCFA</span>
                            </td>
                            <td style="min-width: 170px;">
                                <div class="d-flex justify-content-between fs-8 fw-bold mb-1">
                                    <span class="text-success font-numeric">{{ number_format($loan->total_paid, 0, ',', ' ') }} F</span>
                                    <span class="text-muted">{{ $paidPct }}%</span>
                                </div>
                                <div class="progress" style="height: 5px; background: #f1f5f9;">
                                    <div class="progress-bar bg-success" style="width: {{ $paidPct }}%"></div>
                                </div>
                            </td>
                            <td>
                                @switch($loan->status)
                                    @case('approved')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                            Approuvé (À Décaisser)
                                        </span>
                                        @break
                                    @case('disbursed')
                                    @case('active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                            Actif / En cours
                                        </span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                            Soldé 🎉
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill fs-8">
                                            En Étude
                                        </span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark border px-2 py-1 rounded-pill fs-8">{{ ucfirst($loan->status) }}</span>
                                @endswitch
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('caissier.loans.show', $loan->id) }}" class="btn btn-light border text-dark" title="Voir Fiche Prêt & Échéancier">
                                        <i class="fas fa-eye me-1"></i> Échéancier
                                    </a>

                                    @if($loan->status === 'approved')
                                        <form action="{{ route('caissier.loans.disburse', $loan->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="button" class="btn btn-success fw-bold" title="Décaisser les espèces"
                                                    data-confirm="Confirmer le décaissement physique de <strong>{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</strong> pour l'emprunteur <strong>{{ addslashes($loan->client->full_name) }}</strong> ?"
                                                    data-confirm-title="Décaissement de Prêt"
                                                    data-confirm-subtitle="Remise des fonds en espèces • Prêt {{ $loan->loan_number }}"
                                                    data-confirm-btn="Confirmer le Décaissement"
                                                    data-confirm-class="btn-success"
                                                    data-confirm-icon="fa-hand-holding-dollar"
                                                    data-confirm-icon-bg="#ecfdf5"
                                                    data-confirm-icon-color="#059669">
                                                <i class="fas fa-hand-holding-dollar me-1"></i> Décaisser
                                            </button>
                                        </form>
                                    @elseif(in_array($loan->status, ['disbursed', 'active']))
                                        <a href="{{ route('caissier.loans.show', ['loan' => $loan->id, 'repay' => 1]) }}" class="btn btn-success fw-bold text-white shadow-sm" title="Encaisser un Remboursement">
                                            <i class="fas fa-coins me-1"></i> Rembourser
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold text-dark mb-1">Aucun dossier de prêt trouvé</h6>
                                    <p class="fs-8 text-muted mb-0">Modifiez vos critères ou enregistrez une nouvelle demande.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loans->hasPages())
            <div class="p-3 border-top bg-light d-flex justify-content-end">
                {{ $loans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
