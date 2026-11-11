@extends('layouts.cashier')

@section('title', 'Détails du Compte ' . $account->account_number)
@section('page-title', 'Détails du Compte')
@section('page-subtitle', $account->account_number . ' • Titulaire : ' . ($account->client->full_name ?? 'Client'))

@push('styles')
<style>
.tontine-day-box {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    position: relative;
    transition: all 0.2s ease;
    cursor: pointer;
    user-select: none;
}
.tontine-day-box.paid {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: 1px solid #047857;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);
}
.tontine-day-box.paid .day-icon {
    font-size: 0.55rem;
    line-height: 1;
    margin-top: 2px;
}
.tontine-day-box.unpaid {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
}
.tontine-day-box.commission {
    background: #faf5ff;
    color: #9333ea;
    border: 1px dashed #c084fc;
}
.tontine-day-box.commission .day-icon-subtle {
    font-size: 0.55rem;
    opacity: 0.8;
    line-height: 1;
    margin-top: 2px;
}
.tontine-day-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
}
</style>
@endpush

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Compte {{ $account->account_number }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.accounts.index') }}" class="text-decoration-none text-muted">Comptes</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">{{ $account->account_number }}</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($account->status == 'active')
                <a href="{{ route('caissier.accounts.deposit.form', $account->id) }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                    <i class="fas fa-arrow-down me-1"></i> Faire un Dépôt au Guichet
                </a>
            @endif
            <a href="{{ route('caissier.clients.show', $account->client_id) }}" class="btn btn-light border fw-bold px-3 py-2 rounded-3 fs-7 text-dark">
                <i class="fas fa-user me-1 text-primary"></i> Dossier Adhérent
            </a>
            <a href="{{ route('caissier.accounts.index') }}" class="btn btn-light border fw-bold px-3 py-2 rounded-3 fs-7 text-muted">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>

    <!-- 4 KPIS PASTELS DU COMPTE -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-vault"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-check-circle"></i> Disponible</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700 fs-4">{{ number_format($account->balance, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Solde Actuel Disponible</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-money-bill-transfer"></i>
                    </div>
                    <span class="kpi-trend text-primary"><i class="fas fa-arrow-down"></i> Entrées</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-primary fs-4">{{ number_format($stats['total_deposits'], 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Total Cotisé / Déposé</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-amber">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <span class="kpi-trend text-warning">
                        @if($account->tontineAccount)
                            {{ ucfirst($account->tontineAccount->payment_frequency) }}
                        @else
                            Épargne Libre
                        @endif
                    </span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-amber-700 fs-4">
                        @if($account->tontineAccount)
                            {{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} <small class="fs-8">F</small>
                        @else
                            Standard
                        @endif
                    </div>
                    <div class="kpi-label">Mise / Type de Produit</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-receipt"></i> Historique</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700">{{ $stats['transaction_count'] }}</div>
                    <div class="kpi-label">Opérations Enregistrées</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CORPS DE PAGE : IDENTITÉ COMPTE & TRANSACTIONS -->
    <div class="row g-4">
        <!-- FICHE D'IDENTITÉ DU COMPTE (COLONNE GAUCHE) -->
        <div class="col-12 col-xl-4">
            <div class="white-card h-100 p-4">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.2rem;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0 fs-6">{{ $account->account_number }}</h5>
                        <span class="badge bg-light text-dark border font-monospace fs-8">{{ strtoupper($account->account_type) }}</span>
                    </div>
                </div>

                <div class="space-y-2 fs-8 text-muted">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span><i class="fas fa-user text-muted me-2"></i>Titulaire</span>
                        <a href="{{ route('caissier.clients.show', $account->client_id) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $account->client->full_name }}
                        </a>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span><i class="fas fa-phone text-muted me-2"></i>Téléphone</span>
                        <strong class="text-dark font-monospace">{{ $account->client->phone }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span><i class="fas fa-calendar-alt text-muted me-2"></i>Date d'Ouverture</span>
                        <strong class="text-dark">{{ $account->created_at->format('d/m/Y') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><i class="fas fa-shield-halved text-muted me-2"></i>Statut Compte</span>
                        @if($account->status == 'active')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold">
                                <i class="fas fa-circle-check me-1"></i> Actif
                            </span>
                        @elseif($account->status == 'suspended')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fw-bold">
                                <i class="fas fa-pause me-1"></i> Suspendu
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-muted border px-2 py-1 rounded-pill">{{ $account->status }}</span>
                        @endif
                    </div>

                    @if($account->tontineAccount)
                        @php
                            $t = $account->tontineAccount;
                            $pctTontine = $t->total_expected > 0 ? min(100, round(($t->total_paid / $t->total_expected) * 100)) : 0;
                        @endphp
                        <div class="pt-3">
                            <label class="form-label fw-bold text-dark fs-8 d-block mb-1">Progression de la Tontine</label>
                            <div class="d-flex justify-content-between fs-8 mb-1">
                                <span class="text-muted">{{ number_format($t->total_paid, 0, ',', ' ') }} F payés</span>
                                <span class="fw-bold text-dark">{{ $pctTontine }}%</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 4px;">
                                <div class="progress-bar bg-success" style="width: {{ $pctTontine }}%;"></div>
                            </div>
                            <small class="text-muted fs-8 d-block mt-1">Objectif cycle : {{ number_format($t->total_expected, 0, ',', ' ') }} FCFA</small>
                        </div>
                    @endif
                </div>

                @if($account->status == 'active')
                    <div class="mt-4 pt-3 border-top">
                        <a href="{{ route('caissier.accounts.deposit.form', $account->id) }}" class="btn btn-success fw-bold w-100 py-2 rounded-3 fs-7 shadow-sm">
                            <i class="fas fa-arrow-down me-1"></i> Encaisser un Dépôt
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- JOURNAL DES TRANSACTIONS RÉCENTES & CALENDRIER TONTINE (COLONNE DROITE) -->
        <div class="col-12 col-xl-8">

            @if($account->tontineAccount)
                @php
                    $tontine = $account->tontineAccount;
                    $activeCycle = $tontine->activeCycle ?? $tontine->cycles()->first();
                    $dailyMise = (float) $tontine->tontine_amount;
                    $totalSlots = 31; // Règle standard 31 jours / cycle mensuel
                    $cycleCollected = $activeCycle ? (float) $activeCycle->collected_amount : (float) $tontine->total_paid;
                    $filledSlots = $dailyMise > 0 ? min($totalSlots, (int) floor($cycleCollected / $dailyMise)) : 0;
                    $remainingSlots = max(0, $totalSlots - $filledSlots);
                    $remainingAmount = $remainingSlots * $dailyMise;
                    $commissionAmount = $dailyMise; // 1 mise retenue au titre des frais
                    $totalTarget = $totalSlots * $dailyMise;
                    $clientPayout = max(0, ($totalSlots - 1) * $dailyMise); // 30 mises nettes
                    $progressPct = round(($filledSlots / $totalSlots) * 100, 1);
                @endphp

                <!-- CALENDRIER DE TONTINE 1-31 (GRILLE JOURNALIÈRE & CALCULS) -->
                <div class="white-card p-4 mb-4 shadow-sm border">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2 fs-6">
                                <i class="fas fa-calendar-days text-purple-600"></i>
                                Carnet de Tontine Journalière &bull; Calendrier des 31 Mises
                            </h5>
                            <small class="text-muted">
                                Cycle Actuel {{ $activeCycle->cycle_number ?? 1 }} &bull; Règle du 1/31 (1 mois = 31 jours de cotisation)
                            </small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-purple-subtle text-purple-700 border border-purple-200 px-3 py-2 rounded-pill fw-bold fs-7">
                                <i class="fas fa-check-circle me-1"></i> {{ $filledSlots }} / 31 Mises Réalisées
                            </span>
                        </div>
                    </div>

                    <!-- 4 INDICATEURS DE CALCUL FINANCIER (RÈGLE DU 1/31) -->
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <small class="text-muted d-block fs-8 fw-semibold text-uppercase">Mise du Jour</small>
                                <div class="fw-bold text-dark fs-6">{{ number_format($dailyMise, 0, ',', ' ') }} F</div>
                                <small class="text-muted fs-8">1 jour = 1 mise</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2.5 rounded-3 bg-success-subtle border border-success-subtle text-center">
                                <small class="text-success d-block fs-8 fw-semibold text-uppercase">Cotisé ({{ $filledSlots }} j)</small>
                                <div class="fw-bold text-success fs-6">{{ number_format($filledSlots * $dailyMise, 0, ',', ' ') }} F</div>
                                <small class="text-success fs-8">{{ $progressPct }}% du cycle</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2.5 rounded-3 bg-warning-subtle border border-warning-subtle text-center">
                                <small class="text-warning-emphasis d-block fs-8 fw-semibold text-uppercase">Reste ({{ $remainingSlots }} j)</small>
                                <div class="fw-bold text-warning-emphasis fs-6">{{ number_format($remainingAmount, 0, ',', ' ') }} F</div>
                                <small class="text-muted fs-8">Pour boucler le cycle</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2.5 rounded-3 bg-primary-subtle border border-primary-subtle text-center">
                                <small class="text-primary d-block fs-8 fw-semibold text-uppercase">Net Client (30 j)</small>
                                <div class="fw-bold text-primary fs-6">{{ number_format($clientPayout, 0, ',', ' ') }} F</div>
                                <small class="text-muted fs-8">Frais 1/31 : -{{ number_format($commissionAmount, 0, ',', ' ') }} F</small>
                            </div>
                        </div>
                    </div>

                    <!-- GRILLE VISUELLE DES 31 CASES (1 À 31) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fs-8 fw-bold text-dark text-uppercase">
                                <i class="fas fa-th me-1 text-muted"></i> Cases de Cotisation Journalière (1 - 31)
                            </span>
                            <div class="d-flex align-items-center gap-3 fs-8">
                                <span class="d-flex align-items-center gap-1 text-success">
                                    <span style="width: 10px; height: 10px; border-radius: 2px; background: #10b981; display: inline-block;"></span> Cotisé
                                </span>
                                <span class="d-flex align-items-center gap-1 text-muted">
                                    <span style="width: 10px; height: 10px; border-radius: 2px; background: #f8fafc; border: 1px solid #cbd5e1; display: inline-block;"></span> En attente
                                </span>
                                <span class="d-flex align-items-center gap-1 text-purple-700">
                                    <span style="width: 10px; height: 10px; border-radius: 2px; background: #faf5ff; border: 1px dashed #a855f7; display: inline-block;"></span> Case 31 (Frais 1/31)
                                </span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-start">
                            @for($day = 1; $day <= 31; $day++)
                                @php
                                    $isPaid = $day <= $filledSlots;
                                    $isCommissionSlot = ($day === 31);
                                @endphp
                                <div class="tontine-day-box {{ $isPaid ? 'paid' : ($isCommissionSlot ? 'commission' : 'unpaid') }}"
                                     data-bs-toggle="tooltip"
                                     title="Jour {{ $day }} : {{ $isPaid ? 'Cotisé (' . number_format($dailyMise, 0, ',', ' ') . ' FCFA)' : ($isCommissionSlot ? '31e mise = Frais de Gestion Microfinance (1/31)' : 'En attente (' . number_format($dailyMise, 0, ',', ' ') . ' FCFA)') }}">
                                    <span class="day-num">{{ $day }}</span>
                                    @if($isPaid)
                                        <i class="fas fa-check day-icon"></i>
                                    @elseif($isCommissionSlot)
                                        <i class="fas fa-hand-holding-dollar day-icon-subtle"></i>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- PROGRESSION DU CYCLE -->
                    <div>
                        <div class="d-flex justify-content-between fs-8 mb-1">
                            <span class="text-muted fw-semibold">Progression Globale du Cycle</span>
                            <span class="fw-bold text-dark">{{ $progressPct }}% ({{ $filledSlots }} / 31 jours complétés)</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px; background: #f1f5f9;">
                            <div class="progress-bar bg-success" style="width: {{ $progressPct }}%;"></div>
                        </div>
                    </div>

                    @if($filledSlots >= 31)
                        <div class="alert alert-success border-0 bg-emerald-50 text-emerald-900 rounded-3 p-3 mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-circle-check fs-4 text-emerald-600"></i>
                                <div>
                                    <strong class="d-block fs-7">Mois de 31 jours complété avec succès !</strong>
                                    <span class="fs-8">Capital net client : <strong>{{ number_format($clientPayout, 0, ',', ' ') }} FCFA</strong> &bull; Frais 1/31 prélevés.</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-white text-emerald-700 border border-emerald-200 px-3 py-2 rounded-pill fs-8 fw-bold">
                                    <i class="fas fa-rotate me-1"></i> Continuité Cycle {{ ($activeCycle->cycle_number ?? 1) + 1 }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="white-card p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-list-check text-emerald-600"></i>
                        Transactions Récentes sur ce Compte
                    </h6>
                    <span class="badge bg-light text-muted border px-2 py-1 fs-8">25 dernières écritures</span>
                </div>

                <div class="table-responsive">
                    <table class="table-corporate mb-0">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Référence</th>
                                <th>Type Opération</th>
                                <th>Montant (FCFA)</th>
                                <th>Statut</th>
                                <th class="text-end">Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($account->transactions ?? [] as $transaction)
                                @php
                                    $isCredit = in_array($transaction->transaction_type ?? $transaction->type, ['deposit', 'savings_deposit', 'tontine_deposit', 'supply']);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-8">{{ $transaction->created_at->format('d/m/Y') }}</div>
                                        <small class="text-muted fs-8">{{ $transaction->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark font-monospace fs-8">{{ $transaction->transaction_reference ?? $transaction->transaction_number }}</span>
                                    </td>
                                    <td>
                                        @if($isCredit)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8">
                                                <i class="fas fa-arrow-down me-1"></i> Dépôt
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fs-8">
                                                <i class="fas fa-arrow-up me-1"></i> Retrait
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold {{ $transaction->status == 'cancelled' ? 'text-decoration-line-through text-muted' : ($isCredit ? 'text-success' : 'text-danger') }} font-numeric fs-7">
                                            {{ $isCredit ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', ' ') }} F
                                        </span>
                                    </td>
                                    <td>
                                        @if($transaction->status == 'completed')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8">
                                                <i class="fas fa-check-circle me-1"></i> Réglé
                                            </span>
                                        @elseif($transaction->status == 'cancelled')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fs-8" title="{{ $transaction->description }}">
                                                <i class="fas fa-ban me-1"></i> Extourné (Annulé)
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border px-2 py-1 rounded-pill fs-8">
                                                {{ $transaction->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($transaction->status !== 'cancelled')
                                            <a href="{{ route('caissier.receipt.print', $transaction->id) }}" target="_blank" class="btn btn-sm btn-light border text-dark" title="Imprimer le Reçu">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        @else
                                            <span class="text-muted fs-8 fst-italic">Annulé</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="py-4">
                                            <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                            <h6 class="fw-bold text-dark mb-1">Aucune transaction enregistrée</h6>
                                            <p class="fs-8 text-muted mb-0">Les dépôts et retraits effectués sur ce compte s'afficheront ici.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
