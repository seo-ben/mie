@extends('layouts.agent')

@section('title', 'Mes Comptes Tontine')

@section('content')
<div class="py-4 container-fluid">

    <!-- En-tête avec titre et bouton d'action -->
    <div class="mb-4 row">
        <div class="col-md-8">
            <h2 class="mb-1">
                <i class="fas fa-users text-primary me-2"></i>
                Mes Comptes Tontine
            </h2>
            <p class="mb-0 text-muted">Gérez les comptes tontine de vos clients</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('agent.accounts.quick-deposit') }}" class="shadow-sm btn btn-success btn-lg">
                <i class="fas fa-bolt me-2"></i>Dépôt Rapide
            </a>
        </div>
    </div>

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

    <!-- Statistiques -->
    <div class="mb-4 row g-3">
        <div class="col-md-3">
            <div class="border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 bg-primary bg-opacity-10 rounded-circle">
                                <i class="fas fa-wallet fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Total Comptes</h6>
                            <h3 class="mb-0 fw-bold">{{ $stats['total_accounts'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 bg-success bg-opacity-10 rounded-circle">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Comptes Actifs</h6>
                            <h3 class="mb-0 fw-bold text-success">{{ $stats['active_accounts'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 bg-warning bg-opacity-10 rounded-circle">
                                <i class="fas fa-pause-circle fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Suspendus</h6>
                            <h3 class="mb-0 fw-bold text-warning">{{ $stats['suspended_accounts'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="text-white border-0 shadow-sm card h-100 bg-gradient-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                                <i class="text-white fas fa-coins fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 opacity-75 small">Solde Total</h6>
                            <h4 class="mb-0 fw-bold">{{ number_format($stats['total_balance'], 0, ',', ' ') }}</h4>
                            <small class="opacity-75">FCFA</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="mb-4 border-0 shadow-sm card">
        <div class="card-body">
            <form method="GET" action="{{ route('agent.accounts.index') }}" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="bg-white input-group-text border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Rechercher par nom, numéro de compte, téléphone..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- Tous les statuts --</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspendu</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="gap-2 d-grid d-md-flex">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('agent.accounts.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-1"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des comptes -->
    <div class="border-0 shadow-sm card">
        <div class="py-3 bg-white card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list text-primary me-2"></i>
                    Liste des Comptes ({{ $accounts->total() }})
                </h5>
                <div class="text-muted small">
                    Page {{ $accounts->currentPage() }} sur {{ $accounts->lastPage() }}
                </div>
            </div>
        </div>
        <div class="p-0 card-body">
            @forelse($accounts as $account)
                <div class="p-3 transition border-bottom hover-bg-light">
                    <div class="row align-items-center">
                        <!-- Informations client -->
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary">
                                        {{ strtoupper(substr($account->client->first_name, 0, 1) . substr($account->client->last_name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1 fw-bold">{{ $account->client->first_name }} {{ $account->client->last_name }}</h6>
                                    <div class="small text-muted">
                                        <i class="fas fa-hashtag me-1"></i>{{ $account->client->client_number }}
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-phone me-1"></i>{{ $account->client->phone }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations compte -->
                        <div class="col-md-3">
                            <div>
                                <span class="mb-2 badge bg-purple-soft text-purple">
                                    <i class="fas fa-users me-1"></i>Tontine
                                </span>
                                <div class="mb-1 fw-bold font-monospace small">{{ $account->account_number }}</div>
                                @if($account->status === 'active')
                                    <span class="badge bg-success-soft text-success">
                                        <i class="fas fa-check-circle me-1"></i>Actif
                                    </span>
                                @else
                                    <span class="badge bg-warning-soft text-warning">
                                        <i class="fas fa-pause-circle me-1"></i>Suspendu
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Progression tontine -->
                        <div class="col-md-3">
                            @if($account->tontineAccount)
                                @php
                                    $tontine = $account->tontineAccount;
                                    $progress = $tontine->total_expected > 0
                                        ? round(($tontine->total_paid / $tontine->total_expected) * 100, 1)
                                        : 0;
                                @endphp
                                <div class="mb-2">
                                    <div class="mb-1 d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Progression</small>
                                        <small class="fw-bold text-primary">{{ $progress }}%</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary"
                                             role="progressbar"
                                             style="width: {{ $progress }}%"
                                             aria-valuenow="{{ $progress }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="small text-muted">
                                    {{ number_format($tontine->total_paid, 0, ',', ' ') }} /
                                    {{ number_format($tontine->total_expected, 0, ',', ' ') }} FCFA
                                </div>

                                @if($tontine->activeCycle)
                                    <div class="mt-2 small">
                                        <span class="badge bg-info-soft text-info">
                                            <i class="fas fa-sync-alt me-1"></i>Cycle #{{ $tontine->activeCycle->cycle_number }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Solde et actions -->
                        <div class="col-md-2 text-end">
                            <h4 class="mb-2 fw-bold text-primary">{{ number_format($account->balance, 0, ',', ' ') }}</h4>
                            <small class="mb-3 text-muted d-block">FCFA</small>

                            <div class="btn-group" role="group">
                                <a href="{{ route('agent.accounts.show', $account->id) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip"
                                   title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($account->status === 'active')
                                    <a href="{{ route('agent.accounts.deposit.form', $account->id) }}"
                                       class="btn btn-sm btn-outline-success"
                                       data-bs-toggle="tooltip"
                                       title="Faire un dépôt">
                                        <i class="fas fa-plus-circle"></i>
                                    </a>
                                @else
                                    <a href="{{ route('agent.accounts.activate.form', $account->id) }}"
                                       class="btn btn-sm btn-outline-warning"
                                       data-bs-toggle="tooltip"
                                       title="Activer le compte">
                                        <i class="fas fa-play-circle"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-5 text-center">
                    <div class="mb-3">
                        <i class="opacity-50 fas fa-inbox fa-4x text-muted"></i>
                    </div>
                    <h5 class="text-muted">Aucun compte tontine trouvé</h5>
                    <p class="mb-0 text-muted">Commencez par créer un compte pour vos clients</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($accounts->hasPages())
            <div class="bg-white card-footer border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Affichage de {{ $accounts->firstItem() }} à {{ $accounts->lastItem() }} sur {{ $accounts->total() }} comptes
                    </div>
                    <nav>
                        {{ $accounts->links() }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }

    .transition {
        transition: all 0.3s ease;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    .bg-warning-soft {
        background-color: #fef3c7;
    }

    .text-warning {
        color: #d97706;
    }

    .bg-info-soft {
        background-color: #dbeafe;
    }

    .text-info {
        color: #3b82f6;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
    }
</style>

@push('scripts')
<script>
    // Initialiser les tooltips Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

@endsection
