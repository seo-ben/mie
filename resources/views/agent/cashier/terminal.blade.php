@extends('layouts.cashier')

@section('title', 'Terminal de Caisse')
@section('page-title', 'Terminal de Caisse')
@section('page-subtitle', $stats->is_audit ? 'Mode Audit & Consultation d\'Historique' : 'Journal des Opérations de la Session Active')

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS RAPIDES -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Terminal de Caisse</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Terminal & Journal</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('caissier.depot') }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                <i class="fas fa-plus me-1"></i> Nouveau Dépôt
            </a>
            <a href="{{ route('caissier.retrait') }}" class="btn btn-outline-danger fw-bold px-3 py-2 rounded-3 fs-7">
                <i class="fas fa-minus me-1"></i> Nouveau Retrait
            </a>
            <button type="button" class="btn btn-outline-warning border fw-bold text-dark px-3 py-2 rounded-3 fs-7" data-bs-toggle="modal" data-bs-target="#vaultTransferModal">
                <i class="fas fa-building-columns me-1"></i> Coffre
            </button>
        </div>
    </div>

    <!-- 4 KPIS PASTELS DU TERMINAL DE CAISSE -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-play"></i> Ouverture</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700 fs-4">{{ number_format($stats->opening_balance, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Provision Initiale Caisse</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-plus"></i> Inflow</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700 fs-4">+{{ number_format($stats->total_in, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Total Entrées Guichet</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-rose">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <span class="kpi-trend text-danger"><i class="fas fa-minus"></i> Outflow</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-rose-700 fs-4">-{{ number_format($stats->total_out, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Total Sorties Guichet</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <span class="kpi-trend text-primary"><i class="fas fa-vault"></i> En Caisse</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-primary fs-4">{{ number_format($stats->current_balance, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Solde Consolidé Théorique</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BARRE D'AUDIT & FILTRAGE -->
    <div class="white-card p-4 mb-4">
        <form method="GET" action="{{ route('caissier.terminal') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-bold text-dark fs-8 mb-1">Date Début</label>
                <input type="date" name="date_start" class="form-control rounded-3 border" style="padding: 9px 12px; font-size: 0.88rem;" value="{{ request('date_start') }}">
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-bold text-dark fs-8 mb-1">Date Fin</label>
                <input type="date" name="date_end" class="form-control rounded-3 border" style="padding: 9px 12px; font-size: 0.88rem;" value="{{ request('date_end') }}">
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-success fw-bold flex-grow-1 py-2 rounded-3 fs-7">
                    <i class="fas fa-search me-1"></i> Auditer la Période
                </button>
                <a href="{{ route('caissier.terminal') }}" class="btn btn-light border fw-bold py-2 px-3 rounded-3 fs-7 text-dark">
                    Session Actuelle
                </a>
            </div>
        </form>
    </div>

    @if(!$stats->is_audit && isset($activeSession))
        <div class="p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                    <span class="status-dot d-inline-block me-1" style="width: 7px; height: 7px; border-radius: 50%; background: #10b981;"></span> Session Active
                </span>
                <span class="text-dark fs-8">Ouverte le {{ \Carbon\Carbon::parse($activeSession->opened_at)->format('d/m/Y à H:i') }}</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-warning text-dark border-warning-subtle fw-bold rounded-2 px-3" data-bs-toggle="modal" data-bs-target="#vaultTransferModal">
                    <i class="fas fa-building-columns me-1"></i> Transfert Coffre
                </button>
                <a href="{{ route('caissier.session.close.form') }}" class="btn btn-sm btn-outline-danger fw-bold rounded-2 px-3">
                    <i class="fas fa-lock me-1"></i> Clôturer Caisse
                </a>
            </div>
        </div>
    @endif

    <!-- TABLEAU DU JOURNAL DES ÉCRITURES DE CAISSE -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="fas fa-receipt text-primary"></i>
                    Journal des Écritures de Caisse ({{ $recentTransactions->total() }})
                </h6>
                <small class="text-muted fs-8">Flux chronologiques et écritures de guichet</small>
            </div>

            <!-- Filtres par statut d'écriture (Actives vs Extournées) -->
            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1 fs-8 fw-bold {{ ($statusFilter ?? 'all') === 'all' ? 'btn-primary shadow-sm text-white' : 'btn-light border text-dark' }}">
                    Toutes ({{ $countAll ?? $recentTransactions->total() }})
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'completed', 'page' => 1]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1 fs-8 fw-bold {{ ($statusFilter ?? 'all') === 'completed' ? 'btn-success shadow-sm text-white' : 'btn-light border text-dark' }}">
                    <i class="fas fa-check-circle me-1"></i> Validées ({{ $countCompleted ?? 0 }})
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'cancelled', 'page' => 1]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1 fs-8 fw-bold {{ ($statusFilter ?? 'all') === 'cancelled' ? 'btn-danger shadow-sm text-white' : 'btn-light border text-danger' }}">
                    <i class="fas fa-ban me-1"></i> Extournées ({{ $countCancelled ?? 0 }})
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>Réf / Heure</th>
                        <th>Client & Compte</th>
                        <th>Nature Opération</th>
                        <th>Canal</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $tx)
                        @php
                            $isCancelled = ($tx->status === 'cancelled');
                            $isIn = in_array($tx->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment', 'transfer_in']);
                            $accType = $tx->account?->account_type;
                        @endphp
                        <tr class="{{ $isCancelled ? 'table-danger-subtle bg-danger-subtle/10' : '' }}">
                            <td>
                                <div class="fw-bold text-dark fs-8">{{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('H:i:s') : $tx->created_at->format('H:i:s') }}</div>
                                <span class="badge {{ $isCancelled ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border' }} font-monospace fs-8">{{ $tx->transaction_reference }}</span>
                                @if($isCancelled)
                                    <small class="text-danger fs-9 d-block fw-semibold mt-0.5" title="{{ $tx->description }}">
                                        <i class="fas fa-ban me-1"></i> Extournée (Annulée)
                                    </small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @php
                                        $clientObj = $tx->client;
                                        $refNumber = $tx->account?->account_number ?? $tx->loan?->loan_number ?? '-';
                                        $initial = strtoupper(substr($clientObj->first_name ?? ($tx->account ? 'C' : 'P'), 0, 1));
                                    @endphp
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center fw-bold text-dark" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                        {{ $initial }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-7">{{ $clientObj->full_name ?? 'Opération de Caisse' }}</div>
                                        <small class="text-muted fs-8 font-monospace">{{ $refNumber }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <span class="badge {{ $isCancelled ? 'bg-secondary-subtle text-secondary border' : ($isIn ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle') }} fs-8 fw-bold">
                                        @if($tx->transaction_type === 'loan_disbursement') DÉCAISSEMENT PRÊT
                                        @elseif($tx->transaction_type === 'loan_repayment') REMBOURSEMENT PRÊT
                                        @elseif($accType === 'tontine') TONTINE ({{ $isIn ? 'DÉPÔT' : 'RETRAIT' }})
                                        @elseif($accType === 'savings') ÉPARGNE ({{ $isIn ? 'DÉPÔT' : 'RETRAIT' }})
                                        @else {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }} @endif
                                    </span>
                                    @if($isCancelled)
                                        <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 fs-9 fw-bold">
                                            <i class="fas fa-rotate-left me-1"></i> Extourné
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-muted border text-capitalize fs-8">
                                    {{ str_replace('_', ' ', $tx->payment_method) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($isCancelled)
                                    <del class="text-muted fw-bold fs-7">{{ number_format($tx->amount, 0, ',', ' ') }} FCFA</del>
                                    <small class="text-danger d-block fs-9 font-monospace">Annulé</small>
                                @else
                                    <span class="fw-bold fs-7 {{ $isIn ? 'text-success' : 'text-danger' }}">
                                        {{ $isIn ? '+' : '-' }} {{ number_format($tx->amount, 0, ',', ' ') }} FCFA
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if(!$isCancelled)
                                        <a href="{{ route('caissier.receipt.print', $tx->id) }}" target="_blank" class="btn btn-light border text-dark" title="Imprimer le Reçu">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <button type="button" class="btn btn-light border text-danger" title="Extourner avec PIN Superviseur" onclick="triggerReversal('{{ $tx->transaction_reference }}', '{{ number_format($tx->amount, 0, ',', ' ') }}')">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-8" title="{{ $tx->description }}">
                                            <i class="fas fa-ban me-1"></i> Extourné
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold text-dark mb-1">Aucune transaction enregistrée pour cette période</h6>
                                    <p class="fs-8 text-muted mb-0">Les flux enregistrés au guichet apparaîtront automatiquement en temps réel.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentTransactions->hasPages())
            <div class="p-3 border-top bg-light d-flex justify-content-end">
                {{ $recentTransactions->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
