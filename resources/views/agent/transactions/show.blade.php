@extends('layouts.agent')

@section('title', 'Détails Transaction')

@section('content')
<div class="px-4 py-4 container-fluid">
    <div class="mb-4 row align-items-center">
        <div class="col-md-6">
            <h1 class="mb-1 h3 fw-bold">Détails Transaction</h1>
            <p class="mb-0 text-muted">Référence : {{ $transaction->transaction_reference }}</p>
        </div>
        <div class="text-end col-md-6">
            <a href="{{ route('agent.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
            </a>
            <a href="{{ route('agent.transactions.receipt', $transaction->id) }}" class="btn btn-primary" target="_blank">
                <i class="fas fa-print me-1"></i> Imprimer Reçu
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="shadow-sm border-0 card h-100">
                <div class="py-3 bg-white card-header border-bottom-0">
                    <h5 class="mb-0 fw-bold">Informations Transaction</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4 d-flex justify-content-between">
                        <div>
                            <span class="text-muted small d-block uppercase">Date & Heure</span>
                            <span class="fw-bold">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block uppercase">Type de transaction</span>
                            <span class="badge bg-primary px-3">{{ strtoupper($transaction->transaction_type) }}</span>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted small d-block mb-1">Montant</span>
                                <h2 class="fw-bold mb-0 {{ in_array($transaction->transaction_type, ['withdrawal', 'loan_disbursement']) ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($transaction->amount, 0, ',', ' ') }} CFA
                                </h2>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3">
                                <span class="text-muted small d-block mb-1">Status</span>
                                <span class="badge bg-success bg-opacity-10 text-success p-2">
                                    <i class="fas fa-check-circle me-1"></i> {{ strtoupper($transaction->status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="text-muted small d-block uppercase mb-2">Description / Notes</span>
                        <div class="p-3 bg-light rounded-3 italic">
                            {{ $transaction->description ?? 'Aucune description fournie.' }}
                        </div>
                    </div>

                    <div class="p-3 rounded-3 border">
                        <span class="text-muted small d-block uppercase mb-2">Historique des soldes (Compte)</span>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small d-block">Avant</span>
                                <span class="fw-bold">{{ number_format($transaction->balance_before, 0, ',', ' ') }} CFA</span>
                            </div>
                            <i class="fas fa-long-arrow-alt-right text-muted fa-lg mx-3"></i>
                            <div class="text-end">
                                <span class="text-muted small d-block">Après</span>
                                <span class="fw-bold text-primary">{{ number_format($transaction->balance_after, 0, ',', ' ') }} CFA</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="shadow-sm border-0 card mb-4">
                <div class="py-3 bg-white card-header border-bottom-0">
                    <h5 class="mb-0 fw-bold">Client & Compte</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <div class="avatar avatar-xl bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                        <h5 class="fw-bold mb-1">{{ $transaction->account->client->full_name }}</h5>
                        <p class="text-muted small">{{ $transaction->account->client->client_number }}</p>
                    </div>
                    <hr>
                    <div class="space-y-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">N° de Compte</span>
                            <span class="fw-bold">{{ $transaction->account->account_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Type de compte</span>
                            <span class="badge bg-secondary px-2">{{ strtoupper($transaction->account->account_type) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="shadow-sm border-0 card">
                <div class="py-3 bg-white card-header border-bottom-0">
                    <h5 class="mb-0 fw-bold">Opérateur</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-info bg-opacity-10 text-info rounded-circle me-3 d-flex align-items-center justify-center shadow-sm" style="width: 40px; height: 40px;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <span class="fw-bold d-block">{{ $transaction->processedBy->full_name ?? 'Système' }}</span>
                            <span class="text-muted small">Agent / Caissier</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
