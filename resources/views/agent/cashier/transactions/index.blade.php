@extends('layouts.cashier')

@section('title', 'Historique des Transactions')
@section('page-title', 'Transactions')
@section('page-subtitle', 'Historique des opérations')

@section('content')
<div class="container-fluid py-4">
    <!-- Filtres -->
    <div class="card glass-card border-0 shadow-lg mb-4">
        <div class="card-header bg-transparent border-bottom border-white-5 p-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white fw-bold"><i class="fas fa-filter me-2 text-teal-accent"></i>Recherche et Filtres</h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('caissier.transactions.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small">Date Début</label>
                        <input type="date" class="form-control custom-input" name="date_start" value="{{ request('date_start') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small">Date Fin</label>
                        <input type="date" class="form-control custom-input" name="date_end" value="{{ request('date_end') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small">Type</label>
                        <select class="form-select custom-input" name="type">
                            <option value="">Tous les types</option>
                            <option value="deposit" {{ request('type') == 'deposit' ? 'selected' : '' }}>Dépôt</option>
                            <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Retrait</option>
                            <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transfert</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white-50 small">Recherche</label>
                        <div class="d-flex gap-2 h-100 align-items-start">
                            <input type="text" class="form-control custom-input" name="search" placeholder="Réf, Compte, Client..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary-gradient px-3 rounded-3" style="min-height: 44px;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card glass-card border-0 shadow-lg">
        <div class="card-header bg-transparent border-bottom border-white-5 p-4">
            <h5 class="mb-0 text-white fw-bold"><i class="fas fa-exchange-alt me-2 text-teal-accent"></i>Liste des Transactions ({{ $transactions->total() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table custom-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Référence</th>
                            <th>N° Compte / Client</th>
                            <th>Type</th>
                            <th>Montant (FCFA)</th>
                            <th>Agent / Caissier</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td class="text-white-50">
                                {{ $transaction->created_at->format('d/m/Y') }}<br>
                                <small>{{ $transaction->created_at->format('H:i') }}</small>
                            </td>
                            <td class="text-teal-accent fw-bold">{{ $transaction->transaction_reference ?? $transaction->transaction_number }}</td>
                            <td>
                                <div><span class="text-white">{{ $transaction->account->account_number }}</span></div>
                                <div class="text-white-50" style="font-size: 0.75rem;">
                                    {{ $transaction->account->client->first_name }} {{ $transaction->account->client->last_name }}
                                </div>
                            </td>
                            <td>
                                @if(in_array($transaction->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit']))
                                    <span class="badge bg-blur-teal text-teal-accent border border-teal-soft"><i class="fas fa-arrow-down me-1"></i>Dépôt</span>
                                @elseif(in_array($transaction->transaction_type, ['withdrawal', 'payout']))
                                    <span class="badge bg-danger-soft text-danger"><i class="fas fa-arrow-up me-1"></i>Retrait</span>
                                @elseif($transaction->transaction_type == 'fee')
                                    <span class="badge bg-warning-soft text-warning"><i class="fas fa-tag me-1"></i>Frais</span>
                                @else
                                    <span class="badge bg-info-soft text-info"><i class="fas fa-exchange-alt me-1"></i>{{ $transaction->transaction_type ?? $transaction->type }}</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($transaction->transaction_type, ['withdrawal', 'payout', 'fee']))
                                    <span class="text-danger fw-bold">- {{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                                @else
                                    <span class="text-teal-accent fw-bold">+ {{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-small me-2" style="width: 28px; height: 28px; font-size: 0.6rem;">
                                        {{ substr($transaction->processedBy->first_name ?? 'A', 0, 1) }}{{ substr($transaction->processedBy->last_name ?? 'A', 0, 1) }}
                                    </div>
                                    <span class="text-white-50" style="font-size: 0.8rem;">{{ $transaction->processedBy->full_name ?? 'Système' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($transaction->status == 'completed')
                                    <span class="badge bg-success-soft text-success"><i class="fas fa-check me-1"></i>Complet</span>
                                @elseif($transaction->status == 'pending')
                                    <span class="badge bg-warning-soft text-warning"><i class="fas fa-clock me-1"></i>En attente</span>
                                @else
                                    <span class="badge bg-danger-soft text-danger">{{ $transaction->status }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-search-dollar fa-3x text-white-50 mb-3"></i>
                                <h5 class="text-white">Aucune transaction trouvée</h5>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator && $transactions->hasPages())
                <div class="p-4 border-top border-white-5">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .glass-card {
        background: rgba(26, 35, 50, 0.7);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 20px;
    }

    .custom-input {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: white !important;
        border-radius: 12px;
        padding: 10px 16px;
    }
    .custom-input:focus {
        border-color: #00d1b2 !important;
        box-shadow: 0 0 0 4px rgba(0, 209, 178, 0.15) !important;
    }

    .btn-primary-gradient {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
        border: none;
        color: white;
        transition: all 0.3s;
    }
    .btn-primary-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 209, 178, 0.2);
    }

    .custom-table {
        color: white;
    }
    .custom-table thead th {
        background: rgba(0, 0, 0, 0.3) !important;
        color: #00d1b2;
        border-bottom: none;
        padding: 15px 20px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    .custom-table tbody td {
        background: transparent !important;
        border-color: rgba(255, 255, 255, 0.05);
        padding: 15px 20px;
        vertical-align: middle;
    }
    .custom-table tbody tr {
        transition: background 0.2s;
    }
    .custom-table tbody tr:hover td {
        background: rgba(255, 255, 255, 0.02) !important;
    }

    .bg-blur-teal { background: rgba(0, 209, 178, 0.1); }
    .text-teal-accent { color: #00d1b2; }
    .border-teal-soft { border-color: rgba(0, 209, 178, 0.3) !important; }
    .bg-success-soft { background: rgba(0, 255, 170, 0.1); }
    .bg-warning-soft { background: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background: rgba(255, 0, 0, 0.1); }
    .bg-info-soft { background: rgba(0, 180, 216, 0.1); }

    .user-avatar-small {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(0, 209, 178, 0.2), rgba(0, 180, 216, 0.2));
        color: #00d1b2;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        border: 1px solid rgba(0, 209, 178, 0.3);
    }
</style>
@endsection
