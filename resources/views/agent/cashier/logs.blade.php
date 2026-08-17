@extends('layouts.cashier')

@section('title', 'Journal d\'Activité Caissier')
@section('page-title', 'Mes Opérations')
@section('page-subtitle', 'Historique personnel des actions effectuées')

@push('styles')
<style>
    .log-container {
        background: #1a2332;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .log-table {
        margin-bottom: 0;
    }

    .log-table td {
        padding: 18px 20px;
        color: #e2e8f0; /* Off-white for better visibility */
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        vertical-align: middle;
        font-size: 0.9rem;
        background: transparent;
    }

    .log-table th {
        background: rgba(0, 0, 0, 0.2) !important;
        color: #00d1b2 !important; /* Teal for header visibility */
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
        padding: 15px 20px;
        border: none;
    }

    .action-badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .action-deposit { background: rgba(0, 209, 178, 0.1); color: #00d1b2; }
    .action-withdrawal { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .action-loan_disbursement { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .action-session_open { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .action-session_close { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

    .ip-address {
        font-family: 'Courier New', monospace;
        color: rgba(225, 232, 237, 0.3);
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')

<div class="log-container">
    <div class="table-responsive">
        <table class="table log-table">
            <thead>
                <tr>
                    <th>Date & Heure</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Identité Client / Entité</th>
                    <th>Adresse IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>
                        <span class="d-block text-white">{{ $log->created_at->format('d/m/Y') }}</span>
                        <span class="text-white-50 small">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td>
                        <span class="action-badge action-{{ $log->action }}">
                            {{ strtoupper(str_replace('_', ' ', $log->action)) }}
                        </span>
                    </td>
                    <td class="text-white-50">
                        {{ $log->description }}
                    </td>
                    <td>
                        <small class="text-white-50 d-block">{{ str_replace('App\\Models\\', '', $log->entity_type) }}</small>
                        <span class="fw-bold">ID: {{ $log->entity_id }}</span>
                    </td>
                    <td>
                        <span class="ip-address">{{ $log->ip_address }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-white-50">
                        <i class="fas fa-history fa-3x mb-3 opacity-25"></i>
                        <p>Aucune action enregistrée pour le moment.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 d-flex justify-content-center">
    {{ $logs->links() }}
</div>

@endsection
