@extends('layouts.cashier')

@section('title', 'Dashboard Caissier')
@section('page-title', 'Tableau de Bord')
@section('page-subtitle', now()->translatedFormat('l d F Y'))

@push('styles')
<style>
    .stat-card {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 16px;
        padding: 22px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--card-accent, #00d1b2), transparent);
    }

    .stat-card:hover {
        border-color: rgba(0, 209, 178, 0.3);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .stat-card .stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: white;
        margin-top: 12px;
    }

    .stat-card .stat-label {
        font-size: 0.8rem;
        color: rgba(225, 232, 237, 0.5);
        margin-top: 4px;
    }

    .stat-card .stat-change {
        font-size: 0.75rem;
        margin-top: 6px;
    }

    .action-card {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        display: block;
    }

    .action-card:hover {
        border-color: rgba(0, 209, 178, 0.4);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        color: inherit;
    }

    .action-card .action-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 15px;
    }

    .action-card h6 { color: white; font-weight: 600; margin-bottom: 5px; }
    .action-card p { color: rgba(225, 232, 237, 0.5); font-size: 0.8rem; margin: 0; }

    .session-banner {
        background: linear-gradient(135deg, rgba(0, 209, 178, 0.15), rgba(0, 180, 216, 0.1));
        border: 1px solid rgba(0, 209, 178, 0.2);
        border-radius: 16px;
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .session-banner .session-info h5 { color: white; font-weight: 600; margin-bottom: 3px; }
    .session-banner .session-info span { color: rgba(225, 232, 237, 0.6); font-size: 0.85rem; }

    .recent-table {
        background: #1a2332;
        border: 1px solid rgba(0, 209, 178, 0.1);
        border-radius: 16px;
        overflow: hidden;
    }

    .recent-table .table-header {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(0, 209, 178, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .recent-table .table-header h6 { color: white; font-weight: 600; margin: 0; }

    .recent-table table {
        width: 100%;
        margin: 0;
    }

    .recent-table table th {
        color: rgba(225, 232, 237, 0.5);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 18px;
        border-bottom: 1px solid rgba(0, 209, 178, 0.08);
        background: transparent;
    }

    .recent-table table td {
        padding: 12px 18px;
        border-bottom: 1px solid rgba(0, 209, 178, 0.05);
        color: rgba(225, 232, 237, 0.8);
        font-size: 0.85rem;
        vertical-align: middle;
    }

    .badge-deposit {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-withdrawal {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .amount-in { color: #10b981; font-weight: 600; }
    .amount-out { color: #ef4444; font-weight: 600; }
</style>
@endpush

@section('content')

<!-- Session Banner -->
<div class="session-banner">
    <div class="session-info">
        <h5><i class="fas fa-shield-alt me-2"></i>Session #{{ $activeSession->id }} – Active</h5>
        <span>Ouverte le {{ $activeSession->opened_at->translatedFormat('d/m/Y à H:i') }} • Solde d'ouverture : {{ number_format($activeSession->opening_balance, 0, ',', ' ') }} FCFA</span>
    </div>
    <div>
        <a href="{{ route('caissier.session.close.form') }}" class="btn btn-sm" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);">
            <i class="fas fa-lock me-1"></i> Préparer la clôture
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card" style="--card-accent: #10b981;">
            <div class="stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-value">{{ number_format($todayStats->total_in ?? 0, 0, ',', ' ') }}</div>
            <div class="stat-label">Encaissements (FCFA)</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card" style="--card-accent: #ef4444;">
            <div class="stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-value">{{ number_format($todayStats->total_out ?? 0, 0, ',', ' ') }}</div>
            <div class="stat-label">Décaissements (FCFA)</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card" style="--card-accent: #00d1b2;">
            <div class="stat-icon" style="background: rgba(0,209,178,0.12); color: #00d1b2;">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="stat-value">{{ number_format(($activeSession->opening_balance + ($todayStats->total_in ?? 0) - ($todayStats->total_out ?? 0)), 0, ',', ' ') }}</div>
            <div class="stat-label">Solde en Caisse (FCFA)</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card" style="--card-accent: #f59e0b;">
            <div class="stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="stat-value">{{ $todayStats->tx_count ?? 0 }}</div>
            <div class="stat-label">Opérations ce jour</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<h6 class="mb-3" style="color: rgba(225,232,237,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Actions Rapides</h6>
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('caissier.depot') }}" class="action-card">
            <div class="action-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-arrow-down"></i>
            </div>
            <h6>Encaissement</h6>
            <p>Dépôt épargne, tontine</p>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('caissier.retrait') }}" class="action-card">
            <div class="action-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-arrow-up"></i>
            </div>
            <h6>Décaissement</h6>
            <p>Retrait espèces</p>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('caissier.clients.create') }}" class="action-card">
            <div class="action-icon" style="background: rgba(99,102,241,0.12); color: #6366f1;">
                <i class="fas fa-user-plus"></i>
            </div>
            <h6>Nouveau Client</h6>
            <p>Inscription au guichet</p>
        </a>
    </div>
    <div class="col-lg-3 col-md-6">
        <a href="{{ route('caissier.terminal') }}" class="action-card">
            <div class="action-icon" style="background: rgba(0,209,178,0.12); color: #00d1b2;">
                <i class="fas fa-cash-register"></i>
            </div>
            <h6>Terminal de Caisse</h6>
            <p>Journal & audit</p>
        </a>
    </div>
</div>

<!-- Recent Transactions -->
<div class="recent-table">
    <div class="table-header">
        <h6><i class="fas fa-history me-2"></i>Dernières Opérations</h6>
        <a href="{{ route('caissier.terminal') }}" style="color: #00d1b2; font-size: 0.85rem; text-decoration: none;">
            Voir tout <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Heure</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $tx)
                    <tr>
                        <td style="font-family: 'Courier New', monospace; font-size: 0.8rem;">{{ $tx->transaction_reference }}</td>
                        <td>{{ $tx->account?->client?->full_name ?? 'N/A' }}</td>
                        <td>
                            @if(in_array($tx->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment']))
                                <span class="badge-deposit">{{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}</span>
                            @else
                                <span class="badge-withdrawal">{{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}</span>
                            @endif
                        </td>
                        <td>
                            @if(in_array($tx->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment']))
                                <span class="amount-in">+{{ number_format($tx->amount, 0, ',', ' ') }}</span>
                            @else
                                <span class="amount-out">-{{ number_format($tx->amount, 0, ',', ' ') }}</span>
                            @endif
                        </td>
                        <td style="color: rgba(225,232,237,0.5);">{{ $tx->processed_at?->format('H:i') ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('caissier.receipt.print', $tx->id) }}" target="_blank" class="btn btn-sm" style="background: rgba(0, 209, 178, 0.1); color: #00d1b2; border: 1px solid rgba(0, 209, 178, 0.2);" title="Imprimer le reçu">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 40px; color: rgba(225,232,237,0.3);">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Aucune opération pour cette session
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
