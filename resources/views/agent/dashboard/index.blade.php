@extends('layouts.agent')

@section('title', 'Tableau de bord Agent')

@section('content')
<div class="px-4 py-4 container-fluid">
    <!-- En-tête avec informations agent -->
    <div class="mb-4 row align-items-center">
        <div class="col-md-6">
            <h1 class="mb-1 h3 fw-bold">Tableau de bord</h1>
            <p class="mb-0 text-muted">
                <i class="fas fa-user-tie me-1"></i>
                {{ $agent['name'] }}
                <span class="mx-2">•</span>
                <i class="fas fa-building me-1"></i>
                {{ $agent['agency'] }}
            </p>
        </div>
        <div class="text-end col-md-6">
            {{-- <div class="shadow-sm btn-group" role="group" aria-label="Filtres de période">
                <button type="button" class="btn btn-outline-primary period-filter {{ $currentPeriod === 'today' ? 'active' : '' }}"
                        data-period="today">
                    <i class="fas fa-calendar-day"></i> Aujourd'hui
                </button>
                <button type="button" class="btn btn-outline-primary period-filter {{ $currentPeriod === 'week' ? 'active' : '' }}"
                        data-period="week">
                    <i class="fas fa-calendar-week"></i> Semaine
                </button>
                <button type="button" class="btn btn-outline-primary period-filter {{ $currentPeriod === 'month' ? 'active' : '' }}"
                        data-period="month">
                    <i class="fas fa-calendar-alt"></i> Mois
                </button>
            </div> --}}
        </div>
    </div>

    <!-- Cartes statistiques principales -->
    <div class="mb-4 row g-3">
        <!-- Total collecté -->
        <div class="col-xl-3 col-md-6">
            <div class="overflow-hidden border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-start justify-content-between">
                        <div class="p-3 bg-success bg-opacity-10 rounded-3">
                            <i class="fas fa-money-bill-wave text-success fa-2x"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success">
                            <i class="fas fa-arrow-up"></i> Collectes
                        </span>
                    </div>
                    <div class="mb-1 text-muted small">Total collecté</div>
                    <h3 class="mb-2 fw-bold" id="total-collected">
                        {{ number_format($collections['total_collected'], 0, ',', ' ') }}
                    </h3>
                    <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="date-range">{{ $collections['date_range']['start'] }} - {{ $collections['date_range']['end'] }}</span>
                    </small>
                </div>

            </div>
        </div>

        <!-- Clients gérés -->
        <div class="col-xl-3 col-md-6">
            <div class="overflow-hidden border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-start justify-content-between">
                        <div class="p-3 bg-primary bg-opacity-10 rounded-3">
                            <i class="fas fa-users text-primary fa-2x"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary">
                            Clients
                        </span>
                    </div>
                    <div class="mb-1 text-muted small">Clients gérés</div>
                    <h3 class="mb-2 fw-bold">{{ number_format($overview['total_clients']) }}</h3>
                    <small class="text-muted">
                        <span class="text-success me-2">
                            <i class="fas fa-user-check"></i> {{ $overview['active_clients'] }} actifs
                        </span>
                    </small>
                </div>

            </div>
        </div>

        <!-- Comptes actifs -->
        <div class="col-xl-3 col-md-6">
            <div class="overflow-hidden border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-start justify-content-between">
                        <div class="p-3 bg-info bg-opacity-10 rounded-3">
                            <i class="fas fa-wallet text-info fa-2x"></i>
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info" id="new-accounts-badge">
                            +{{ $overview['new_accounts'] }}
                        </span>
                    </div>
                    <div class="mb-1 text-muted small">Comptes actifs</div>
                    <h3 class="mb-2 fw-bold">
                        {{ $overview['savings_accounts'] + $overview['tontine_accounts'] }}
                    </h3>
                    <div class="gap-3 d-flex">
                        <small class="text-muted">
                            <i class="fas fa-handshake text-success"></i>
                            {{ $overview['tontine_accounts'] }} Tontine
                        </small>
                    </div>
                </div>

            </div>
        </div>

        <!-- Solde total -->
        <div class="col-xl-3 col-md-6">
            <div class="overflow-hidden border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-start justify-content-between">
                        <div class="p-3 bg-warning bg-opacity-10 rounded-3">
                            <i class="fas fa-coins text-warning fa-2x"></i>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning">
                            Soldes
                        </span>
                    </div>
                    <div class="mb-1 text-muted small">Solde total géré</div>
                    <h3 class="mb-2 fw-bold">
                        {{ number_format($overview['total_balance'], 0, ',', ' ') }}
                    </h3>
                    <small class="text-muted">
                        <i class="fas fa-chart-line"></i> Tous comptes confondus
                    </small>
                </div>

            </div>
        </div>
    </div>

    <div class="mb-4 row g-3">
        <!-- Graphique des collectes -->
        <div class="col-xl-8">
            <div class="border-0 shadow-sm card h-100">
                <div class="py-3 bg-white card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Évolution des collectes
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary chart-type active" data-type="line">
                                <i class="fas fa-chart-line"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary chart-type" data-type="bar">
                                <i class="fas fa-chart-bar"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="collectionsChart" height="80"></canvas>
                </div>
                <div class="bg-transparent card-footer">
                    <div class="text-center row">
                        <div class="col-4">
                            <small class="text-muted d-block">Dépôts</small>
                            <strong class="text-success" id="chart-total-deposits">-</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Retraits</small>
                            <strong class="text-danger" id="chart-total-withdrawals">-</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Net</small>
                            <strong class="text-primary" id="chart-net">-</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition des comptes -->
        <div class="col-xl-4">
            <div class="border-0 shadow-sm card h-100">
                <div class="py-3 bg-white card-header">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Répartition des soldes
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="accountDistributionChart"></canvas>
                </div>
                <div class="bg-transparent card-footer">
                    <div class="mb-3">
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-circle text-primary me-1"></i>
                                Épargne
                                <small class="text-muted">({{ $chartData['account_distribution']['savings']['count'] }})</small>
                            </span>
                            <strong class="text-primary">
                                {{ number_format($chartData['account_distribution']['savings']['balance'], 0, ',', ' ') }}
                            </strong>
                        </div>
                        <div class="mb-3 progress" style="height: 6px;">
                            @php
                                $total = $chartData['account_distribution']['savings']['balance'] + $chartData['account_distribution']['tontine']['balance'];
                                $savingsPercent = $total > 0 ? ($chartData['account_distribution']['savings']['balance'] / $total) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-primary" style="width: {{ $savingsPercent }}%"></div>
                        </div>

                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-circle text-success me-1"></i>
                                Tontine
                                <small class="text-muted">({{ $chartData['account_distribution']['tontine']['count'] }})</small>
                            </span>
                            <strong class="text-success">
                                {{ number_format($chartData['account_distribution']['tontine']['balance'], 0, ',', ' ') }}
                            </strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            @php
                                $tontinePercent = $total > 0 ? ($chartData['account_distribution']['tontine']['balance'] / $total) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $tontinePercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="d-none">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
    </div>
</div>
@endsection

