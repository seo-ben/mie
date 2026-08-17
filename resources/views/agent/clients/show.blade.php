@extends('layouts.agent')

@section('title', 'Détails Client')

@section('content')
<div class="py-4 container-fluid">
    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('agent.clients.index') }}" class="mb-2 btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                    <h2 class="mb-0">{{ $client->first_name }} {{ $client->last_name }}</h2>
                    <p class="mb-0 text-muted">Client N° {{ $client->client_number }}</p>
                </div>
                <div>
                    @if($client->kyc_status !== 'approved')
                        <a href="{{ route('agent.clients.edit', $client->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Informations du client -->
    <div class="mb-4 row">
        <div class="col-lg-4">
            <div class="mb-4 shadow card">
                <div class="text-center card-body">
                    @if($client->profile_photo_url)
                        <img src="{{ Storage::url($client->profile_photo_url) }}"
                             alt="Photo de profil"
                             class="mb-3 img-thumbnail rounded-circle"
                             style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="mx-auto mb-3 text-white rounded-circle bg-primary d-flex align-items-center justify-content-center"
                             style="width: 150px; height: 150px; font-size: 3rem;">
                            {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                        </div>
                    @endif

                    <h4>{{ $client->first_name }} {{ $client->last_name }}</h4>
                    <p class="text-muted">{{ $client->client_number }}</p>

                    <div class="mb-3">
                    <div class="mb-3">
                    </div>
                    </div>

                    <hr>

                    <div class="text-start">
                        <p class="mb-2">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <strong>Téléphone:</strong><br>
                            <span class="ms-4">{{ $client->phone }}</span>
                        </p>
                        @if($client->email)
                        <p class="mb-2">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <strong>Email:</strong><br>
                            <span class="ms-4">{{ $client->email }}</span>
                        </p>
                        @endif
                        <p class="mb-2">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            <strong>Date de naissance:</strong><br>
                            <span class="ms-4">{{ \Carbon\Carbon::parse($client->date_of_birth)->format('d/m/Y') }}
                            ({{ \Carbon\Carbon::parse($client->date_of_birth)->age }} ans)</span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-venus-mars text-primary me-2"></i>
                            <strong>Genre:</strong><br>
                            <span class="ms-4">{{ $client->gender == 'male' ? 'Masculin' : ($client->gender == 'female' ? 'Féminin' : 'Autre') }}</span>
                        </p>
                        @if($client->address)
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            <strong>Adresse:</strong><br>
                            <span class="ms-4">{{ $client->address }}</span>
                        </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Documents d'identité -->
            @if(auth()->user()->role !== 'agent_terrain')
            <div class="mb-4 shadow card">
                <div class="py-3 card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-id-card me-2"></i>Pièce d'Identité
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $client->id_type)) }}</p>
                    <p class="mb-2"><strong>Numéro:</strong> {{ $client->id_number }}</p>
                    @if($client->id_issue_date)
                        <p class="mb-2"><strong>Délivré le:</strong> {{ \Carbon\Carbon::parse($client->id_issue_date)->format('d/m/Y') }}</p>
                    @endif
                    @if($client->id_expiry_date)
                        <p class="mb-0"><strong>Expire le:</strong> {{ \Carbon\Carbon::parse($client->id_expiry_date)->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <!-- Résumé financier -->
            <div class="mb-4 row">
                <div class="mb-3 col-md-3">
                    <div class="py-2 shadow card border-left-primary h-100">
                        <div class="card-body">
                            <div class="mb-1 text-xs font-weight-bold text-primary text-uppercase">Épargne</div>
                            <div class="mb-0 text-gray-800 h6 font-weight-bold">
                                {{ number_format($summary['total_savings'], 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 col-md-3">
                    <div class="py-2 shadow card border-left-success h-100">
                        <div class="card-body">
                            <div class="mb-1 text-xs font-weight-bold text-success text-uppercase">Tontine</div>
                            <div class="mb-0 text-gray-800 h6 font-weight-bold">
                                {{ number_format($summary['total_tontine'], 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 col-md-3">
                    <div class="py-2 shadow card border-left-warning h-100">
                        <div class="card-body">
                            <div class="mb-1 text-xs font-weight-bold text-warning text-uppercase">Prêts Actifs</div>
                            <div class="mb-0 text-gray-800 h6 font-weight-bold">
                                {{ number_format($summary['active_loans_amount'], 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 col-md-3">
                    <div class="py-2 shadow card border-left-info h-100">
                        <div class="card-body">
                            <div class="mb-1 text-xs font-weight-bold text-info text-uppercase">Comptes</div>
                            <div class="mb-0 text-gray-800 h6 font-weight-bold">
                                {{ $summary['active_accounts'] }}/{{ $summary['total_accounts'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="accounts-tab" data-bs-toggle="tab"
                            data-bs-target="#accounts" type="button" role="tab">
                        <i class="fas fa-wallet me-2"></i>Comptes ({{ $client->accounts->count() }})
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="info-tab" data-bs-toggle="tab"
                            data-bs-target="#info" type="button" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Informations
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="clientTabsContent">
                <!-- Onglet Comptes -->
                <div class="tab-pane fade show active" id="accounts" role="tabpanel">
                    <div class="shadow card">
                        <div class="card-body">
                            @if($client->accounts->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>N° Compte</th>
                                                <th>Type</th>
                                                <th>Solde</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($client->accounts as $account)
                                            <tr>
                                                <td><strong>{{ $account->account_number }}</strong></td>
                                                <td>
                                                    @if($account->account_type == 'savings')
                                                        <span class="badge bg-primary">Épargne</span>
                                                    @elseif($account->account_type == 'tontine')
                                                        <span class="badge bg-success">Tontine</span>
                                                    @else
                                                        <span class="badge bg-info">{{ ucfirst($account->account_type) }}</span>
                                                    @endif
                                                </td>
                                                <td class="fw-bold">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</td>
                                                <td>
                                                    <a href="{{ route('agent.accounts.show', $account->id) }}"
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="py-4 text-center">
                                    <i class="mb-3 fas fa-wallet fa-3x text-muted"></i>
                                    <p class="text-muted">Aucun compte pour ce client</p>
                                    <a href="{{ route('agent.accounts.create', $client->id) }}"
                                       class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Créer un Compte
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Onglet Transactions -->
                <div class="tab-pane fade" id="transactions" role="tabpanel">
                    <div class="shadow card">
                        <div class="card-body">
                            @if($recentTransactions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>N° Transaction</th>
                                                <th>Type</th>
                                                <th>Montant</th>
                                                <th>Compte</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentTransactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                                <td><small>{{ $transaction->transaction_number }}</small></td>
                                                <td>
                                                    @if($transaction->type == 'deposit')
                                                        <span class="badge bg-success">Dépôt</span>
                                                    @elseif($transaction->type == 'withdrawal')
                                                        <span class="badge bg-warning">Retrait</span>
                                                    @else
                                                        <span class="badge bg-info">{{ ucfirst($transaction->type) }}</span>
                                                    @endif
                                                </td>
                                                <td class="fw-bold">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</td>
                                                <td><small>{{ $transaction->account->account_number }}</small></td>
                                                <td>
                                                    @if($transaction->status == 'completed')
                                                        <span class="badge bg-success">Complété</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="{{ route('agent.transactions.index', ['client_id' => $client->id]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Voir toutes les transactions
                                    </a>
                                </div>
                            @else
                                <div class="py-4 text-center">
                                    <i class="mb-3 fas fa-exchange-alt fa-3x text-muted"></i>
                                    <p class="text-muted">Aucune transaction pour ce client</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Onglet Prêts -->
                <div class="tab-pane fade" id="loans" role="tabpanel">
                    <div class="shadow card">
                        <div class="card-body">
                            @if($client->loans->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>N° Prêt</th>
                                                <th>Montant</th>
                                                <th>Reste à Payer</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($client->loans as $loan)
                                            <tr>
                                                <td><strong>{{ $loan->loan_number }}</strong></td>
                                                <td>{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</td>
                                                <td class="fw-bold text-danger">{{ number_format($loan->outstanding_balance, 0, ',', ' ') }} FCFA</td>
                                                <td>
                                                    @if($loan->status == 'active')
                                                        <span class="badge bg-success">Actif</span>
                                                    @elseif($loan->status == 'completed')
                                                        <span class="badge bg-info">Complété</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($loan->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $loan->created_at->format('d/m/Y') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="py-4 text-center">
                                    <i class="mb-3 fas fa-hand-holding-usd fa-3x text-muted"></i>
                                    <p class="text-muted">Aucun prêt pour ce client</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Onglet Informations -->
                <div class="tab-pane fade" id="info" role="tabpanel">
                    <div class="shadow card">
                        <div class="card-body">
                            <h6 class="mb-3 font-weight-bold">Informations d'Inscription</h6>
                            <p><strong>Enregistré par:</strong> {{ $client->registeredBy->name ?? 'N/A' }}</p>
                            <p><strong>Agence:</strong> {{ $client->agency->name ?? 'N/A' }}</p>
                            <p><strong>Date d'inscription:</strong> {{ $client->created_at->format('d/m/Y à H:i') }}</p>
                            <p><strong>Canal:</strong> {{ ucfirst(str_replace('_', ' ', $client->registration_channel)) }}</p>

                            @if(auth()->user()->role !== 'agent_terrain')
                                @if($client->kyc_approved_at)
                                    <hr>
                                    <h6 class="mb-3 font-weight-bold">Validation KYC</h6>
                                    <p><strong>Statut:</strong>
                                        @switch($client->kyc_status)
                                            @case('approved')
                                                <span class="badge bg-success">Approuvé</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejeté</span>
                                                @break
                                            @default
                                                <span class="badge bg-warning">En attente</span>
                                        @endswitch
                                    </p>
                                    @if($client->kyc_approved_at)
                                        <p><strong>Date d'approbation:</strong> {{ \Carbon\Carbon::parse($client->kyc_approved_at)->format('d/m/Y à H:i') }}</p>
                                        <p><strong>Approuvé par:</strong> {{ $client->approvedBy->name ?? 'N/A' }}</p>
                                    @endif
                                    @if($client->rejection_reason)
                                        <p><strong>Raison du rejet:</strong> {{ $client->rejection_reason }}</p>
                                    @endif
                                @endif
                            @endif

                            @if($client->occupation || $client->employer)
                                <hr>
                                <h6 class="mb-3 font-weight-bold">Informations Professionnelles</h6>
                                @if($client->occupation)
                                    <p><strong>Profession:</strong> {{ $client->occupation }}</p>
                                @endif
                                @if($client->employer)
                                    <p><strong>Employeur:</strong> {{ $client->employer }}</p>
                                @endif
                                @if($client->monthly_income)
                                    <p><strong>Revenu mensuel:</strong> {{ number_format($client->monthly_income, 0, ',', ' ') }} FCFA</p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
</style>
@endpush
