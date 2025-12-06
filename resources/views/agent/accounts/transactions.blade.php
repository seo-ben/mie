@extends('layouts.agent')

@section('title', 'Historique des Transactions')

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
            <li class="breadcrumb-item">
                <a href="{{ route('agent.accounts.show', $account->id) }}">{{ $account->account_number }}</a>
            </li>
            <li class="breadcrumb-item active">Transactions</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-md-8">
            <h2 class="mb-2">
                <i class="fas fa-receipt text-primary me-2"></i>
                Historique des Transactions
            </h2>
            <div class="flex-wrap gap-3 d-flex align-items-center">
                <div>
                    <small class="text-muted">Compte :</small>
                    <span class="badge bg-purple-soft text-purple">{{ $account->account_number }}</span>
                </div>
                <div>
                    <small class="text-muted">Titulaire :</small>
                    <strong>{{ $account->client->first_name }} {{ $account->client->last_name }}</strong>
                </div>
                <div>
                    <small class="text-muted">Solde actuel :</small>
                    <strong class="text-primary">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</strong>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('agent.accounts.show', $account->id) }}" class="mb-2 btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour au compte
            </a>
            <a href="{{ route('agent.accounts.deposit.form', $account->id) }}" class="mb-2 btn btn-success">
                <i class="fas fa-plus-circle me-2"></i>Nouveau dépôt
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mb-4 border-0 shadow-sm card">
        <div class="p-4 card-body">
            <form method="GET" action="{{ route('agent.accounts.transactions', $account->id) }}" class="row g-3">
                <div class="col-md-3">
                    <label class="mb-1 form-label small text-muted">Type de transaction</label>
                    <select name="type" class="form-select">
                        <option value="">-- Tous les types --</option>
                        <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>
                            Dépôts
                        </option>
                        <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>
                            Retraits
                        </option>
                        <option value="fee" {{ request('type') == 'fee' ? 'selected' : '' }}>
                            Frais
                        </option>
                        <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>
                            Virements
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="mb-1 form-label small text-muted">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">-- Tous --</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                            Complété
                        </option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                            En attente
                        </option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>
                            Échoué
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                            Annulé
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="mb-1 form-label small text-muted">Date début</label>
                    <input type="date"
                           name="date_from"
                           class="form-control"
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="mb-1 form-label small text-muted">Date fin</label>
                    <input type="date"
                           name="date_to"
                           class="form-control"
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="mb-1 form-label small text-muted">&nbsp;</label>
                    <div class="gap-2 d-grid d-md-flex">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                        <a href="{{ route('agent.accounts.transactions', $account->id) }}"
                           class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-1"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="mb-4 row g-3">
        <div class="col-md-3">
            <div class="border-0 shadow-sm card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="p-3 bg-success bg-opacity-10 rounded-circle">
                                <i class="fas fa-arrow-down fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Total Dépôts</h6>
                            @php
                                $totalDeposits = $transactions->where('transaction_type', 'deposit')->where('status', 'completed')->sum('amount');
                                $depositCount = $transactions->where('transaction_type', 'deposit')->where('status', 'completed')->count();
                            @endphp
                            <h4 class="mb-0 text-success fw-bold">
                                {{ number_format($totalDeposits, 0, ',', ' ') }}
                            </h4>
                            <small class="text-muted">{{ $depositCount }} transaction(s)</small>
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
                            <div class="p-3 bg-danger bg-opacity-10 rounded-circle">
                                <i class="fas fa-arrow-up fa-2x text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Total Retraits</h6>
                            @php
                                $totalWithdrawals = $transactions->where('transaction_type', 'withdrawal')->where('status', 'completed')->sum('amount');
                                $withdrawalCount = $transactions->where('transaction_type', 'withdrawal')->where('status', 'completed')->count();
                            @endphp
                            <h4 class="mb-0 text-danger fw-bold">
                                {{ number_format($totalWithdrawals, 0, ',', ' ') }}
                            </h4>
                            <small class="text-muted">{{ $withdrawalCount }} transaction(s)</small>
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
                                <i class="fas fa-coins fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Total Frais</h6>
                            @php
                                $totalFees = $transactions->where('status', 'completed')->sum('fee_amount');
                            @endphp
                            <h4 class="mb-0 text-warning fw-bold">
                                {{ number_format($totalFees, 0, ',', ' ') }}
                            </h4>
                            <small class="text-muted">Tous frais confondus</small>
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
                            <div class="p-3 bg-info bg-opacity-10 rounded-circle">
                                <i class="fas fa-list fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 text-muted small">Total Transactions</h6>
                            <h4 class="mb-0 text-info fw-bold">{{ $transactions->total() }}</h4>
                            <small class="text-muted">Sur cette période</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="border-0 shadow-sm card">
        <div class="py-3 bg-white card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list text-primary me-2"></i>
                    Liste des Transactions ({{ $transactions->total() }})
                </h5>
                <div class="text-muted small">
                    Page {{ $transactions->currentPage() }} sur {{ $transactions->lastPage() }}
                </div>
            </div>
        </div>
        <div class="p-0 card-body">
            @if($transactions->isEmpty())
                <div class="py-5 text-center">
                    <div class="mb-3">
                        <i class="opacity-50 fas fa-inbox fa-4x text-muted"></i>
                    </div>
                    <h5 class="text-muted">Aucune transaction trouvée</h5>
                    <p class="mb-4 text-muted">
                        @if(request()->hasAny(['type', 'status', 'date_from', 'date_to']))
                            Aucune transaction ne correspond aux critères de filtrage.
                        @else
                            Il n'y a pas encore de transactions sur ce compte.
                        @endif
                    </p>
                    @if(request()->hasAny(['type', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('agent.accounts.transactions', $account->id) }}" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Voir toutes les transactions
                        </a>
                    @else
                        <a href="{{ route('agent.accounts.deposit.form', $account->id) }}" class="btn btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Effectuer un dépôt
                        </a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table mb-0 table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" width="15%">Référence</th>
                                <th width="12%">Date & Heure</th>
                                <th width="10%">Type</th>
                                <th width="12%" class="text-end">Montant</th>
                                <th width="10%">Méthode</th>
                                <th width="15%">Référence Paiement</th>
                                <th width="10%">Statut</th>
                                <th width="10%">Traité par</th>
                                <th width="6%" class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr class="transaction-row">
                                    <td class="ps-4">
                                        <div class="font-monospace small">{{ $transaction->transaction_reference }}</div>
                                        @if($transaction->description)
                                            <small class="mt-1 text-muted d-block">
                                                <i class="fas fa-comment me-1"></i>{{ Str::limit($transaction->description, 30) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            <i class="fas fa-calendar me-1 text-muted"></i>
                                            {{ $transaction->transaction_date->format('d/m/Y') }}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $transaction->transaction_date->format('H:i') }}
                                        </div>
                                    </td>
                                    <td>
                                        @switch($transaction->transaction_type)
                                            @case('deposit')
                                                <span class="badge bg-success-soft text-success">
                                                    <i class="fas fa-arrow-down me-1"></i>Dépôt
                                                </span>
                                                @break
                                            @case('withdrawal')
                                                <span class="badge bg-danger-soft text-danger">
                                                    <i class="fas fa-arrow-up me-1"></i>Retrait
                                                </span>
                                                @break
                                            @case('fee')
                                                <span class="badge bg-warning-soft text-warning">
                                                    <i class="fas fa-coins me-1"></i>Frais
                                                </span>
                                                @break
                                            @case('transfer')
                                                <span class="badge bg-info-soft text-info">
                                                    <i class="fas fa-exchange-alt me-1"></i>Virement
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">
                                                    {{ ucfirst($transaction->transaction_type) }}
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold {{ $transaction->transaction_type === 'deposit' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}
                                            {{ number_format($transaction->amount, 0, ',', ' ') }}
                                        </div>
                                        <small class="text-muted">FCFA</small>
                                        @if($transaction->fee_amount > 0)
                                            <div class="small text-warning">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Frais: {{ number_format($transaction->fee_amount, 0, ',', ' ') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($transaction->payment_method)
                                            @case('cash')
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-money-bill-wave text-success me-2"></i>
                                                    <span>Espèces</span>
                                                </div>
                                                @break
                                            @case('mobile_money')
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-mobile-alt text-primary me-2"></i>
                                                    <div>
                                                        <div>Mobile Money</div>
                                                        @if($transaction->mobile_money_operator)
                                                            <span class="badge bg-light text-dark small">
                                                                {{ strtoupper($transaction->mobile_money_operator) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @break
                                            @case('bank_transfer')
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-university text-info me-2"></i>
                                                    <span>Virement</span>
                                                </div>
                                                @break
                                            @default
                                                <span class="text-muted">{{ $transaction->payment_method }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($transaction->payment_reference)
                                            <span class="font-monospace small">{{ $transaction->payment_reference }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($transaction->status)
                                            @case('completed')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-1"></i>Complété
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock me-1"></i>En attente
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times-circle me-1"></i>Échoué
                                                </span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-ban me-1"></i>Annulé
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($transaction->processedBy)
                                            <div class="small">
                                                <i class="fas fa-user me-1 text-muted"></i>
                                                {{ $transaction->processedBy->name ?? 'N/A' }}
                                            </div>
                                            @if($transaction->processed_at)
                                                <small class="text-muted">
                                                    {{ $transaction->processed_at->format('d/m H:i') }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#transactionModal{{ $transaction->id }}"
                                                title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal détails transaction -->
                                <div class="modal fade" id="transactionModal{{ $transaction->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-receipt text-primary me-2"></i>
                                                    Détails de la Transaction
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="text-muted small">Référence</label>
                                                        <div class="fw-bold font-monospace">{{ $transaction->transaction_reference }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="text-muted small">Statut</label>
                                                        <div>
                                                            @switch($transaction->status)
                                                                @case('completed')
                                                                    <span class="badge bg-success">Complété</span>
                                                                    @break
                                                                @case('pending')
                                                                    <span class="badge bg-warning">En attente</span>
                                                                    @break
                                                                @default
                                                                    <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                                            @endswitch
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="text-muted small">Date et Heure</label>
                                                        <div class="fw-bold">{{ $transaction->transaction_date->format('d/m/Y à H:i') }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="text-muted small">Type</label>
                                                        <div class="fw-bold">{{ ucfirst($transaction->transaction_type) }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="text-muted small">Montant</label>
                                                        <div class="h4 mb-0 {{ $transaction->transaction_type === 'deposit' ? 'text-success' : 'text-danger' }}">
                                                            {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}
                                                            {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA
                                                        </div>
                                                    </div>
                                                    @if($transaction->fee_amount > 0)
                                                        <div class="col-md-6">
                                                            <label class="text-muted small">Frais</label>
                                                            <div class="mb-0 h5 text-warning">
                                                                {{ number_format($transaction->fee_amount, 0, ',', ' ') }} FCFA
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="col-md-6">
                                                        <label class="text-muted small">Méthode de paiement</label>
                                                        <div class="fw-bold">
                                                            @switch($transaction->payment_method)
                                                                @case('cash') Espèces @break
                                                                @case('mobile_money') Mobile Money @break
                                                                @case('bank_transfer') Virement bancaire @break
                                                                @default {{ $transaction->payment_method }}
                                                            @endswitch
                                                            @if($transaction->mobile_money_operator)
                                                                <span class="badge bg-primary">{{ strtoupper($transaction->mobile_money_operator) }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($transaction->payment_reference)
                                                        <div class="col-md-6">
                                                            <label class="text-muted small">Référence de paiement</label>
                                                            <div class="font-monospace">{{ $transaction->payment_reference }}</div>
                                                        </div>
                                                    @endif
                                                    @if($transaction->balance_before !== null)
                                                        <div class="col-md-4">
                                                            <label class="text-muted small">Solde avant</label>
                                                            <div class="fw-bold">{{ number_format($transaction->balance_before, 0, ',', ' ') }} FCFA</div>
                                                        </div>
                                                    @endif
                                                    @if($transaction->balance_after !== null)
                                                        <div class="col-md-4">
                                                            <label class="text-muted small">Solde après</label>
                                                            <div class="fw-bold text-primary">{{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</div>
                                                        </div>
                                                    @endif
                                                    @if($transaction->description)
                                                        <div class="col-12">
                                                            <label class="text-muted small">Description</label>
                                                            <div class="p-3 rounded bg-light">{{ $transaction->description }}</div>
                                                        </div>
                                                    @endif
                                                    @if($transaction->processedBy)
                                                        <div class="col-md-6">
                                                            <label class="text-muted small">Traité par</label>
                                                            <div class="fw-bold">{{ $transaction->processedBy->name ?? 'N/A' }}</div>
                                                        </div>
                                                    @endif
                                                    @if($transaction->processed_at)
                                                        <div class="col-md-6">
                                                            <label class="text-muted small">Date de traitement</label>
                                                            <div class="fw-bold">{{ $transaction->processed_at->format('d/m/Y à H:i:s') }}</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Fermer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="bg-white card-footer border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Affichage de {{ $transactions->firstItem() }} à {{ $transactions->lastItem() }} sur {{ $transactions->total() }} transactions
                    </div>
                    <nav>
                        {{ $transactions->appends(request()->query())->links() }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .font-monospace {
        font-family: 'Courier New', monospace;
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

    .bg-danger-soft {
        background-color: #fee2e2;
    }

    .text-danger {
        color: #dc2626;
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

    .transaction-row {
        transition: background-color 0.2s ease;
    }

    .transaction-row:hover {
        background-color: #f8f9fa;
    }

    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
    }

    .modal-lg {
        max-width: 900px;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(el => {
        new bootstrap.Tooltip(el);
    });
});
</script>
@endpush

@endsection
