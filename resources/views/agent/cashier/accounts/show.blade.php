@extends('layouts.cashier')

@section('title', 'Détails du Compte')
@section('page-title', 'Compte Tontine')
@section('page-subtitle', $account->account_number)

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4 mb-4">
        <!-- Informations Compte -->
        <div class="col-lg-4">
            <div class="card glass-card h-100 border-0 shadow-lg position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="fas fa-wallet fa-6x text-teal-accent"></i>
                </div>
                <div class="card-body p-4 position-relative">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto mb-3">
                            <i class="fas fa-wallet text-teal-accent"></i>
                        </div>
                        <h4 class="text-white fw-bold mb-1">{{ $account->account_number }}</h4>
                        <span class="badge bg-blur-teal text-teal-accent border border-teal-soft fs-6">{{ $account->client->first_name }} {{ $account->client->last_name }}</span>
                    </div>

                    <ul class="list-group list-group-flush custom-list">
                        <li class="list-group-item">
                            <i class="fas fa-money-bill-wave me-3 text-teal-accent"></i> 
                            @if($account->tontineAccount)
                                Mise : <span class="fw-bold">{{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} F</span>
                                <small class="text-white-50 ms-1">({{ ucfirst($account->tontineAccount->payment_frequency) }})</small>
                            @else
                                Solde : <span class="fw-bold">{{ number_format($account->balance, 0, ',', ' ') }} F</span>
                            @endif
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-chart-line me-3 text-teal-accent"></i> 
                            Statut : 
                            @if($account->status == 'active')
                                <span class="text-success fw-bold">Actif</span>
                            @elseif($account->status == 'suspended')
                                <span class="text-warning fw-bold">Suspendu</span>
                            @else
                                <span class="text-danger fw-bold">{{ $account->status }}</span>
                            @endif
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-calendar-alt me-3 text-teal-accent"></i> 
                            Créé le {{ $account->created_at->format('d/m/Y') }}
                        </li>
                    </ul>
                    
                    <div class="mt-4 text-center">
                        @if($account->status == 'active')
                            <a href="{{ route('caissier.accounts.deposit.form', $account->id) }}" class="btn btn-primary-gradient w-100 py-2 fw-bold shadow-brand">
                                <i class="fas fa-arrow-down me-2"></i> FAIRE UN DÉPÔT
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions du compte -->
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-lg h-100">
                <div class="card-header bg-transparent border-bottom border-white-5 p-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-bold"><i class="fas fa-list me-2 text-teal-accent"></i>Transactions Récentes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Type</th>
                                    <th>Montant (FCFA)</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($account->transactions ?? [] as $transaction)
                                <tr>
                                    <td class="text-white-50">
                                        {{ $transaction->created_at->format('d/m/Y') }}<br>
                                        <small>{{ $transaction->created_at->format('H:i') }}</small>
                                    </td>
                                    <td class="text-teal-accent fw-bold">{{ $transaction->transaction_reference ?? $transaction->transaction_number }}</td>
                                    <td>
                                        @if(in_array($transaction->transaction_type ?? $transaction->type, ['deposit', 'savings_deposit', 'tontine_deposit']))
                                            <span class="badge bg-blur-teal text-teal-accent border border-teal-soft"><i class="fas fa-arrow-down me-1"></i>Dépôt</span>
                                        @elseif(in_array($transaction->transaction_type ?? $transaction->type, ['withdrawal', 'payout']))
                                            <span class="badge bg-danger-soft text-danger"><i class="fas fa-arrow-up me-1"></i>Retrait</span>
                                        @elseif(($transaction->transaction_type ?? $transaction->type) == 'fee')
                                            <span class="badge bg-warning-soft text-warning"><i class="fas fa-tag me-1"></i>Frais</span>
                                        @else
                                            <span class="badge bg-info-soft text-info"><i class="fas fa-exchange-alt me-1"></i>{{ $transaction->transaction_type ?? $transaction->type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($transaction->transaction_type ?? $transaction->type, ['withdrawal', 'payout', 'fee']))
                                            <span class="text-danger fw-bold">- {{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                                        @else
                                            <span class="text-teal-accent fw-bold">+ {{ number_format($transaction->amount, 0, ',', ' ') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->status == 'completed')
                                            <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                        @else
                                            <span class="text-warning"><i class="fas fa-clock"></i></span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-white-50">Aucune transaction trouvée.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
    .user-avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(0, 209, 178, 0.2), rgba(0, 180, 216, 0.2));
        color: #00d1b2;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        border: 2px solid rgba(0, 209, 178, 0.3);
    }
    .custom-list .list-group-item {
        background: transparent;
        border-color: rgba(255, 255, 255, 0.05);
        color: #e1e8ed;
        padding: 15px 0;
    }
    .text-teal-accent { color: #00d1b2; }
    .bg-blur-teal { background: rgba(0, 209, 178, 0.1); }
    .border-teal-soft { border-color: rgba(0, 209, 178, 0.3) !important; }

    .custom-table { color: white; }
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
    .shadow-brand {
        box-shadow: 0 8px 15px rgba(0, 209, 178, 0.2);
    }
</style>
@endsection
