@extends('layouts.cashier')

@section('title', 'Journal d\'Activité Caissier')
@section('page-title', 'Journal des Flux')
@section('page-subtitle', 'Historique d\'audit des écritures et transactions enregistrées au guichet')

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Journal d'Audit & Flux de Caisse</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Journal des Écritures</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-cash-register me-1 text-primary"></i> Terminal de Caisse
            </a>
            <a href="{{ route('caissier.reports') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-chart-pie me-1 text-emerald-600"></i> Rapports Financiers
            </a>
        </div>
    </div>

    <!-- TABLEAU D'AUDIT CORPORATIF BLANC -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-clock-rotate-left text-primary"></i>
                Historique des Actions et Mouvements
            </h6>
            <span class="badge bg-light text-dark border px-2 py-1 rounded-2 fs-8 font-monospace">
                Audit Trail Sécurisé
            </span>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Type d'Action</th>
                        <th>Description de l'Événement</th>
                        <th>Entité Concernée</th>
                        <th>Adresse IP / Empreinte</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark fs-8">{{ $log->created_at->format('d/m/Y') }}</div>
                            <small class="text-muted fs-8 font-monospace">{{ $log->created_at->format('H:i:s') }}</small>
                        </td>
                        <td>
                            @php
                                $actionName = strtolower($log->action);
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                if (str_contains($actionName, 'deposit') || str_contains($actionName, 'supply') || str_contains($actionName, 'open')) {
                                    $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                } elseif (str_contains($actionName, 'withdraw') || str_contains($actionName, 'discharge') || str_contains($actionName, 'reversal')) {
                                    $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                } elseif (str_contains($actionName, 'transfer') || str_contains($actionName, 'loan')) {
                                    $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                } elseif (str_contains($actionName, 'close')) {
                                    $badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }} px-2 py-1 rounded-pill fs-8 fw-bold">
                                {{ strtoupper(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td>
                            <div class="text-dark fs-8">{{ $log->description }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 rounded-2 fs-8">
                                {{ str_replace('App\\Models\\', '', $log->entity_type) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted font-monospace fs-8">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <i class="fas fa-clock-rotate-left fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">Aucune action enregistrée pour le moment</h6>
                                <p class="fs-8 text-muted mb-0">Toutes les opérations de caisse et événements guichet seront consignés ici.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="p-3 border-top bg-light d-flex justify-content-end">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
