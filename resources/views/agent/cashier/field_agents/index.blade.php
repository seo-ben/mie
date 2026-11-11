@extends('layouts.cashier')

@section('title', 'Supervision des Agents de Terrain')
@section('page-title', 'Collectes & Agents de Terrain')
@section('page-subtitle', 'Supervision des tournées et versements du ' . $date->translatedFormat('d F Y'))

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & BREADCRUMBS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Supervision Terrain</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Agents de Collecte</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-dark border px-3 py-2 rounded-3 fs-8 fw-bold">
                <i class="far fa-calendar-alt text-muted me-1"></i> {{ $date->translatedFormat('d F Y') }}
            </span>
            <a href="{{ route('caissier.dashboard') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2">
                <i class="fas fa-arrow-left me-1"></i> Retour Guichet
            </a>
        </div>
    </div>

    <!-- 4 KPIS PASTELS DE SYNTHÈSE COLLECTES -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-blue">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-users-viewfinder"></i>
                    </div>
                    <span class="kpi-trend text-primary"><i class="fas fa-motorcycle"></i> Tournées</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $summary->total_agents }}</div>
                    <div class="kpi-label">{{ $summary->active_today }} actifs en mission</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-green">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                    <span class="kpi-trend text-success"><i class="fas fa-arrow-trend-up"></i> Flux Agence</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-emerald-700 fs-4">{{ number_format($summary->total_collected, 0, ',', ' ') }} <small class="fs-8">F</small></div>
                    <div class="kpi-label">Collecté Aujourd'hui</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-teal">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="kpi-trend text-teal-700"><i class="fas fa-check"></i> Encaissés</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-teal-700">{{ $summary->total_operations }}</div>
                    <div class="kpi-label">Reçus Délivrés sur le Terrain</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-pastel-card kpi-pastel-amber">
                <div class="kpi-header">
                    <div class="kpi-icon-wrapper">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <span class="kpi-trend text-warning"><i class="fas fa-lock"></i> Sécurité</span>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value text-amber-700 fs-4">500 000 <small class="fs-8">F</small></div>
                    <div class="kpi-label">Plafond Encaissé Max / Agent</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLEAU CORPORATE DES AGENTS DE TERRAIN -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white">
            <div>
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="fas fa-person-walking-luggage text-primary"></i>
                    Équipes Terrain & Suivi des Encaissements
                </h6>
                <small class="text-muted fs-8">Pointage des versements et vérification des seuils de trésorerie</small>
            </div>

            <form action="{{ route('caissier.field-agents.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <div class="search-box-wrapper" style="width: 260px;">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="topbar-search-input" placeholder="Rechercher un agent...">
                </div>
                @if(request('search'))
                    <a href="{{ route('caissier.field-agents.index') }}" class="btn btn-sm btn-light border" title="Réinitialiser"><i class="fas fa-xmark"></i></a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>Agent de Terrain</th>
                        <th>Téléphone</th>
                        <th>Clients Portefeuille</th>
                        <th>Collecte du Jour</th>
                        <th>Niveau Encaisse / Plafond</th>
                        <th>Statut Tournée</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $item)
                        @php
                            $pct = min(100, round(($item->today_collected / max(1, $item->cash_limit)) * 100));
                            $isOver = $item->today_collected >= $item->cash_limit;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 38px; height: 38px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 0.85rem;">
                                        {{ strtoupper(substr($item->agent->first_name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-7">{{ $item->agent->full_name }}</div>
                                        <small class="text-muted fs-8">{{ $item->agent->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-dark fs-8 font-monospace"><i class="fas fa-phone-alt me-1 text-muted"></i>{{ $item->agent->phone ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-2 fs-8 fw-semibold">
                                    <i class="fas fa-user-group me-1 text-primary"></i> {{ $item->clients_count }} clients
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-success fs-7">
                                    {{ number_format($item->today_collected, 0, ',', ' ') }} FCFA
                                </div>
                                <small class="text-muted fs-8">{{ $item->today_ops_count ?? $item->today_count ?? 0 }} opération(s)</small>
                            </td>
                            <td style="min-width: 180px;">
                                <div class="d-flex justify-content-between align-items-center fs-8 mb-1">
                                    <span class="fw-bold {{ $isOver ? 'text-danger' : 'text-dark' }}">{{ $pct }}%</span>
                                    <span class="text-muted">Plafond : {{ number_format($item->cash_limit, 0, ',', ' ') }} F</span>
                                </div>
                                <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 4px;">
                                    <div class="progress-bar {{ $isOver ? 'bg-danger' : ($pct > 75 ? 'bg-warning' : 'bg-success') }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </td>
                            <td>
                                @if($item->today_collected > 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8">
                                        <i class="fas fa-motorcycle me-1"></i> En Tournée
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border px-2 py-1 rounded-pill fs-8">
                                        <i class="fas fa-pause me-1"></i> Inactif ce jour
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('caissier.field-agents.show', $item->agent->id) }}" class="btn btn-sm btn-light border fw-semibold rounded-2 px-3 fs-8 text-dark">
                                    Détails &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="fw-bold text-dark mb-1">Aucun agent de terrain rattaché à cette agence.</p>
                                    <small class="text-muted">Les agents créés et affiliés à l'agence apparaîtront automatiquement ici.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
