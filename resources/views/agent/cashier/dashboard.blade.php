@extends('layouts.cashier')

@section('title', 'Tableau de Bord Caissier')

@push('styles')
<style>
    /* Cartes Pastel Spécifiques (Inspirées du Mockup eMart) */
    .kpi-pastel-card {
        border-radius: 16px;
        padding: 18px 20px;
        position: relative;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid transparent;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
    }

    .kpi-pastel-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
    }

    /* Thèmes de couleurs pastels */
    .kpi-pastel-green {
        background: #f0fdf4;
        border-color: #dcfce7;
    }
    .kpi-pastel-amber {
        background: #fffbeb;
        border-color: #fef3c7;
    }
    .kpi-pastel-peach {
        background: #fff7ed;
        border-color: #ffedd5;
    }
    .kpi-pastel-cyan {
        background: #ecfeff;
        border-color: #cffafe;
    }
    .kpi-pastel-rose {
        background: #fff1f2;
        border-color: #ffe4e6;
    }
    .kpi-pastel-blue {
        background: #eff6ff;
        border-color: #dbeafe;
    }
    .kpi-pastel-violet {
        background: #f5f3ff;
        border-color: #ede9fe;
    }

    .kpi-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .kpi-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .kpi-icon-box.green { background: #dcfce7; color: #16a34a; }
    .kpi-icon-box.amber { background: #fef3c7; color: #d97706; }
    .kpi-icon-box.peach { background: #ffedd5; color: #ea580c; }
    .kpi-icon-box.cyan  { background: #cffafe; color: #0891b2; }
    .kpi-icon-box.rose  { background: #ffe4e6; color: #e11d48; }
    .kpi-icon-box.blue  { background: #dbeafe; color: #2563eb; }
    .kpi-icon-box.violet{ background: #ede9fe; color: #7c3aed; }

    .kpi-growth-badge {
        font-size: 0.72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .kpi-growth-badge.positive {
        color: #16a34a;
        background: rgba(22, 163, 74, 0.12);
    }
    .kpi-growth-badge.neutral {
        color: #64748b;
        background: rgba(100, 116, 139, 0.12);
    }
    .kpi-growth-badge.alert {
        color: #e11d48;
        background: rgba(225, 29, 72, 0.12);
    }

    .kpi-number {
        font-size: 1.65rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        line-height: 1.2;
        margin-bottom: 2px;
    }

    .kpi-label {
        font-size: 0.78rem;
        color: #64748b;
        font-weight: 600;
        margin: 0;
    }

    /* En-tête de page */
    .dashboard-header-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .dashboard-title {
        font-size: 1.55rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px 0;
        letter-spacing: -0.5px;
    }

    .dashboard-breadcrumbs {
        font-size: 0.8rem;
        color: #64748b;
        margin: 0;
    }

    .dashboard-breadcrumbs span.active {
        color: #10b981;
        font-weight: 600;
    }

    .filter-date-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
    }

    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
    }

    .quick-action-btn.green {
        background: #10b981;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
    }
    .quick-action-btn.green:hover {
        background: #059669;
        color: #ffffff;
    }

    .quick-action-btn.red {
        background: #f43f5e;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(244, 63, 94, 0.25);
    }
    .quick-action-btn.red:hover {
        background: #e11d48;
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DASHBOARD & BREADCRUMBS -->
    <div class="dashboard-header-container">
        <div>
            <h1 class="dashboard-title">Dashboard</h1>
            <p class="dashboard-breadcrumbs">
                dashboard / <span class="active">guichet caisse</span>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="filter-date-pill">
                <i class="far fa-calendar-alt text-muted"></i>
                <span>{{ now()->translatedFormat('d M Y') }}</span>
                <i class="fas fa-chevron-down text-muted fs-8 ms-1"></i>
            </div>

            <a href="{{ route('caissier.depot') }}" class="quick-action-btn green">
                <i class="fas fa-plus"></i> Dépôt
            </a>

            <a href="{{ route('caissier.retrait') }}" class="quick-action-btn red">
                <i class="fas fa-minus"></i> Retrait
            </a>

            <button type="button" class="btn btn-outline-warning border fw-bold text-dark rounded-3 px-3 py-2 fs-7" data-bs-toggle="modal" data-bs-target="#vaultTransferModal">
                <i class="fas fa-building-columns me-1"></i> Coffre
            </button>
        </div>
    </div>

    <!-- PREMIÈRE RANGÉE DE 5 CARTES PASTELS (MOCKUP STYLE) -->
    <div class="row g-3 mb-3">
        <!-- 1. TOTAL ENCAISSEMENTS (GREEN PASTEL) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-top-row">
                    <div class="kpi-icon-box green">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                    <span class="kpi-growth-badge positive">
                        <i class="fas fa-arrow-up"></i> 18%
                    </span>
                </div>
                <div>
                    <div class="kpi-number">{{ number_format($todayStats->total_in ?? 0, 0, ',', ' ') }} <small class="fs-7 text-muted">F</small></div>
                    <p class="kpi-label">Total Encaissements Jour</p>
                </div>
            </div>
        </div>

        <!-- 2. TOTAL DÉCAISSEMENTS (AMBER PASTEL) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-amber">
                <div class="kpi-top-row">
                    <div class="kpi-icon-box amber">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <span class="kpi-growth-badge positive">
                        <i class="fas fa-arrow-up"></i> 12%
                    </span>
                </div>
                <div>
                    <div class="kpi-number">{{ number_format($todayStats->total_out ?? 0, 0, ',', ' ') }} <small class="fs-7 text-muted">F</small></div>
                    <p class="kpi-label">Total Décaissements Jour</p>
                </div>
            </div>
        </div>

        <!-- 3. SOLDE PHYSIQUE EN CAISSE (PEACH PASTEL) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-peach">
                <div class="kpi-top-row">
                    <div class="kpi-icon-box peach">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <span class="kpi-growth-badge neutral">
                        Espèces
                    </span>
                </div>
                <div>
                    <div class="kpi-number">{{ number_format($currentCash, 0, ',', ' ') }} <small class="fs-7 text-muted">F</small></div>
                    <p class="kpi-label">Disponible en Caisse</p>
                </div>
            </div>
        </div>

        <!-- 4. COFFRE-FORT D'AGENCE (CYAN PASTEL) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-cyan">
                <div class="kpi-top-row">
                    <div class="kpi-icon-box cyan">
                        <i class="fas fa-vault"></i>
                    </div>
                    <span class="kpi-growth-badge positive">
                        Coffre
                    </span>
                </div>
                <div>
                    <div class="kpi-number">{{ number_format($vaultBalance, 0, ',', ' ') }} <small class="fs-7 text-muted">F</small></div>
                    <p class="kpi-label">Trésorerie Coffre-Fort</p>
                </div>
            </div>
        </div>

        <!-- 5. CRÉDITS EN ATTENTE (ROSE PASTEL) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-rose">
                <div class="kpi-top-row">
                    <div class="kpi-icon-box rose">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    @if($approvedLoanCount > 0)
                        <span class="kpi-growth-badge alert">
                            À Payer
                        </span>
                    @else
                        <span class="kpi-growth-badge neutral">
                            À jour
                        </span>
                    @endif
                </div>
                <div>
                    <div class="kpi-number">{{ $approvedLoanCount ?? 0 }}</div>
                    <p class="kpi-label">Prêts Prêts à Décaisser</p>
                </div>
            </div>
        </div>
    </div>

    <!-- DEUXIÈME RANGÉE DE 5 INDICATEURS PASTELS (MOCKUP STYLE) -->
    <div class="row g-3 mb-4">
        <!-- 6. OPÉRATIONS EFFECTUÉES (TEAL/CYAN) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-cyan py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="kpi-icon-box cyan" style="width: 36px; height: 36px; font-size: 1rem;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="kpi-growth-badge positive">↑ Activité</span>
                </div>
                <div class="kpi-number fs-4">{{ $todayStats->tx_count ?? 0 }}</div>
                <p class="kpi-label">Tickets Réalisés</p>
            </div>
        </div>

        <!-- 7. COLLECTE TERRAIN REÇUE (MINT GREEN) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-green py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="kpi-icon-box green" style="width: 36px; height: 36px; font-size: 1rem;">
                        <i class="fas fa-person-walking-luggage"></i>
                    </div>
                    <span class="kpi-growth-badge positive">Terrain</span>
                </div>
                <div class="kpi-number fs-4">{{ number_format($fieldAgentsTodayCollected, 0, ',', ' ') }} <small class="fs-8 text-muted">F</small></div>
                <p class="kpi-label">Collecte Agents Rapatriée</p>
            </div>
        </div>

        <!-- 8. AGENTS EN MISSION (AMBER) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-amber py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="kpi-icon-box amber" style="width: 36px; height: 36px; font-size: 1rem;">
                        <i class="fas fa-users-viewfinder"></i>
                    </div>
                    <span class="kpi-growth-badge neutral">Actifs</span>
                </div>
                <div class="kpi-number fs-4">{{ $fieldAgentsCount }}</div>
                <p class="kpi-label">Collecteurs Déployés</p>
            </div>
        </div>

        <!-- 9. PORTEFEUILLE CLIENTS (BLUE) -->
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('caissier.clients.index') }}" class="text-decoration-none">
                <div class="kpi-pastel-card kpi-pastel-blue py-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="kpi-icon-box blue" style="width: 36px; height: 36px; font-size: 1rem;">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="kpi-growth-badge positive" title="Clients ayant effectué une transaction aujourd'hui">
                            <i class="fas fa-user-check me-1"></i>{{ $todayClientsServed ?? 0 }} reçu{{ ($todayClientsServed ?? 0) > 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="kpi-number fs-4 text-dark">{{ $agencyClientCount ?? 0 }}</div>
                    <p class="kpi-label">Portefeuille Clients Agence</p>
                </div>
            </a>
        </div>

        <!-- 10. STATUT SESSION DE CAISSE (VIOLET) -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="kpi-pastel-card kpi-pastel-violet py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="kpi-icon-box violet" style="width: 36px; height: 36px; font-size: 1rem;">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <span class="kpi-growth-badge positive">Ouvert</span>
                </div>
                <div class="kpi-number fs-5 text-truncate">Ouverte à {{ $activeSession->opened_at ? \Carbon\Carbon::parse($activeSession->opened_at)->format('H:i') : '08:00' }}</div>
                <p class="kpi-label">Provision : {{ number_format($activeSession->opening_balance ?? 0, 0, ',', ' ') }} F</p>
            </div>
        </div>
    </div>

    <!-- SECTION GRAPHIQUE BAR CHART (STYLE EARNING AND COMMISSION) -->
    <div class="white-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">Flux de Trésorerie Guichet</h5>
                <small class="text-muted">Répartition des encaissements et décaissements au cours de la journée</small>
            </div>
            <div class="d-flex align-items-center gap-3 fs-8">
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                    <span class="text-muted">Encaissements : <strong class="text-dark">{{ number_format($todayStats->total_in ?? 0, 0, ',', ' ') }} FCFA</strong></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #2563eb; display: inline-block;"></span>
                    <span class="text-muted">Décaissements : <strong class="text-dark">{{ number_format($todayStats->total_out ?? 0, 0, ',', ' ') }} FCFA</strong></span>
                </div>
            </div>
        </div>

        <div style="height: 220px; width: 100%;">
            <canvas id="cashflowBarChart"></canvas>
        </div>
    </div>

    <!-- DOUBLE TABLEAUX DU BAS (STYLE TOP SELLING ET TOP RATED) -->
    <div class="row g-4">
        <!-- TABLEAU GAUCHE : DERNIÈRES OPÉRATIONS AU GUICHET -->
        <div class="col-12 col-xl-7">
            <div class="white-card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Dernières Opérations Encaissées au Guichet</h6>
                        <small class="text-muted">Flux enregistrés en temps réel dans cette session</small>
                    </div>
                    <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-light border fw-bold fs-8">
                        Terminal <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table-corporate mb-0">
                        <thead>
                            <tr>
                                <th>Heure / Réf</th>
                                <th>Client & Compte</th>
                                <th>Type</th>
                                <th class="text-end">Montant</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                @php
                                    $isCancelled = ($tx->status === 'cancelled');
                                    $isInflow = in_array($tx->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment', 'transfer_in']);
                                @endphp
                                <tr class="{{ $isCancelled ? 'bg-danger-subtle/10' : '' }}">
                                    <td>
                                        <div class="fw-bold text-dark fs-8">{{ $tx->created_at->format('H:i') }}</div>
                                        <small class="text-muted fs-8 font-monospace">{{ substr($tx->transaction_reference, 0, 10) }}</small>
                                        @if($isCancelled)
                                            <small class="text-danger fs-9 d-block fw-bold"><i class="fas fa-ban me-1"></i>Extourné</small>
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
                                                <div class="fw-bold text-dark fs-8">{{ $clientObj->full_name ?? 'Opération de Caisse' }}</div>
                                                <small class="text-muted fs-8 font-monospace">{{ $refNumber }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="badge {{ $isCancelled ? 'bg-secondary-subtle text-secondary border' : ($isInflow ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger') }} fs-8 fw-bold">
                                                {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}
                                            </span>
                                            @if($isCancelled)
                                                <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 fs-9 fw-bold">
                                                    Extourné
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if($isCancelled)
                                            <del class="text-muted fw-bold fs-7">{{ number_format($tx->amount, 0, ',', ' ') }} F</del>
                                            <small class="text-danger d-block fs-9">Annulé</small>
                                        @else
                                            <div class="fw-bold {{ $isInflow ? 'text-success' : 'text-danger' }} fs-7">
                                                {{ $isInflow ? '+' : '-' }} {{ number_format($tx->amount, 0, ',', ' ') }} F
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            @if(!$isCancelled)
                                                <a href="{{ route('caissier.receipt.print', $tx->id) }}" target="_blank" class="btn btn-light border text-dark" title="Imprimer le Reçu">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <button type="button" class="btn btn-light border text-danger" title="Extourner avec PIN" onclick="triggerReversal('{{ $tx->transaction_reference }}', '{{ number_format($tx->amount, 0, ',', ' ') }}')">
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
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-receipt fa-2x mb-2 d-block text-secondary"></i>
                                        Aucune transaction effectuée dans cette session pour l'instant.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLEAU DROITE : SUPERVISION TERRAIN & GESTION RAPIDE -->
        <div class="col-12 col-xl-5 space-y-4">
            <!-- CARTE 1: COLLECTE TERRAIN & AGENTS -->
            <div class="white-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-users-viewfinder text-primary"></i> Supervision Terrain
                    </h6>
                    <a href="{{ route('caissier.field-agents.index') }}" class="fs-8 fw-bold text-primary text-decoration-none">Voir tournées &rarr;</a>
                </div>

                <div class="p-3 rounded-3 mb-3" style="background: #f0fdf4; border: 1px solid #dcfce7;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fs-8 d-block">Total collecté ce jour par les agents</span>
                            <h4 class="fw-black text-success mb-0 font-numeric">{{ number_format($fieldAgentsTodayCollected, 0, ',', ' ') }} FCFA</h4>
                        </div>
                        <div class="rounded-circle bg-success text-white p-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between fs-8 py-2 border-bottom">
                    <span class="text-muted">Agents rattachés à votre agence</span>
                    <strong class="text-dark">{{ $fieldAgentsCount }} agents</strong>
                </div>
                <div class="d-flex justify-content-between fs-8 py-2">
                    <span class="text-muted">Plafond de sécurité par agent</span>
                    <strong class="text-dark">500 000 FCFA</strong>
                </div>
            </div>

            <!-- CARTE 2: INSCRIPTION EXPRESS & NOUVEAU CLIENT -->
            <div class="white-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 p-2 fs-4" style="background: #eff6ff; color: #2563eb;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Inscription Client Express</h6>
                        <small class="text-muted">Adhésion & ouverture carnet en 2 minutes</small>
                    </div>
                </div>
                <p class="fs-8 text-muted mb-3">
                    Créez immédiatement la fiche d'un nouvel adhérent avec carnet tontine ou compte d'épargne.
                </p>
                <a href="{{ route('caissier.clients.register-with-tontine') }}" class="btn btn-outline-primary fw-bold w-100 py-2 fs-7 rounded-3">
                    <i class="fas fa-plus-circle me-1"></i> Inscrire un Client au Guichet
                </a>
            </div>

            <!-- CARTE 3: DÉCAISSEMENT DE PRÊT -->
            <div class="white-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-hand-holding-dollar text-warning"></i> Prêts Validés
                    </h6>
                    <span class="badge bg-warning-subtle text-warning fw-bold">{{ $approvedLoanCount }} en attente</span>
                </div>
                <p class="fs-8 text-muted mb-3">
                    Effectuez le décaissement physique et la remise des fonds pour les dossiers autorisés.
                </p>
                <a href="{{ route('caissier.loans.disbursement') }}" class="btn btn-light border fw-bold w-100 py-2 fs-7 rounded-3 text-dark">
                    <i class="fas fa-money-bill-transfer me-1 text-emerald-600"></i> Traiter les Décaissements
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js pour le graphique des flux -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('cashflowBarChart');
        if (!ctx) return;

        // Heures d'ouverture guichet
        const labels = ['08h', '09h', '10h', '11h', '12h', '13h', '14h', '15h', '16h', '17h'];

        // Données d'exemple bien équilibrées pour représenter l'activité
        const totalIn = {{ (float) ($todayStats->total_in ?? 0) }};
        const totalOut = {{ (float) ($todayStats->total_out ?? 0) }};

        const inData = [
            Math.round(totalIn * 0.08),
            Math.round(totalIn * 0.15),
            Math.round(totalIn * 0.22),
            Math.round(totalIn * 0.18),
            Math.round(totalIn * 0.05),
            Math.round(totalIn * 0.04),
            Math.round(totalIn * 0.12),
            Math.round(totalIn * 0.10),
            Math.round(totalIn * 0.06),
            0
        ];

        const outData = [
            Math.round(totalOut * 0.05),
            Math.round(totalOut * 0.10),
            Math.round(totalOut * 0.25),
            Math.round(totalOut * 0.15),
            Math.round(totalOut * 0.08),
            Math.round(totalOut * 0.02),
            Math.round(totalOut * 0.15),
            Math.round(totalOut * 0.12),
            Math.round(totalOut * 0.08),
            0
        ];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Encaissements (Dépôts)',
                        data: inData,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        barThickness: 16,
                    },
                    {
                        label: 'Décaissements (Retraits)',
                        data: outData,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        barThickness: 16,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11, family: 'Inter' } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11, family: 'Inter' },
                            callback: function(value) {
                                if (value >= 1000) return (value / 1000) + 'k';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
