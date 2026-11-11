@extends('layouts.cashier')

@section('title', 'Détails de l\'Agent ' . $agent->full_name)
@section('page-title', 'Agent de Terrain : ' . $agent->full_name)
@section('page-subtitle', 'Supervision des collectes et portefeuille clients')

@section('content')
<div class="space-y-4">

    <!-- BARRE D'ENTÊTE -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <a href="{{ route('caissier.field-agents.index') }}" class="btn btn-light border fw-bold text-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour aux équipes
        </a>

        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success fs-7 px-3 py-2 border border-success-subtle">
                <i class="fas fa-calendar-day me-1"></i> Collecte Aujourd'hui : <strong>{{ number_format($todayCollected, 0, ',', ' ') }} FCFA</strong>
            </span>
        </div>
    </div>

    <!-- FICHE IDENTITÉ AGENT -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="white-card">
                <div class="text-center pb-3 border-bottom mb-3">
                    <div class="user-avatar-circle bg-primary text-white mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        {{ strtoupper(substr($agent->first_name, 0, 1)) }}{{ strtoupper(substr($agent->last_name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $agent->full_name }}</h5>
                    <span class="badge bg-light text-primary border">Agent Terrain (Collecteur)</span>
                </div>

                <div class="space-y-2 fs-7">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Nom d'utilisateur :</span>
                        <span class="fw-bold text-dark">{{ $agent->username }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Téléphone :</span>
                        <span class="fw-bold text-dark">{{ $agent->phone ?? 'Non renseigné' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Email :</span>
                        <span class="fw-bold text-dark">{{ $agent->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Agence :</span>
                        <span class="fw-bold text-dark">{{ $agent->agency->name ?? 'Agence Centrale' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Clients enregistrés :</span>
                        <span class="fw-bold text-dark badge bg-light text-dark border">{{ $clients->total() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- JOURNAL DES DERNIÈRES COLLECTES DE L'AGENT -->
        <div class="col-12 col-lg-8">
            <div class="white-card p-0 overflow-hidden">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-receipt text-success me-2"></i>Dernières Collectes Encaissées sur le Terrain</h6>
                    <small class="text-muted">{{ $transactions->total() }} opération(s)</small>
                </div>

                <div class="table-responsive">
                    <table class="table-corporate mb-0">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Client & Compte</th>
                                <th>Type</th>
                                <th>Montant</th>
                                <th>Réf. Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-8">{{ $tx->created_at->format('d/m/Y H:i') }}</div>
                                        <small class="text-muted">{{ $tx->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $tx->account->client->full_name ?? 'Client inconnu' }}</div>
                                        <small class="text-muted">{{ $tx->account->account_number ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success fs-8">
                                            {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-black text-dark fs-7">{{ number_format($tx->amount, 0, ',', ' ') }} FCFA</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('caissier.receipt.print', $tx->id) }}" target="_blank" class="btn btn-sm btn-light border fs-8">
                                            <i class="fas fa-print me-1"></i> {{ $tx->transaction_reference }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Aucune collecte récente enregistrée pour cet agent.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top bg-light">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- CLIENTS ENREGISTRÉS PAR CET AGENT -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-light">
            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-users text-primary me-2"></i>Portefeuille des Adhérents Enregistrés</h6>
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>N° Client</th>
                        <th>Nom & Prénoms</th>
                        <th>Téléphone</th>
                        <th>Comptes Actifs</th>
                        <th>Statut KYC</th>
                        <th class="text-end">Actions Guichet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $c)
                        <tr>
                            <td><span class="badge bg-light text-dark border">{{ $c->client_number }}</span></td>
                            <td class="fw-bold text-dark">{{ $c->full_name }}</td>
                            <td>{{ $c->phone }}</td>
                            <td>
                                @foreach($c->accounts as $acc)
                                    <span class="badge bg-light text-dark border me-1">
                                        {{ strtoupper($acc->account_type) }} : {{ number_format($acc->balance, 0, ',', ' ') }} F
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                @if($c->kyc_status === 'approved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">KYC Conforme</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ ucfirst($c->kyc_status) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('caissier.depot') }}?query={{ $c->client_number }}" class="btn btn-sm btn-outline-success fw-bold rounded-2">
                                    <i class="fas fa-plus-circle me-1"></i> Dépôt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Aucun client rattaché à cet agent.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top bg-light">
            {{ $clients->links() }}
        </div>
    </div>
</div>
@endsection

