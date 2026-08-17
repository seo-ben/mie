@extends('layouts.agent')

@section('title', 'Collectes du Jour')

@section('content')
<div class="py-4 container-fluid">
    {{-- En-tête --}}
    <div class="mb-4 row">
        <div class="col-lg-8 col-md-6">
            <h4 class="mb-0">
                <i class="fas fa-calendar-day me-2"></i>
                Collectes du Jour
            </h4>
            <p class="mb-0 text-sm text-muted">
                <i class="fas fa-clock me-1"></i>
                {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
            </p>
        </div>
        <div class="text-end col-lg-4 col-md-6">
            <a href="{{ route('agent.accounts.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i>
                Retour
            </a>
            <a href="{{ route('agent.accounts.quick-deposit') }}" class="btn btn-success btn-sm">
                <i class="fas fa-bolt me-1"></i>
                Collecte Rapide
            </a>
        </div>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistiques du jour --}}
    <div class="mb-4 row">
        <div class="mb-3 col-xl-3 col-md-6 mb-xl-0">
            <div class="card">
                <div class="p-3 card-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="mb-0 text-sm text-uppercase font-weight-bold opacity-7">À Collecter</p>
                                <h5 class="font-weight-bolder text-warning">
                                    {{ number_format($stats['to_collect_count'], 0, ',', ' ') }}
                                </h5>
                                <p class="mb-0 text-xs text-muted">comptes</p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="text-center shadow icon icon-shape bg-gradient-warning rounded-circle">
                                <i class="text-lg fas fa-hourglass-half opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 col-xl-3 col-md-6 mb-xl-0">
            <div class="card">
                <div class="p-3 card-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="mb-0 text-sm text-uppercase font-weight-bold opacity-7">Déjà Collecté</p>
                                <h5 class="font-weight-bolder text-success">
                                    {{ number_format($stats['collected_count'], 0, ',', ' ') }}
                                </h5>
                                <p class="mb-0 text-xs text-muted">comptes</p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="text-center shadow icon icon-shape bg-gradient-success rounded-circle">
                                <i class="text-lg fas fa-check-circle opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 col-xl-3 col-md-6 mb-xl-0">
            <div class="card">
                <div class="p-3 card-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="mb-0 text-sm text-uppercase font-weight-bold opacity-7">Montant Collecté</p>
                                <h5 class="font-weight-bolder text-primary">
                                    {{ number_format($stats['collected_amount'], 0, ',', ' ') }}
                                </h5>
                                <p class="mb-0 text-xs text-muted">FCFA</p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="text-center shadow icon icon-shape bg-gradient-primary rounded-circle">
                                <i class="text-lg fas fa-coins opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-3 col-xl-3 col-md-6 mb-xl-0">
            <div class="card">
                <div class="p-3 card-body">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="mb-0 text-sm text-uppercase font-weight-bold opacity-7">Objectif</p>
                                <h5 class="font-weight-bolder text-info">
                                    {{ number_format($stats['target_amount'], 0, ',', ' ') }}
                                </h5>
                                <p class="mb-0 text-xs text-muted">FCFA</p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="text-center shadow icon icon-shape bg-gradient-info rounded-circle">
                                <i class="text-lg fas fa-bullseye opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre de progression globale --}}
    @if($stats['target_amount'] > 0)
        <div class="mb-4 card">
            <div class="card-body">
                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Progression du Jour</h6>
                    <span class="text-sm font-weight-bold">
                        {{ $stats['collected_amount'] > 0 ? round(($stats['collected_amount'] / $stats['target_amount']) * 100, 1) : 0 }}%
                    </span>
                </div>
                <div class="progress" style="height: 20px;">
                    @php
                        $progressPercent = $stats['target_amount'] > 0
                            ? min(100, round(($stats['collected_amount'] / $stats['target_amount']) * 100, 1))
                            : 0;
                    @endphp
                    <div class="progress-bar bg-gradient-success"
                         role="progressbar"
                         style="width: {{ $progressPercent }}%"
                         aria-valuenow="{{ $progressPercent }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <span class="text-white font-weight-bold">
                            {{ number_format($stats['collected_amount'], 0, ',', ' ') }} FCFA
                        </span>
                    </div>
                </div>
                <div class="mt-2 text-sm text-center text-muted">
                    Reste à collecter: {{ number_format($stats['target_amount'] - $stats['collected_amount'], 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Comptes à collecter --}}
        <div class="mb-4 col-lg-6">
            <div class="card">
                <div class="pb-0 card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-clock text-warning me-2"></i>
                            À Collecter Aujourd'hui
                        </h6>
                        <span class="badge bg-gradient-warning">{{ $accountsToCollect->count() }}</span>
                    </div>
                </div>
                <div class="p-0 card-body">
                    @forelse($accountsToCollect as $account)
                        @php
                            $tontine = $account->tontineAccount;
                            $activeCycle = $tontine->activeCycle;
                            $expectedAmount = $tontine->expected_monthly_payment ?? $tontine->tontine_amount;
                        @endphp
                        <div class="p-3 border-bottom hover-shadow">
                            <div class="mb-2 d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-sm">
                                        {{ $account->client->first_name }} {{ $account->client->last_name }}
                                    </h6>
                                    <p class="mb-1 text-xs text-muted">
                                        <i class="fas fa-hashtag me-1"></i>{{ $account->account_number }}
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-phone me-1"></i>{{ $account->client->phone }}
                                    </p>
                                    <div class="gap-2 mt-2 d-flex align-items-center">
                                        <span class="badge badge-sm bg-gradient-info">
                                            @switch($tontine->payment_frequency)
                                                @case('daily') Quotidien @break
                                                @case('weekly') Hebdomadaire @break
                                                @case('monthly') Mensuel @break
                                            @endswitch
                                        </span>
                                        @if($activeCycle)
                                            <span class="badge badge-sm bg-gradient-primary">
                                                Cycle #{{ $activeCycle->cycle_number }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="mb-1 text-sm font-weight-bold text-success">
                                        {{ number_format($expectedAmount, 0, ',', ' ') }} FCFA
                                    </p>
                                    <a href="{{ route('agent.accounts.deposit.form', $account->id) }}"
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-plus-circle me-1"></i>
                                        Collecter
                                    </a>
                                </div>
                            </div>
                            @if($activeCycle)
                                <div class="mt-2">
                                    <div class="mb-1 text-xs d-flex justify-content-between">
                                        <span class="text-muted">Progression du cycle</span>
                                        <span class="font-weight-bold">
                                            {{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} /
                                            {{ number_format($activeCycle->target_amount, 0, ',', ' ') }} FCFA
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        @php
                                            $cycleProgress = $activeCycle->target_amount > 0
                                                ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 1)
                                                : 0;
                                        @endphp
                                        <div class="progress-bar bg-gradient-success"
                                             style="width: {{ $cycleProgress }}%">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <i class="mb-3 fas fa-check-circle fa-3x text-success opacity-5"></i>
                            <p class="mb-0 text-muted">Toutes les collectes sont à jour ! 🎉</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Comptes déjà collectés --}}
        <div class="mb-4 col-lg-6">
            <div class="card">
                <div class="pb-0 card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Déjà Collectés Aujourd'hui
                        </h6>
                        <span class="badge bg-gradient-success">{{ $collectedToday->count() }}</span>
                    </div>
                </div>
                <div class="p-0 card-body">
                    @forelse($collectedToday as $account)
                        @php
                            $tontine = $account->tontineAccount;
                            $todayTransaction = $account->transactions()
                                ->where('transaction_type', 'deposit')
                                ->where('status', 'completed')
                                ->whereDate('transaction_date', today())
                                ->latest()
                                ->first();
                        @endphp
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-sm">
                                        {{ $account->client->first_name }} {{ $account->client->last_name }}
                                    </h6>
                                    <p class="mb-1 text-xs text-muted">
                                        <i class="fas fa-hashtag me-1"></i>{{ $account->account_number }}
                                    </p>
                                    @if($todayTransaction)
                                        <p class="mb-0 text-xs text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Collecté à {{ $todayTransaction->created_at->format('H:i') }}
                                        </p>
                                    @endif
                                    <div class="gap-2 mt-2 d-flex align-items-center">
                                        <span class="badge badge-sm bg-gradient-info">
                                            @switch($tontine->payment_frequency)
                                                @case('daily') Quotidien @break
                                                @case('weekly') Hebdomadaire @break
                                                @case('monthly') Mensuel @break
                                            @endswitch
                                        </span>
                                        <span class="badge badge-sm bg-gradient-success">
                                            <i class="fas fa-check me-1"></i>Payé
                                        </span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    @if($todayTransaction)
                                        <p class="mb-1 text-sm font-weight-bold text-success">
                                            +{{ number_format($todayTransaction->amount, 0, ',', ' ') }} FCFA
                                        </p>
                                    @endif
                                    <a href="{{ route('agent.accounts.show', $account->id) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>
                                        Voir
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <i class="mb-3 fas fa-inbox fa-3x opacity-5 text-muted"></i>
                            <p class="mb-0 text-muted">Aucune collecte effectuée aujourd'hui</p>
                            <a href="{{ route('agent.accounts.quick-deposit') }}" class="mt-2 btn btn-sm btn-success">
                                Commencer les collectes
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Actions rapides --}}
    <div class="card">
        <div class="py-4 text-center card-body">
            <h6 class="mb-3">Actions Rapides</h6>
            <div class="flex-wrap gap-2 d-flex justify-content-center">
                <a href="{{ route('agent.accounts.quick-deposit') }}" class="btn btn-success">
                    <i class="fas fa-bolt me-2"></i>
                    Collecte Rapide
                </a>
                <a href="{{ route('agent.transactions.index') }}" class="btn btn-info">
                    <i class="fas fa-history me-2"></i>
                    Historique des Collectes
                </a>
                <a href="{{ route('agent.accounts.index') }}" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>
                    Tous les Comptes
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print me-2"></i>
                    Imprimer
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        background-color: #f8f9fa;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    @media print {
        .btn, .icon, nav, header, footer {
            display: none !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Auto-refresh toutes les 5 minutes
    setTimeout(function() {
        if (confirm('Voulez-vous actualiser la page pour voir les dernières collectes ?')) {
            location.reload();
        }
    }, 300000); // 5 minutes

    // Animation des compteurs au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const counters = document.querySelectorAll('.numbers h5');
        counters.forEach(counter => {
            const target = parseInt(counter.innerText.replace(/\s/g, ''));
            if (!isNaN(target) && target < 1000) {
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.innerText = target.toLocaleString('fr-FR');
                        clearInterval(timer);
                    } else {
                        counter.innerText = Math.ceil(current).toLocaleString('fr-FR');
                    }
                }, 20);
            }
        });
    });
</script>
@endpush
@endsection
