@extends('layouts.app_admin')

@section('title', 'Rapport Intérêts des Prêts')

@section('content')
<div class="py-4 container-fluid">

    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">💰 Revenus des Prêts</h2>
                    <p class="mb-0 text-muted">
                        Période: {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}
                    </p>
                </div>

                <div class="gap-2 d-flex">
                    <form method="GET" action="{{ route('admin.profitability.loan-interest') }}" class="gap-2 d-flex">
                        <select name="period" class="form-select" onchange="this.form.submit()">
                            <option value="30days" {{ $period == '30days' ? 'selected' : '' }}>30 jours</option>
                            <option value="90days" {{ $period == '90days' ? 'selected' : '' }}>90 jours</option>
                            <option value="6months" {{ $period == '6months' ? 'selected' : '' }}>6 mois</option>
                            <option value="year" {{ $period == 'year' ? 'selected' : '' }}>1 an</option>
                        </select>
                    </form>

                    <a href="{{ route('admin.profitability.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs Principaux -->
    <div class="mb-4 row g-3">
        <div class="col-md-3">
            <div class="text-white border-0 shadow-sm card bg-success h-100">
                <div class="text-center card-body">
                    <p class="mb-1 text-white-50 small">Intérêts Collectés</p>
                    <h3 class="mb-0">{{ number_format($loanData['collected_interest'], 0, ',', ' ') }}</h3>
                    <small>FCFA</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="text-white border-0 shadow-sm card bg-warning h-100">
                <div class="text-center card-body">
                    <p class="mb-1 text-white-50 small">Intérêts en Attente</p>
                    <h3 class="mb-0">{{ number_format($loanData['pending_interest'], 0, ',', ' ') }}</h3>
                    <small>FCFA</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="text-white border-0 shadow-sm card bg-danger h-100">
                <div class="text-center card-body">
                    <p class="mb-1 text-white-50 small">Pénalités de Retard</p>
                    <h3 class="mb-0">{{ number_format($loanData['penalties'], 0, ',', ' ') }}</h3>
                    <small>FCFA</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="text-white border-0 shadow-sm card bg-info h-100">
                <div class="text-center card-body">
                    <p class="mb-1 text-white-50 small">Portfolio Actif</p>
                    <h3 class="mb-0">{{ number_format($loanData['active_portfolio'], 0, ',', ' ') }}</h3>
                    <small>FCFA</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenus Total des Prêts -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="border-0 shadow-sm card">
                <div class="py-4 text-center card-body">
                    <h6 class="mb-2 text-muted">Revenus Total des Prêts (Intérêts + Pénalités)</h6>
                    <h1 class="mb-0 text-primary display-4">
                        {{ number_format($loanData['collected_interest'] + $loanData['penalties'], 0, ',', ' ') }}
                        <small class="fs-4">FCFA</small>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Analyse par Niveau de Risque -->
    <div class="mb-4 row g-3">
        <div class="col-md-8">
            <div class="border-0 shadow-sm card">
                <div class="py-3 bg-white card-header">
                    <h5 class="mb-0">📊 Revenus par Niveau de Risque</h5>
                </div>
                <div class="card-body">
                    <canvas id="riskLevelChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border-0 shadow-sm card h-100">
                <div class="py-3 bg-white card-header">
                    <h5 class="mb-0">🎯 Détails par Risque</h5>
                </div>
                <div class="card-body">
                    @php
                        $riskColors = [
                            'low' => 'success',
                            'medium' => 'info',
                            'high' => 'warning',
                            'very_high' => 'danger'
                        ];
                        $riskLabels = [
                            'low' => 'Faible',
                            'medium' => 'Moyen',
                            'high' => 'Élevé',
                            'very_high' => 'Très Élevé'
                        ];
                    @endphp

                    @foreach(['low', 'medium', 'high', 'very_high'] as $risk)
                        @if(isset($byRiskLevel[$risk]))
                        <div class="mb-3">
                            <div class="mb-1 d-flex justify-content-between">
                                <span class="badge bg-{{ $riskColors[$risk] }}">{{ $riskLabels[$risk] }}</span>
                                <strong>{{ number_format($byRiskLevel[$risk], 0, ',', ' ') }} FCFA</strong>
                            </div>
                            @php
                                $total = array_sum($byRiskLevel->toArray());
                                $percentage = $total > 0 ? round(($byRiskLevel[$risk] / $total) * 100, 1) : 0;
                            @endphp
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-{{ $riskColors[$risk] }}"
                                     style="width: {{ $percentage }}%"
                                     role="progressbar"></div>
                            </div>
                            <small class="text-muted">{{ $percentage }}% du total</small>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Portfolio et Projections -->
    <div class="mb-4 row g-3">
        <div class="col-md-6">
            <div class="border-0 shadow-sm card h-100">
                <div class="py-3 bg-white card-header">
                    <h5 class="mb-0">💼 Portfolio de Prêts Actifs</h5>
                </div>
                <div class="card-body">
                    <div class="text-center row">
                        <div class="mb-3 col-6">
                            <p class="mb-1 text-muted small">Capital Prêté</p>
                            <h4 class="mb-0">{{ number_format($loanData['active_portfolio'], 0, ',', ' ') }}</h4>
                            <small class="text-muted">FCFA</small>
                        </div>
                        <div class="mb-3 col-6">
                            <p class="mb-1 text-muted small">Intérêts Projetés</p>
                            <h4 class="mb-0 text-success">{{ number_format($loanData['projected_interest'], 0, ',', ' ') }}</h4>
                            <small class="text-muted">FCFA</small>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-0 alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Rendement Potentiel:</strong>
                        {{ $loanData['active_portfolio'] > 0 ? number_format(($loanData['projected_interest'] / $loanData['active_portfolio']) * 100, 2) : 0 }}%
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="border-0 shadow-sm card h-100">
                <div class="py-3 bg-white card-header">
                    <h5 class="mb-0">⚠️ Pénalités & Retards</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center row">
                        <div class="col-6">
                            <p class="mb-1 text-muted small">Pénalités Collectées</p>
                            <h4 class="mb-0 text-danger">{{ number_format($loanData['penalties'], 0, ',', ' ') }}</h4>
                            <small class="text-muted">FCFA</small>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-muted small">% des Revenus</p>
                            <h4 class="mb-0">
                                {{ ($loanData['collected_interest'] + $loanData['penalties']) > 0 ? number_format(($loanData['penalties'] / ($loanData['collected_interest'] + $loanData['penalties'])) * 100, 1) : 0 }}%
                            </h4>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-0 alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Les pénalités représentent un revenu additionnel mais indiquent aussi des retards de paiement.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques Détaillées -->
    <div class="row">
        <div class="col-12">
            <div class="border-0 shadow-sm card">
                <div class="py-3 bg-white card-header">
                    <h5 class="mb-0">📈 Statistiques Détaillées</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Métrique</th>
                                    <th class="text-end">Valeur</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Intérêts Collectés</strong></td>
                                    <td class="text-end text-success">{{ number_format($loanData['collected_interest'], 0, ',', ' ') }} FCFA</td>
                                    <td><small class="text-muted">Revenus confirmés</small></td>
                                </tr>
                                <tr>
                                    <td><strong>Intérêts en Attente</strong></td>
                                    <td class="text-end text-warning">{{ number_format($loanData['pending_interest'], 0, ',', ' ') }} FCFA</td>
                                    <td><small class="text-muted">À collecter</small></td>
                                </tr>
                                <tr>
                                    <td><strong>Pénalités</strong></td>
                                    <td class="text-end text-danger">{{ number_format($loanData['penalties'], 0, ',', ' ') }} FCFA</td>
                                    <td><small class="text-muted">Retards de paiement</small></td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Total Revenus Prêts</strong></td>
                                    <td class="text-end"><strong>{{ number_format($loanData['collected_interest'] + $loanData['penalties'], 0, ',', ' ') }} FCFA</strong></td>
                                    <td><small class="text-muted">Période actuelle</small></td>
                                </tr>
                                <tr>
                                    <td><strong>Portfolio Actif</strong></td>
                                    <td class="text-end text-info">{{ number_format($loanData['active_portfolio'], 0, ',', ' ') }} FCFA</td>
                                    <td><small class="text-muted">Capital en circulation</small></td>
                                </tr>
                                <tr>
                                    <td><strong>Intérêts Projetés</strong></td>
                                    <td class="text-end text-primary">{{ number_format($loanData['projected_interest'], 0, ',', ' ') }} FCFA</td>
                                    <td><small class="text-muted">Revenus futurs attendus</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Graphique par niveau de risque
const riskCtx = document.getElementById('riskLevelChart').getContext('2d');
new Chart(riskCtx, {
    type: 'doughnut',
    data: {
        labels: ['Faible', 'Moyen', 'Élevé', 'Très Élevé'],
        datasets: [{
            data: [
                {{ $byRiskLevel->get('low', 0) }},
                {{ $byRiskLevel->get('medium', 0) }},
                {{ $byRiskLevel->get('high', 0) }},
                {{ $byRiskLevel->get('very_high', 0) }}
            ],
            backgroundColor: [
                '#198754',
                '#0dcaf0',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA';
                    }
                }
            }
        }
    }
});
</script>
@endpush

@endsection
