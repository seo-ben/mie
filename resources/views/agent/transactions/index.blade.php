@extends('layouts.agent')

@section('title', 'Journal des Transactions')

@section('content')
<div class="px-4 py-4 container-fluid">
    <div class="mb-4 row align-items-center">
        <div class="col-md-6">
            <h1 class="mb-1 h3 fw-bold">Journal des Transactions</h1>
            <p class="mb-0 text-muted">Historique des flux de vos clients</p>
        </div>
        <div class="text-end col-md-6">
            <a href="{{ route('agent.accounts.quick-deposit') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nouveau Dépôt
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mb-4 shadow-sm border-0 card">
        <div class="card-body">
            <form action="{{ route('agent.transactions.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Recherche</label>
                    <div class="input-group">
                        <span class="bg-transparent input-group-text border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Client, Compte, Réf..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Date de début</label>
                    <input type="date" name="date_start" class="form-control" value="{{ request('date_start') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Date de fin</label>
                    <input type="date" name="date_end" class="form-control" value="{{ request('date_end') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Dépôt</option>
                        <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Retrait</option>
                        <option value="loan_repayment" {{ request('type') == 'loan_repayment' ? 'selected' : '' }}>Remboursement Prêt</option>
                        <option value="tontine_deposit" {{ request('type') == 'tontine_deposit' ? 'selected' : '' }}>Dépôt Tontine</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    <a href="{{ route('agent.transactions.index') }}" class="btn btn-outline-secondary" title="Réinitialiser">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="border-0 shadow-sm card">
        <div class="p-0 card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Référence & Date</th>
                            <th>Client & Compte</th>
                            <th>Type</th>
                            <th class="text-end">Montant</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-primary small mb-1">{{ $transaction->transaction_reference }}</div>
                                <div class="text-muted small">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $transaction->account->client->full_name ?? 'N/A' }}</div>
                                <div class="text-muted small">Compte: {{ $transaction->account->account_number ?? 'N/A' }}</div>
                            </td>
                            <td>
                                @php
                                    $typeColors = [
                                        'deposit' => 'success',
                                        'withdrawal' => 'danger',
                                        'loan_repayment' => 'info',
                                        'tontine_deposit' => 'primary',
                                        'savings_deposit' => 'success',
                                    ];
                                    $color = $typeColors[$transaction->transaction_type] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} px-2">
                                    {{ strtoupper($transaction->transaction_type) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold {{ in_array($transaction->transaction_type, ['withdrawal', 'loan_disbursement']) ? 'text-danger' : 'text-success' }}">
                                {{ in_array($transaction->transaction_type, ['withdrawal', 'loan_disbursement']) ? '-' : '+' }}
                                {{ number_format($transaction->amount, 0, ',', ' ') }} CFA
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                    ];
                                    $sColor = $statusColors[$transaction->status] ?? 'secondary';
                                @endphp
                                <span class="rounded-pill badge bg-{{ $sColor }} px-2">
                                    {{ $transaction->status }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('agent.transactions.show', $transaction->id) }}" class="btn btn-outline-primary" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('agent.transactions.receipt', $transaction->id) }}" class="btn btn-outline-info" target="_blank" title="Reçu">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
                                Aucune transaction trouvée
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
