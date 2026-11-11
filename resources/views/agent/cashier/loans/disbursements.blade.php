@extends('layouts.cashier')

@section('title', 'Décaissement des Prêts & Crédits')
@section('page-title', 'Décaissement des Prêts')
@section('page-subtitle', 'Comptoir de remise physique des fonds pour les dossiers de crédit validés')

@push('styles')
<style>
    /* --- PASTILLES & BADGES COLORÉS AVEC CONTRASTE GARANTI --- */
    .badge-soft-emerald {
        background-color: #ecfdf5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .badge-solid-emerald {
        background-color: #059669 !important;
        color: #ffffff !important;
        border: 1px solid #047857 !important;
    }
    .badge-soft-purple {
        background-color: #f5f3ff !important;
        color: #5b21b6 !important;
        border: 1px solid #ddd6fe !important;
    }
    .badge-soft-amber {
        background-color: #fffbeb !important;
        color: #92400e !important;
        border: 1px solid #fde68a !important;
    }
    .badge-soft-rose {
        background-color: #fff1f2 !important;
        color: #9f1239 !important;
        border: 1px solid #fecdd3 !important;
    }
    .badge-soft-slate {
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
        border: 1px solid #cbd5e1 !important;
    }

    /* --- DESIGN TABLEAU BANCAIRE FINTECH --- */
    .disbursement-table {
        table-layout: auto;
    }
    .disbursement-table th {
        font-size: 0.72rem;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #64748b;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .disbursement-table td {
        padding-top: 14px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-row-hover {
        transition: background-color 0.15s ease-in-out;
    }
    .table-row-hover:hover {
        background-color: #f8fafc !important;
    }
    .table-client-avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        color: #047857;
        font-weight: 800;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1.5px solid #a7f3d0;
        flex-shrink: 0;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .search-disbursement-input {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 18px 12px 42px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .search-disbursement-input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
        outline: none;
    }
    #disbursementTabs .nav-link {
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    #disbursementTabs .nav-link.active {
        background: #10b981 !important;
        color: #ffffff !important;
        border-color: #10b981 !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }
</style>
@endpush

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE SECTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Comptoir de Décaissement</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item text-muted">Gestion des Prêts</li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Décaissements Validés</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.terminal') }}" class="btn btn-outline-secondary fw-semibold px-3 py-2 rounded-3 fs-7 bg-white">
                <i class="fas fa-cash-register me-1 text-muted"></i> Terminal Caisse
            </a>
            <a href="{{ route('caissier.depot') }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                <i class="fas fa-plus me-1"></i> Nouveau Dépôt
            </a>
        </div>
    </div>

    <!-- 4 KPIS PASTELS INSTITUTIONNELS -->
    <div class="row g-3 mb-4">
        <!-- 1. Dossiers en attente -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <span class="kpi-trend text-teal-700">
                        <i class="fas fa-clock"></i> Prêts Validés
                    </span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700 fs-4">{{ $totalPendingCount }} <small class="fs-8">dossier(s)</small></div>
                    <div class="kpi-label">Prêts en Attente de Décaissement</div>
                </div>
            </div>
        </div>

        <!-- 2. Volume à décaisser -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-peach">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <span class="kpi-trend text-orange-700">
                        <i class="fas fa-arrow-up-right-from-square"></i> Volume Brut
                    </span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-orange-700 fs-4">{{ number_format($totalPendingAmount, 0, ',', ' ') }} <small class="fs-8">FCFA</small></div>
                    <div class="kpi-label">Montant Global à Remettre</div>
                </div>
            </div>
        </div>

        <!-- 3. Liquidité caisse disponible -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-vault"></i>
                    </div>
                    <span class="kpi-trend text-success">
                        <i class="fas fa-shield-check"></i> Caisse
                    </span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-success fs-4">{{ number_format($currentCash, 0, ',', ' ') }} <small class="fs-8">FCFA</small></div>
                    <div class="kpi-label">Liquidité Disponible au Guichet</div>
                </div>
            </div>
        </div>

        <!-- 4. Décaissés aujourd'hui -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <span class="kpi-trend text-blue-700">
                        <i class="fas fa-calendar-day"></i> Aujourd'hui
                    </span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-blue-700 fs-4">{{ number_format($todayDisbursedAmount, 0, ',', ' ') }} <small class="fs-8">FCFA</small></div>
                    <div class="kpi-label">{{ $todayDisbursedCount }} dossier(s) décaissé(s) ce jour</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARTE DE CONTRÔLE : ONGLETS & RECHERCHE -->
    <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <!-- Onglets d'état -->
            <ul class="nav nav-pills" id="disbursementTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 py-2 fs-7 fw-bold" id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending-content" type="button" role="tab">
                        <i class="fas fa-hand-holding-dollar me-1.5"></i> En Attente
                        <span class="badge bg-white text-emerald-700 rounded-pill ms-1.5 px-2">{{ $totalPendingCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2 fs-7 fw-bold" id="history-tab" data-bs-toggle="pill" data-bs-target="#history-content" type="button" role="tab">
                        <i class="fas fa-clock-rotate-left me-1.5"></i> Décaissés Aujourd'hui
                        <span class="badge bg-secondary-subtle text-dark rounded-pill ms-1.5 px-2">{{ $todayDisbursedCount }}</span>
                    </button>
                </li>
            </ul>

            <!-- Barre de Recherche Réactive -->
            <div class="position-relative w-100 w-md-auto" style="min-width: 340px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="loanSearchInput" class="search-disbursement-input w-100" placeholder="Rechercher emprunteur, N° prêt, téléphone...">
            </div>
        </div>
    </div>

    <!-- CONTENU DES ONGLETS -->
    <div class="tab-content" id="disbursementTabsContent">

        <!-- ================= ONGLET 1 : EN ATTENTE DE DÉCAISSEMENT ================= -->
        <div class="tab-pane fade show active" id="pending-content" role="tabpanel">
            @if($loans->isEmpty())
                <div class="bg-white rounded-4 p-5 text-center shadow-sm border border-slate-200 my-4">
                    <div class="w-16 h-16 rounded-circle bg-emerald-50 text-emerald-600 d-inline-flex align-items-center justify-center mb-3">
                        <i class="fas fa-circle-check fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Aucun prêt en attente de décaissement</h5>
                    <p class="text-muted small mb-0 max-w-md mx-auto">
                        Tous les crédits approuvés ont été honorés pour cette session. Les nouveaux prêts validés par la direction apparaîtront automatiquement ici.
                    </p>
                </div>
            @else
                <div class="bg-white rounded-4 shadow-sm border border-slate-200 overflow-hidden mb-4">
                    <div class="px-4 py-3.5 border-bottom border-slate-100 d-flex flex-wrap justify-content-between align-items-center gap-3 bg-slate-50/60">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="w-8 h-8 rounded-3 bg-emerald-100 text-emerald-700 d-flex align-items-center justify-center fs-7">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0 fs-7">Bordereau des Prêts Autorisés au Guichet</h6>
                                <p class="fs-9 text-muted mb-0">Dossiers approuvés par la direction en attente de remise physique d'espèces</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-dark border border-slate-200 rounded-pill px-3 py-1.5 fs-8 fw-bold shadow-xs">
                                <i class="fas fa-list-check text-emerald-600 me-1.5"></i>{{ $totalPendingCount }} dossier(s)
                            </span>
                            <span class="badge bg-emerald-600 text-white rounded-pill px-3 py-1.5 fs-8 fw-black tracking-wide shadow-xs">
                                <i class="fas fa-vault me-1.5"></i>Total : {{ number_format($totalPendingAmount, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0 disbursement-table" id="pendingLoansTable">
                            <thead class="bg-slate-50 text-slate-500 fs-9 text-uppercase tracking-wider fw-bold border-bottom border-slate-200">
                                <tr>
                                    <th class="ps-4 py-3">Réf & Date</th>
                                    <th class="py-3">Emprunteur (Bénéficiaire)</th>
                                    <th class="py-3">Montant Accordé</th>
                                    <th class="py-3">Modalités Crédit</th>
                                    <th class="py-3">Contrôles & Cautions</th>
                                    <th class="py-3">Objet du Financement</th>
                                    <th class="py-3 text-end pe-4">Action Guichet</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 fs-7">
                                @foreach($loans as $loan)
                                @php
                                    $isCashSufficient = $currentCash >= $loan->approved_amount;
                                    $initials = strtoupper(substr($loan->client->first_name ?? 'C', 0, 1) . substr($loan->client->last_name ?? 'L', 0, 1));
                                    $vProg = $loan->getVerificationsProgress();
                                @endphp
                                <tr class="loan-item-card table-row-hover" 
                                    data-search="{{ strtolower($loan->client->full_name . ' ' . $loan->loan_number . ' ' . ($loan->client->phone ?? '') . ' ' . ($loan->client->client_number ?? '')) }}">
                                    
                                    <!-- 1. RÉFÉRENCE & DATE -->
                                    <td class="ps-4">
                                        <div class="d-flex flex-column">
                                            <span class="badge bg-slate-100 text-dark border border-slate-200 font-monospace px-2.5 py-1 rounded-2 fs-8 fw-black w-fit mb-1">
                                                <i class="fas fa-file-invoice text-emerald-600 me-1"></i>{{ $loan->loan_number }}
                                            </span>
                                            <span class="fs-9 text-muted">
                                                <i class="far fa-calendar-check text-slate-400 me-1"></i>{{ $loan->approved_at ? $loan->approved_at->format('d/m/Y') : 'Récemment' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 2. EMPRUNTEUR -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="table-client-avatar flex-shrink-0">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('caissier.clients.show', $loan->client_id) }}" 
                                                   class="fw-black text-dark text-decoration-none fs-7 hover:underline d-block text-truncate" 
                                                   title="{{ $loan->client->full_name }}">
                                                    {{ $loan->client->full_name }}
                                                </a>
                                                <div class="d-flex align-items-center gap-2 fs-9 text-muted">
                                                    <span class="font-monospace fw-semibold text-slate-600">{{ $loan->client->client_number ?? '—' }}</span>
                                                    <span>•</span>
                                                    <a href="tel:{{ $loan->client->phone }}" class="text-slate-600 text-decoration-none hover:underline">
                                                        <i class="fas fa-phone-alt text-emerald-600 me-0.5"></i>{{ $loan->client->phone ?? '—' }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 3. MONTANT ACCORDÉ -->
                                    <td>
                                        <div class="fs-4 fw-black text-emerald-700 font-monospace lh-1 mb-1.5">
                                            {{ number_format($loan->approved_amount, 0, ',', ' ') }}
                                            <small class="fs-7 fw-bold text-slate-600">FCFA</small>
                                        </div>
                                        @if($isCashSufficient)
                                            <span class="badge badge-soft-emerald rounded-pill px-2.5 py-1 fs-8 fw-bold shadow-xs d-inline-flex align-items-center">
                                                <i class="fas fa-circle-check text-emerald-600 me-1.5 fs-7"></i>Espèces Disponibles
                                            </span>
                                        @else
                                            <span class="badge badge-soft-rose rounded-pill px-2.5 py-1 fs-8 fw-bold shadow-xs d-inline-flex align-items-center">
                                                <i class="fas fa-triangle-exclamation text-rose-600 me-1.5 fs-7"></i>Provision Insuffisante
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 4. MODALITÉS CRÉDIT -->
                                    <td>
                                        <div class="fs-8 fw-bold text-slate-800 mb-0.5">
                                            <i class="far fa-clock text-slate-400 me-1"></i>{{ $loan->duration_months ?? 1 }} Mois
                                            <span class="text-muted fw-normal fs-9">({{ number_format($loan->interest_rate ?? 17.0, 1) }}%/an)</span>
                                        </div>
                                        <div class="fs-8 text-emerald-700 font-monospace fw-bold">
                                            <i class="fas fa-coins text-emerald-600 me-1"></i>{{ number_format($loan->monthly_payment ?? ($loan->approved_amount / max(1, $loan->duration_months ?? 1)), 0, ',', ' ') }} <small class="fs-9">F/mois</small>
                                        </div>
                                    </td>

                                    <!-- 5. AUDIT & CAUTIONS -->
                                    <td>
                                        <div class="d-flex flex-column gap-1.5">
                                            <a href="{{ route('caissier.loans.show', $loan->id) }}" 
                                               class="badge {{ ($vProg['is_complete'] ?? false) ? 'badge-soft-emerald' : 'badge-soft-amber' }} rounded-pill px-2.5 py-1 fs-8 fw-bold text-decoration-none w-fit shadow-xs d-inline-flex align-items-center" 
                                               title="Vérifications d'audit">
                                                <i class="fas fa-clipboard-check text-emerald-600 me-1.5"></i>Audit : {{ $vProg['checked'] ?? 0 }}/{{ $vProg['total'] ?? 9 }} Validé
                                            </a>
                                            <span class="badge badge-soft-purple rounded-pill px-2.5 py-1 fs-8 fw-bold w-fit shadow-xs d-inline-flex align-items-center">
                                                <i class="fas fa-shield-halved text-purple-600 me-1.5"></i>{{ $loan->guarantors->count() }} Caution(s) Solidaire(s)
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 6. OBJET DU FINANCEMENT -->
                                    <td>
                                        <div class="fs-8 text-slate-700 max-w-xs" style="max-width: 200px;">
                                            @if($loan->purpose)
                                                <span class="d-inline-block text-truncate w-100" title="{{ $loan->purpose }}">
                                                    <i class="fas fa-briefcase text-slate-400 me-1"></i>{{ $loan->purpose }}
                                                </span>
                                            @else
                                                <span class="text-muted fst-italic fs-9">Non renseigné</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 7. ACTION GUICHET -->
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex align-items-center gap-1.5">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success fw-bold px-3 py-2 rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 text-nowrap"
                                                    onclick="openDisbursementModal('{{ addslashes($loan->client->full_name) }}', '{{ number_format($loan->approved_amount, 0, ',', ' ') }}', '{{ $loan->loan_number }}', '{{ route('caissier.loans.disburse', $loan->id) }}', {{ $isCashSufficient ? 'true' : 'false' }})">
                                                <i class="fas fa-hand-holding-dollar fs-8"></i>
                                                <span>Décaisser</span>
                                            </button>
                                            <a href="{{ route('caissier.loans.show', $loan->id) }}" 
                                               class="btn btn-sm btn-outline-secondary rounded-3 px-2.5 py-2 text-muted hover:text-dark hover:bg-slate-100" 
                                               title="Consulter le contrat & dossier">
                                                <i class="fas fa-file-contract"></i>
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- ================= ONGLET 2 : DÉCAISSÉS AUJOURD'HUI ================= -->
        <div class="tab-pane fade" id="history-content" role="tabpanel">
            <div class="bg-white rounded-4 shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Registre des Décaissements de la Journée</h6>
                        <p class="fs-8 text-muted mb-0">Historique des remises physiques en espèces enregistrées au guichet</p>
                    </div>
                    <span class="badge bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-pill fs-8 fw-bold">
                        {{ $todayDisbursedCount }} dossier(s) honoré(s)
                    </span>
                </div>

                @if($todayDisbursedLoans->isEmpty())
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-inbox fs-2 mb-2 text-slate-300"></i>
                        <p class="fs-7 mb-0">Aucun décaissement de prêt n'a encore été effectué aujourd'hui.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-slate-50 text-muted fs-8 text-uppercase">
                                <tr>
                                    <th class="ps-4">Heure</th>
                                    <th>Emprunteur</th>
                                    <th>N° Prêt</th>
                                    <th>Montant Décaissé</th>
                                    <th>Statut Guichet</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 fs-7">
                                @foreach($todayDisbursedLoans as $disbursedLoan)
                                <tr>
                                    <td class="ps-4 font-monospace text-muted">
                                        {{ $disbursedLoan->disbursed_at ? $disbursedLoan->disbursed_at->format('H:i:s') : '—' }}
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $disbursedLoan->client->full_name }}</div>
                                        <small class="text-muted">{{ $disbursedLoan->client->phone }}</small>
                                    </td>
                                    <td>
                                        <span class="font-monospace fw-bold text-dark">{{ $disbursedLoan->loan_number }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-black text-emerald-600 font-monospace">
                                            {{ number_format($disbursedLoan->approved_amount, 0, ',', ' ') }} FCFA
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-pill px-2.5 py-1 fs-8">
                                            <i class="fas fa-check-circle me-1"></i> Remis en Espèces
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fs-8">
                                            <i class="fas fa-receipt me-1"></i> Journal Caisse
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>

<!-- MODAL INSTITUTIONNEL DE CONFIRMATION DE DÉCAISSEMENT -->
<div class="modal fade" id="disbursementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4 overflow-hidden">
            <!-- Entête Modal -->
            <div class="p-4 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="w-10 h-10 rounded-circle bg-white/20 d-flex align-items-center justify-center">
                        <i class="fas fa-hand-holding-dollar fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-black mb-0">Confirmation de Décaissement</h5>
                        <p class="fs-8 text-white/80 mb-0">Guichet & Opérations de Crédit</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps Modal -->
            <div class="modal-body p-4">
                
                <div class="text-center mb-4">
                    <span class="badge bg-slate-100 text-slate-600 px-3 py-1 rounded-pill fs-8 font-monospace mb-2" id="modalLoanNumber"></span>
                    <h5 class="fw-black text-dark mb-1" id="modalClientName"></h5>
                    <p class="text-muted fs-8 mb-0">Veuillez vérifier l'identité de l'emprunteur avant la remise des espèces.</p>
                </div>

                <!-- Montant à décaisser -->
                <div class="p-4 rounded-3 text-center mb-4 border border-emerald-200" style="background: #f0fdf4;">
                    <p class="text-emerald-800 fs-8 text-uppercase fw-bold mb-1">Montant à Remettre en Espèces</p>
                    <h2 class="fw-black text-emerald-700 mb-0 font-monospace">
                        <span id="modalAmount"></span> <small class="fs-5">FCFA</small>
                    </h2>
                </div>

                <!-- Avertissement Trésorerie -->
                <div id="modalCashWarning" class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis fs-8 rounded-3 d-none mb-4">
                    <i class="fas fa-triangle-exclamation me-1.5"></i>
                    <strong>Attention :</strong> Le solde disponible en caisse est inférieur au montant à décaisser. Procédez à un transfert depuis le coffre-fort si nécessaire.
                </div>

                <!-- Checklist Guichetier -->
                <div class="bg-slate-50 p-3 rounded-3 border border-slate-100 mb-4">
                    <h6 class="fs-8 fw-bold text-uppercase text-slate-500 mb-2">Protocole de Sécurité Guichet :</h6>
                    <div class="form-check fs-8 text-dark mb-1">
                        <input class="form-check-input" type="checkbox" id="checkId" checked disabled>
                        <label class="form-check-label fw-semibold" for="checkId">Pièce d'identité (CNI / Passeport) vérifiée</label>
                    </div>
                    <div class="form-check fs-8 text-dark mb-1">
                        <input class="form-check-input" type="checkbox" id="checkCount" checked disabled>
                        <label class="form-check-label fw-semibold" for="checkCount">Comptage physique des billets effectué</label>
                    </div>
                    <div class="form-check fs-8 text-dark">
                        <input class="form-check-input" type="checkbox" id="checkSign" checked disabled>
                        <label class="form-check-label fw-semibold" for="checkSign">Émission automatique du reçu de décharge</label>
                    </div>
                </div>

                <!-- Formulaire de soumission -->
                <form id="modalDisbursementForm" method="POST">
                    @csrf
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success fw-bold py-3 rounded-3 shadow-sm">
                            <i class="fas fa-check-circle me-1.5"></i> Confirmer la Remise & Décaisser
                        </button>
                        <button type="button" class="btn btn-outline-secondary py-2.5 rounded-3 fw-semibold fs-7" data-bs-dismiss="modal">
                            Annuler
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openDisbursementModal(clientName, amount, loanNumber, actionUrl, isCashSufficient) {
        document.getElementById('modalClientName').innerText = clientName;
        document.getElementById('modalAmount').innerText = amount;
        document.getElementById('modalLoanNumber').innerText = 'Dossier ' + loanNumber;
        document.getElementById('modalDisbursementForm').action = actionUrl;
        
        const warning = document.getElementById('modalCashWarning');
        if (!isCashSufficient) {
            warning.classList.remove('d-none');
        } else {
            warning.classList.add('d-none');
        }

        let myModal = new bootstrap.Modal(document.getElementById('disbursementModal'));
        myModal.show();
    }

    // Filtrage instantané
    document.getElementById('loanSearchInput')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.loan-item-card');
        
        cards.forEach(card => {
            const text = card.getAttribute('data-search') || '';
            if (text.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
@endpush
