@extends('layouts.cashier')

@section('title', 'Détails du Client')
@section('page-title', 'Profil Client')
@section('page-subtitle', $client->first_name . ' ' . $client->last_name)

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4 mb-4">
        <!-- Informations Client -->
        <div class="col-lg-4">
            <div class="card glass-card h-100 border-0 shadow-lg position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-3 opacity-25">
                    <i class="fas fa-id-card fa-6x text-teal-accent"></i>
                </div>
                <div class="card-body p-4 position-relative">
                    <div class="text-center mb-4">
                        <div class="user-avatar-large mx-auto mb-3">
                            {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                        </div>
                        <h4 class="text-white fw-bold mb-1">{{ $client->first_name }} {{ $client->last_name }}</h4>
                        <span class="badge bg-blur-teal text-teal-accent border border-teal-soft fs-6">{{ $client->client_number }}</span>
                    </div>

                    <ul class="list-group list-group-flush custom-list">
                        <li class="list-group-item">
                            <i class="fas fa-phone me-3 text-teal-accent"></i> {{ $client->phone }}
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-map-marker-alt me-3 text-teal-accent"></i> {{ $client->address }}
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-calendar-alt me-3 text-teal-accent"></i> Inscrit le {{ $client->created_at->format('d/m/Y') }}
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-shield-alt me-3 text-teal-accent"></i> KYC : 
                            @if($client->kyc_status == 'approved')
                                <span class="text-success fw-bold">Approuvé</span>
                            @else
                                <span class="text-warning fw-bold">En attente</span>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Comptes du client -->
        <div class="col-lg-8">
            <div class="card glass-card border-0 shadow-lg h-100">
                <div class="card-header bg-transparent border-bottom border-white-5 p-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-bold"><i class="fas fa-wallet me-2 text-teal-accent"></i>Comptes Tontine</h5>
                    <a href="{{ route('caissier.accounts.create', $client->id) }}" class="btn btn-outline-white btn-sm px-3 rounded-pill">
                        <i class="fas fa-plus me-1"></i> Nouveau
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>N° Compte</th>
                                    <th>Mise Régulière</th>
                                    <th>Épargné / Total</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($client->accounts as $account)
                                <tr>
                                    <td class="text-white fw-bold">{{ $account->account_number }}</td>
                                    <td>
                                        @if($account->tontineAccount)
                                            <span class="text-teal-accent fw-bold">{{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} F</span>
                                            <small class="text-white-50 d-block">{{ ucfirst($account->tontineAccount->payment_frequency) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->tontineAccount)
                                            @php
                                                $tontine = $account->tontineAccount;
                                                $percentage = $tontine->total_expected > 0 ? min(100, ($tontine->total_paid / $tontine->total_expected) * 100) : 0;
                                            @endphp
                                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.75rem;">
                                                <span class="text-white-50">{{ number_format($tontine->total_paid, 0, ',', ' ') }}</span>
                                                <span class="text-teal-accent fw-bold">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1);">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%; background-color: #00d1b2;"></div>
                                            </div>
                                        @else
                                            <span class="text-white-50">{{ number_format($account->balance, 0, ',', ' ') }} F</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->status == 'active')
                                            <span class="badge bg-success-soft text-success">Actif</span>
                                        @elseif($account->status == 'suspended')
                                            <span class="badge bg-warning-soft text-warning">En attente</span>
                                        @else
                                            <span class="badge bg-danger-soft text-danger">{{ $account->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('caissier.accounts.show', $account->id) }}" class="btn btn-sm action-btn">
                                            <i class="fas fa-eye text-teal-accent"></i>
                                        </a>
                                        @if($account->status == 'active')
                                            <a href="{{ route('caissier.accounts.deposit.form', $account->id) }}" class="btn btn-sm action-btn border-info">
                                                <i class="fas fa-arrow-down text-info"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-white-50">Aucun compte actif.</td>
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
        font-weight: bold;
        font-size: 2rem;
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
    .bg-success-soft { background: rgba(0, 255, 170, 0.1); }
    .bg-warning-soft { background: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background: rgba(255, 0, 0, 0.1); }

    .action-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        margin-right: 5px;
    }
    .action-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }
    .btn-outline-white {
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    .btn-outline-white:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
</style>
@endsection
