@extends('layouts.cashier')

@section('title', 'Terminal de Caisse')
@section('page-title', 'Terminal de Caisse')
@section('page-subtitle', $stats->is_audit ? 'Mode Audit' : 'Session en cours')

@push('styles')
<style>
    .terminal-header {
        background: linear-gradient(135deg, rgba(0, 209, 178, 0.08), rgba(0, 180, 216, 0.05));
        border: 1px solid rgba(0, 209, 178, 0.15);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 25px;
    }

    .terminal-stat {
        text-align: center;
        padding: 10px;
    }

    .terminal-stat .value {
        font-size: 1.4rem;
        font-weight: 700;
        color: white;
    }

    .terminal-stat .label {
        font-size: 0.75rem;
        color: rgba(225, 232, 237, 0.5);
        margin-top: 4px;
    }

    .terminal-stat.in .value { color: #10b981; }
    .terminal-stat.out .value { color: #ef4444; }
    .terminal-stat.balance .value { color: #00d1b2; }

    .audit-form {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 20px;
    }

    .audit-form label { color: rgba(225, 232, 237, 0.6); font-size: 0.8rem; }

    .audit-form input[type="date"],
    .audit-form select {
        background: #0f1923;
        border: 1px solid rgba(0, 209, 178, 0.2);
        color: white;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.85rem;
    }

    .audit-form input[type="date"]:focus,
    .audit-form select:focus {
        border-color: #00d1b2;
        box-shadow: 0 0 0 3px rgba(0, 209, 178, 0.1);
        outline: none;
    }

    .tx-table {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 16px;
        overflow: hidden;
    }

    .tx-table .table-header {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(0, 209, 178, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tx-table .table-header h6 { color: white; font-weight: 600; margin: 0; }

    .tx-table table { width: 100%; margin: 0; }

    .tx-table table th {
        color: #00d1b2 !important; /* Teal for high visibility */
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 2px solid rgba(0, 209, 178, 0.1);
        background: rgba(0, 0, 0, 0.15) !important;
    }

    .tx-table table td {
        padding: 11px 16px;
        border-bottom: 1px solid rgba(0, 209, 178, 0.05);
        color: #e2e8f0; /* Off-white for better visibility */
        font-size: 0.83rem;
        vertical-align: middle;
        background: transparent !important;
    }

    .tx-table table tr:hover { background: rgba(0, 209, 178, 0.05) !important; }

    .badge-type {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .badge-type.deposit { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .badge-type.withdrawal { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .badge-type.other { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

    .amount-in { color: #10b981; font-weight: 600; }
    .amount-out { color: #ef4444; font-weight: 600; }

    .btn-caissier {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
        color: white;
        border: none;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-caissier:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(0, 209, 178, 0.3);
        color: white;
    }

    .btn-outline-caissier {
        background: transparent;
        color: #00d1b2;
        border: 1px solid rgba(0, 209, 178, 0.3);
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-outline-caissier:hover {
        background: rgba(0, 209, 178, 0.1);
        color: #00d1b2;
    }

    .session-info-bar {
        background: rgba(0, 209, 178, 0.08);
        border: 1px solid rgba(0, 209, 178, 0.15);
        border-radius: 10px;
        padding: 12px 18px;
        font-size: 0.83rem;
        color: rgba(225, 232, 237, 0.7);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .pagination .page-link {
        background: #1a2332;
        border-color: rgba(0, 209, 178, 0.15);
        color: rgba(225, 232, 237, 0.7);
    }

    .pagination .page-link:hover {
        background: rgba(0, 209, 178, 0.1);
        color: #00d1b2;
    }

    .pagination .page-item.active .page-link {
        background: #00d1b2;
        border-color: #00d1b2;
        color: white;
    }
</style>
@endpush

@section('content')

<!-- Stats Header -->
<div class="terminal-header">
    <div class="row align-items-center">
        <div class="col-md-3">
            <div class="terminal-stat">
                <div class="label">Solde d'Ouverture</div>
                <div class="value">{{ number_format($stats->opening_balance, 0, ',', ' ') }}</div>
                <div class="label">FCFA</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="terminal-stat in">
                <div class="label">Total Entrées</div>
                <div class="value">+{{ number_format($stats->total_in, 0, ',', ' ') }}</div>
                <div class="label">FCFA</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="terminal-stat out">
                <div class="label">Total Sorties</div>
                <div class="value">-{{ number_format($stats->total_out, 0, ',', ' ') }}</div>
                <div class="label">FCFA</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="terminal-stat balance">
                <div class="label">Solde Consolidé</div>
                <div class="value">{{ number_format($stats->current_balance, 0, ',', ' ') }}</div>
                <div class="label">FCFA</div>
            </div>
        </div>
    </div>
</div>

<!-- Audit Filter -->
<div class="audit-form">
    <form method="GET" action="{{ route('caissier.terminal') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label>Date début</label>
            <input type="date" name="date_start" class="form-control" value="{{ request('date_start') }}">
        </div>
        <div class="col-md-3">
            <label>Date fin</label>
            <input type="date" name="date_end" class="form-control" value="{{ request('date_end') }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-caissier w-100">
                <i class="fas fa-search me-1"></i> Auditer
            </button>
        </div>
        <div class="col-md-3">
            <a href="{{ route('caissier.terminal') }}" class="btn btn-outline-caissier w-100">
                <i class="fas fa-redo me-1"></i> Session Active
            </a>
        </div>
    </form>
</div>

@if($stats->is_audit)
<div class="session-info-bar">
    <span><i class="fas fa-info-circle me-1" style="color:#f59e0b;"></i> Mode Audit : données filtrées par intervalle de dates</span>
</div>
@else
<div class="session-info-bar">
    <span><i class="fas fa-shield-alt me-1" style="color:#00d1b2;"></i> Session #{{ $activeSession->id }} – Ouverte le {{ $activeSession->opened_at->format('d/m/Y H:i') }}</span>
    <a href="{{ route('caissier.session.close.form') }}" class="btn btn-sm" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-size: 0.8rem;">
        <i class="fas fa-lock me-1"></i> Préparer la clôture
    </a>
</div>
@endif

<!-- Transactions Table -->
<div class="tx-table">
    <div class="table-header">
        <h6><i class="fas fa-list me-2"></i>Journal des Opérations ({{ $recentTransactions->total() }})</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('caissier.depot') }}" class="btn btn-sm btn-caissier">
                <i class="fas fa-plus me-1"></i> Nouveau dépôt
            </a>
            <a href="{{ route('caissier.retrait') }}" class="btn btn-sm btn-outline-caissier">
                <i class="fas fa-minus me-1"></i> Nouveau retrait
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Compte</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Date/Heure</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $tx)
                    <tr>
                        <td style="font-family: 'Courier New', monospace; font-size: 0.78rem;">{{ $tx->transaction_reference }}</td>
                        <td>{{ $tx->account?->client?->full_name ?? 'N/A' }}</td>
                        <td style="font-size: 0.8rem;">{{ $tx->account?->account_number ?? '-' }}</td>
                        <td>
                            @php
                                $isIn = in_array($tx->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment']);
                                $accType = $tx->account?->account_type;
                            @endphp
                            <span class="badge-type {{ $isIn ? 'deposit' : 'withdrawal' }}">
                                @if($tx->transaction_type === 'loan_disbursement') DÉCAISSEMENT PRÊT
                                @elseif($tx->transaction_type === 'loan_repayment') REMBOURSEMENT PRÊT
                                @elseif($accType === 'tontine') TONTINE ({{ $isIn ? 'DÉPÔT' : 'RETRAIT' }})
                                @elseif($accType === 'savings') ÉPARGNE ({{ $isIn ? 'DÉPÔT' : 'RETRAIT' }})
                                @else {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }} @endif
                            </span>
                        </td>
                        <td>
                            @if($isIn)
                                <span class="amount-in">+{{ number_format($tx->amount, 0, ',', ' ') }}</span>
                            @else
                                <span class="amount-out">-{{ number_format($tx->amount, 0, ',', ' ') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($tx->payment_method === 'cash')
                                <i class="fas fa-money-bill-wave text-success me-1"></i> Espèces
                            @elseif($tx->payment_method === 'mobile_money')
                                <i class="fas fa-mobile-alt text-info me-1"></i> Mobile Money
                            @else
                                <i class="fas fa-university text-primary me-1"></i> Virement
                            @endif
                        </td>
                        <td style="color: rgba(225,232,237,0.5); font-size: 0.8rem;">
                            {{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('caissier.receipt.print', $tx->id) }}" target="_blank" class="btn btn-sm" style="background: rgba(0, 209, 178, 0.1); color: #00d1b2; border: 1px solid rgba(0, 209, 178, 0.2);" title="Réimprimer le reçu">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 50px; color: rgba(225,232,237,0.3);">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            <p>Aucune transaction pour cette période</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
@if($recentTransactions->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $recentTransactions->appends(request()->query())->links() }}
</div>
@endif

@endsection
