@extends('layouts.agent')

@section('title', 'Détails du Compte Tontine')

@section('content')
<div class="py-4 container-fluid">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('agent.dashboard') }}">
                    <i class="fas fa-home me-1"></i>Tableau de bord
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('agent.accounts.index') }}">Comptes Tontine</a>
            </li>
            <li class="breadcrumb-item active">{{ $account->account_number }}</li>
        </ol>
    </nav>

    <!-- Messages flash -->
    @if(session('success'))
        <div class="shadow-sm alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Succès !</strong> {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="shadow-sm alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erreur !</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="shadow-sm alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Attention !</strong> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- En-tête avec informations principales -->
    <div class="mb-4 row">
        <div class="col-lg-8">
            <div class="border-0 shadow-sm card h-100">
                <div class="p-4 card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                Compte Tontine
                            </h2>
                            <div class="flex-wrap gap-3 d-flex align-items-center">
                                <span class="badge bg-purple-soft text-purple fs-6">
                                    {{ $account->account_number }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="mb-1 text-muted small">Solde actuel</div>
                            <h2 class="mb-0 text-primary fw-bold">
                                {{ number_format($account->balance, 0, ',', ' ') }}
                                <small class="text-muted fs-6">FCFA</small>
                            </h2>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Informations client -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                        {{ strtoupper(substr($account->client->first_name, 0, 1) . substr($account->client->last_name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="text-muted small">Titulaire du compte</div>
                                    <h5 class="mb-1">{{ $account->client->first_name }} {{ $account->client->last_name }}</h5>
                                    <div class="small text-muted">
                                        <i class="fas fa-phone me-1"></i>{{ $account->client->phone }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Numéro client</div>
                                    <div class="fw-bold">{{ $account->client->client_number }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Date de création</div>
                                    <div class="fw-bold">{{ $account->created_at->format('d/m/Y') }}</div>
                                </div>
                                @if($account->activated_at)
                                <div class="col-6">
                                    <div class="text-muted small">Date d'activation</div>
                                    <div class="fw-bold">{{ $account->activated_at->format('d/m/Y') }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="col-lg-4">
            <div class="border-0 shadow-sm card h-100">
                <div class="p-4 card-body">
                    <h6 class="mb-3 text-muted">
                        <i class="fas fa-bolt me-2"></i>Actions Rapides
                    </h6>
                    <div class="gap-2 d-grid">
                        <a href="{{ route('agent.accounts.deposit.form', $account->id) }}"
                           class="shadow-sm btn btn-success btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>Effectuer un Dépôt
                        </a>
                        <a href="{{ route('agent.accounts.transactions', $account->id) }}"
                           class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>Historique
                        </a>
                        <a href="{{ route('agent.clients.show', $account->client->id) }}"
                           class="btn btn-outline-secondary">
                            <i class="fas fa-user me-2"></i>Voir le Client
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations Tontine -->
    @if($account->tontineAccount)
        @php
            $tontine = $account->tontineAccount;
            $progress = $tontine->total_expected > 0
                ? round(($tontine->total_paid / $tontine->total_expected) * 100, 2)
                : 0;
            $remaining = $tontine->total_expected - $tontine->total_paid;
            $isComplete = $tontine->total_paid >= $tontine->total_expected;
        @endphp

        <div class="mb-4 row">
            <!-- Progression globale -->
            <div class="col-lg-8">
                <div class="border-0 shadow-sm card">
                    <div class="py-3 bg-white card-header border-bottom">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Progression de la Tontine
                        </h5>
                    </div>
                    <div class="p-4 card-body">
                        @if($isComplete)
                            <div class="mb-4 border-0 alert alert-success">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-trophy fa-3x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">🎉 Félicitations ! Tontine Complète !</h5>
                                        <p class="mb-0">L'objectif de {{ number_format($tontine->total_expected, 0, ',', ' ') }} FCFA a été atteint.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mb-4 row g-4">
                            <div class="col-md-4">
                                <div class="p-3 text-center rounded bg-light">
                                    <div class="mb-2 text-muted small">Total Payé</div>
                                    <h3 class="mb-0 text-success fw-bold">
                                        {{ number_format($tontine->total_paid, 0, ',', ' ') }}
                                    </h3>
                                    <small class="text-muted">FCFA</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 text-center rounded bg-light">
                                    <div class="mb-2 text-muted small">Total Attendu</div>
                                    <h3 class="mb-0 text-primary fw-bold">
                                        {{ number_format($tontine->total_expected, 0, ',', ' ') }}
                                    </h3>
                                    <small class="text-muted">FCFA</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 text-center rounded bg-light">
                                    <div class="mb-2 text-muted small">Restant</div>
                                    <h3 class="mb-0 fw-bold" style="color: {{ $remaining > 0 ? '#f59e0b' : '#10b981' }}">
                                        {{ number_format($remaining, 0, ',', ' ') }}
                                    </h3>
                                    <small class="text-muted">FCFA</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Progression totale</span>
                                <span class="badge bg-primary fs-6">{{ $progress }}%</span>
                            </div>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-gradient-primary"
                                     role="progressbar"
                                     style="width: {{ min($progress, 100) }}%"
                                     aria-valuenow="{{ $progress }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                    <strong>{{ $progress }}%</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-coins fa-2x text-primary me-3"></i>
                                    <div>
                                        <div class="text-muted small">Montant par période</div>
                                        <div class="fw-bold">{{ number_format($tontine->tontine_amount, 0, ',', ' ') }} FCFA</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-sync-alt fa-2x text-success me-3"></i>
                                    <div>
                                        <div class="text-muted small">Fréquence</div>
                                        <div class="fw-bold">
                                            @switch($tontine->payment_frequency)
                                                @case('daily') Quotidien @break
                                                @case('weekly') Hebdomadaire @break
                                                @case('monthly') Mensuel @break
                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-alt fa-2x text-info me-3"></i>
                                    <div>
                                        <div class="text-muted small">Durée</div>
                                        <div class="fw-bold">{{ $tontine->cycle_duration_months }} mois</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-check fa-2x text-warning me-3"></i>
                                    <div>
                                        <div class="text-muted small">Date de fin</div>
                                        <div class="fw-bold">{{ $tontine->cycle_end_date }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="col-lg-4">
                <div class="mb-3 border-0 shadow-sm card">
                    <div class="p-4 card-body">
                        <h6 class="mb-3 text-muted">
                            <i class="fas fa-chart-bar me-2"></i>Statistiques
                        </h6>
                        <div class="mb-3">
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Total Cycles</span>
                                <span class="badge bg-info-soft text-info">{{ $stats['total_cycles'] }}</span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Cycles Complétés</span>
                                <span class="badge bg-success-soft text-success">{{ $stats['completed_cycles'] }}</span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Cycles Actifs</span>
                                <span class="badge bg-primary-soft text-primary">{{ $stats['active_cycles'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Transactions</span>
                                <span class="badge bg-secondary">{{ $stats['transaction_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($tontine->activeCycle)
                    <div class="border-0 border-4 shadow-sm card border-start border-primary">
                        <div class="p-4 card-body">
                            <h6 class="mb-3 text-primary">
                                <i class="fas fa-sync-alt me-2"></i>Cycle Actif
                            </h6>
                            <div class="mb-3">
                                <span class="badge bg-primary-soft text-primary fs-5">
                                    Cycle #{{ $tontine->activeCycle->cycle_number }}
                                </span>
                            </div>
                            @php
                                $cycleProgress = $tontine->activeCycle->target_amount > 0
                                    ? round(($tontine->activeCycle->collected_amount / $tontine->activeCycle->target_amount) * 100, 1)
                                    : 0;
                                $cycleRemaining = $tontine->activeCycle->target_amount - $tontine->activeCycle->collected_amount;
                            @endphp
                            <div class="mb-3">
                                <div class="mb-1 d-flex justify-content-between">
                                    <small class="text-muted">Progression</small>
                                    <small class="fw-bold">{{ $cycleProgress }}%</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary"
                                         style="width: {{ $cycleProgress }}%"></div>
                                </div>
                            </div>
                            <div class="small">
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">Collecté</span>
                                    <span class="fw-bold text-success">
                                        {{ number_format($tontine->activeCycle->collected_amount, 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">Objectif</span>
                                    <span class="fw-bold">
                                        {{ number_format($tontine->activeCycle->target_amount, 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Restant</span>
                                    <span class="fw-bold text-warning">
                                        {{ number_format($cycleRemaining, 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                            </div>
                            <hr class="my-3">
                            <div class="small text-muted">
                                <i class="fas fa-calendar me-2"></i>
                                {{ $tontine->activeCycle->start_date->format('d/m/Y') }} -
                                {{ $tontine->activeCycle->end_date->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>


    @endif

    <!-- Transactions récentes -->
    @if($account->transactions->isNotEmpty())
        <div class="row">
            <div class="col-12">
                <div class="border-0 shadow-sm card">
                    <div class="py-3 bg-white card-header border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt text-primary me-2"></i>
                                Transactions Récentes
                            </h5>
                            <a href="{{ route('agent.accounts.transactions', $account->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                Voir tout
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-0 card-body">
                        <div class="table-responsive">
                            <table class="table mb-0 table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Référence</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Méthode</th>
                                        <th>Statut</th>
                                        <th class="pe-4">Traité par</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($account->transactions->take(10) as $transaction)
                                        <tr>
                                            <td class="ps-4">
                                                <span class="font-monospace small">
                                                    {{ $transaction->transaction_reference }}
                                                </span>
                                            </td>
                                            <td>
                                                <small>{{ $transaction->transaction_date->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td>
                                                @if($transaction->transaction_type === 'deposit')
                                                    <span class="badge bg-success-soft text-success">
                                                        <i class="fas fa-arrow-down me-1"></i>Dépôt
                                                    </span>
                                                @elseif($transaction->transaction_type === 'withdrawal')
                                                    <span class="badge bg-danger-soft text-danger">
                                                        <i class="fas fa-arrow-up me-1"></i>Retrait
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        {{ ucfirst($transaction->transaction_type) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-bold {{ $transaction->transaction_type === 'deposit' ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}
                                                    {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    @switch($transaction->payment_method)
                                                        @case('cash')
                                                            <i class="fas fa-money-bill-wave text-success me-1"></i>Espèces
                                                            @break
                                                        @case('mobile_money')
                                                            <i class="fas fa-mobile-alt text-primary me-1"></i>Mobile Money
                                                            @if($transaction->mobile_money_operator)
                                                                <span class="badge bg-light text-dark">
                                                                    {{ strtoupper($transaction->mobile_money_operator) }}
                                                                </span>
                                                            @endif
                                                            @break
                                                        @case('bank_transfer')
                                                            <i class="fas fa-university text-info me-1"></i>Virement
                                                            @break
                                                    @endswitch
                                                </small>
                                            </td>
                                            <td>
                                                @if($transaction->status === 'completed')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Complété
                                                    </span>
                                                @elseif($transaction->status === 'pending')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>En attente
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>{{ ucfirst($transaction->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="pe-4">
                                                @if($transaction->processedBy)
                                                    <small class="text-muted">
                                                        {{ $transaction->processedBy->name ?? 'N/A' }}
                                                    </small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

<style>
    .avatar-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.3rem;
    }

    .bg-gradient-primary {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }

    .bg-purple-soft {
        background-color: #f3e8ff;
    }

    .text-purple {
        color: #9333ea;
    }

    .bg-success-soft {
        background-color: #d1fae5;
    }

    .text-success {
        color: #059669;
    }

    .bg-info-soft {
        background-color: #dbeafe;
    }

    .text-info {
        color: #3b82f6;
    }

    .bg-primary-soft {
        background-color: #dbeafe;
    }

    .text-primary {
        color: #3b82f6;
    }

    .bg-danger-soft {
        background-color: #fee2e2;
    }

    .text-danger {
        color: #dc2626;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
    }

    .border-4 {
        border-width: 4px !important;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        transition: width 0.6s ease;
    }
</style>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });

    // Auto-hide des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Tooltip pour les badges
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
