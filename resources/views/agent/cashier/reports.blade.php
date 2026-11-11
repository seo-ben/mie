@extends('layouts.cashier')

@section('title', 'Rapports Financiers de Caisse')
@section('page-title', 'Rapports & Audit Financier')
@section('page-subtitle', 'Synthèse analytique et comptable des flux de caisse')

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Synthèse & Rapports Financiers</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Rapports Financiers</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-dark border px-3 py-2 rounded-3 fs-8 fw-semibold">
                Période : {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &rarr; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </span>
        </div>
    </div>

    <!-- CARTE DE FILTRE PAR DATE ET PÉRIODE -->
    <div class="white-card p-4 mb-4">
        <form action="{{ route('caissier.reports') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fw-bold text-dark fs-8 mb-1">Date de Début</label>
                <input type="date" name="date_start" value="{{ $startDate }}" class="form-control rounded-3 border" style="padding: 9px 12px; font-size: 0.88rem;">
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-bold text-dark fs-8 mb-1">Date de Fin</label>
                <input type="date" name="date_end" value="{{ $endDate }}" class="form-control rounded-3 border" style="padding: 9px 12px; font-size: 0.88rem;">
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-success fw-bold flex-grow-1 py-2 rounded-3 fs-7">
                    <i class="fas fa-filter me-1"></i> Filtrer les Flux
                </button>
                <a href="{{ route('caissier.reports') }}?date_start={{ now()->startOfMonth()->toDateString() }}&date_end={{ now()->toDateString() }}" class="btn btn-light border fw-bold py-2 px-3 rounded-3 fs-7 text-dark">
                    Ce Mois
                </a>
            </div>
        </form>
    </div>

    <!-- 4 KPIS PASTELS DE SYNTHÈSE DES FLUX -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-arrow-down-long"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-plus"></i> Inflow</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700 fs-4">{{ number_format($totals->total_in ?? 0, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Total Entrées (Inflow)</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-rose">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-arrow-up-long"></i>
                    </div>
                    <span class="kpi-trend text-danger"><i class="fas fa-minus"></i> Outflow</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-rose-700 fs-4">{{ number_format($totals->total_out ?? 0, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Total Sorties (Outflow)</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            @php
                $net = ($totals->total_in ?? 0) - ($totals->total_out ?? 0);
            @endphp
            <div class="kpi-pastel-card {{ $net >= 0 ? 'kpi-pastel-blue' : 'kpi-pastel-amber' }}">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-scale-balanced"></i>
                    </div>
                    <span class="kpi-trend text-{{ $net >= 0 ? 'primary' : 'warning' }}"><i class="fas fa-chart-line"></i> Bilan</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-{{ $net >= 0 ? 'primary' : 'warning' }} fs-4">{{ number_format($net, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Flux Net de Période</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-check"></i> Audit</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700">{{ $totals->total_count ?? 0 }}</div>
                    <div class="kpi-label">Transactions Enregistrées</div>
                </div>
            </div>
        </div>
    </div>

    <!-- DOUBLE PANNEAUX : RÉPARTITION CANAUX & CONFORMITÉ -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="white-card h-100">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-wallet text-primary"></i> Répartition par Canal de Paiement
                </h6>
                <div class="space-y-3">
                    @forelse($byMethod as $m)
                        @php
                            $totalAll = ($totals->total_in ?? 1) + ($totals->total_out ?? 0);
                            $pctMethod = min(100, round(($m->sum_amount / max(1, $totalAll)) * 100));
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1 fs-8">
                                <span class="fw-bold text-dark text-capitalize">
                                    {{ str_replace('_', ' ', $m->payment_method) }}
                                </span>
                                <span class="fw-bold text-dark">{{ number_format($m->sum_amount, 0, ',', ' ') }} FCFA <small class="text-muted">({{ $m->count }})</small></span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 4px;">
                                <div class="progress-bar bg-success" style="width: {{ $pctMethod }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted fs-8 mb-0">Aucun mouvement enregistré pour cette période.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="white-card h-100">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-shield-halved text-success"></i> Contrôle de Conformité Guichet
                </h6>
                <div class="space-y-2 fs-8 text-muted">
                    <div class="p-2 rounded-2 bg-light d-flex align-items-start gap-2 mb-2">
                        <i class="fas fa-check-circle text-success mt-1"></i>
                        <span>Toutes les écritures sont liées à la session de caisse active pour assurer l'imputabilité.</span>
                    </div>
                    <div class="p-2 rounded-2 bg-light d-flex align-items-start gap-2 mb-2">
                        <i class="fas fa-check-circle text-success mt-1"></i>
                        <span>Les commissions de retrait et frais de carnet tontine sont automatiquement séparés.</span>
                    </div>
                    <div class="p-2 rounded-2 bg-light d-flex align-items-start gap-2">
                        <i class="fas fa-check-circle text-success mt-1"></i>
                        <span>En cas d'erreur matérielle, l'extourne est soumise à la double validation avec code PIN superviseur.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JOURNAL COMPTABLE DÉTAILLÉ -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-primary"></i>
                Journal Comptable de la Période
            </h6>
            <span class="badge bg-light text-dark border px-2 py-1 rounded-2 fs-8">{{ $transactions->total() }} écriture(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Référence</th>
                        <th>Bénéficiaire / Compte</th>
                        <th>Nature</th>
                        <th>Mode</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Reçu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                        @php
                            $isInflow = in_array($t->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment', 'transfer_in']);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-dark fs-8">{{ $t->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted fs-8 font-monospace">{{ $t->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace fs-8">{{ $t->transaction_reference }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-7">{{ $t->account->client->full_name ?? 'Opération de Caisse' }}</div>
                                <small class="text-muted fs-8 font-monospace">{{ $t->account->account_number ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $isInflow ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} fs-8 fw-bold">
                                    {{ ucfirst(str_replace('_', ' ', $t->transaction_type)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border fs-8 text-capitalize">{{ str_replace('_', ' ', $t->payment_method) }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold fs-7 {{ $isInflow ? 'text-success' : 'text-danger' }}">
                                    {{ $isInflow ? '+' : '-' }} {{ number_format($t->amount, 0, ',', ' ') }} FCFA
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('caissier.receipt.print', $t->id) }}" target="_blank" class="btn btn-sm btn-light border text-dark" title="Imprimer le Reçu">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold text-dark mb-1">Aucune transaction trouvée sur cette période</h6>
                                    <p class="fs-8 text-muted mb-0">Sélectionnez une autre plage de dates pour consulter les écritures.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-3 border-top bg-light d-flex justify-content-end">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
