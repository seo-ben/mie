@extends('layouts.cashier')

@section('title', 'Dossier Client - ' . $client->full_name)
@section('page-title', 'Dossier Adhérent 360°')
@section('page-subtitle', $client->full_name . ' • N° ' . $client->client_number)

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">{{ $client->full_name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.clients.index') }}" class="text-decoration-none text-muted">Clients</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Fiche Dossier 360°</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('caissier.loans.apply.form', ['client_id' => $client->id]) }}" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm">
                <i class="fas fa-file-circle-plus me-1"></i> Demande de Prêt
            </a>
            @if($client->canOpenSavingsAccount())
                <a href="{{ route('caissier.accounts.create', ['client' => $client->id, 'type' => 'savings']) }}" class="btn btn-primary fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm text-white">
                    <i class="fas fa-vault me-1"></i> Ouvrir Compte Épargne
                </a>
            @endif
            <a href="{{ route('caissier.accounts.create', ['client' => $client->id, 'type' => 'tontine']) }}" class="btn btn-light border fw-bold px-3 py-2 rounded-3 fs-7 text-dark">
                <i class="fas fa-plus me-1 text-emerald-600"></i> Compte Tontine
            </a>
            <a href="{{ route('caissier.clients.index') }}" class="btn btn-light border fw-bold px-3 py-2 rounded-3 fs-7 text-muted">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>

    <!-- CARTE D'IDENTITÉ & 4 KPIS FINANCIERS -->
    <div class="row g-3 mb-4">
        <!-- FICHE D'IDENTITÉ -->
        <div class="col-12 col-xl-4">
            <div class="white-card h-100 p-4">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.25rem;">
                        {{ strtoupper(substr($client->first_name, 0, 1)) }}{{ strtoupper(substr($client->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0 fs-6">{{ $client->full_name }}</h5>
                        <span class="badge bg-light text-dark border font-monospace fs-8">{{ $client->client_number }}</span>
                    </div>
                </div>

                <div class="space-y-2 fs-8 text-muted">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><i class="fas fa-phone text-muted me-2"></i>Téléphone</span>
                        <strong class="text-dark font-monospace">{{ $client->phone }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><i class="fas fa-map-marker-alt text-muted me-2"></i>Adresse</span>
                        <strong class="text-dark">{{ $client->address ?: 'Non renseignée' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><i class="fas fa-briefcase text-muted me-2"></i>Profession</span>
                        <strong class="text-dark">{{ $client->profession ?: 'Commerçant(e)' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span><i class="fas fa-calendar-alt text-muted me-2"></i>Adhésion</span>
                        <strong class="text-dark">{{ $client->created_at->format('d/m/Y') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span><i class="fas fa-shield-alt text-muted me-2"></i>Statut KYC</span>
                        <div>
                            @if($client->kyc_status == 'approved')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold">
                                    <i class="fas fa-circle-check me-1"></i> Conforme (Validé)
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fw-bold">
                                    <i class="fas fa-clock me-1"></i> En Attente
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($client->kyc_status != 'approved')
                    <div class="mt-3 pt-3 border-top">
                        <form action="{{ route('caissier.clients.approve-kyc', $client->id) }}" method="POST">
                            @csrf
                            <button type="button" class="btn btn-success fw-bold w-100 py-2 rounded-3 fs-7"
                                    data-confirm="Confirmez-vous que les pièces d'identité et justificatifs de l'adhérent <strong>{{ addslashes($client->full_name) }}</strong> sont conformes aux exigences KYC ?"
                                    data-confirm-title="Validation KYC Adhérent"
                                    data-confirm-subtitle="Dossier N° {{ $client->client_number }}"
                                    data-confirm-btn="Valider le KYC sur Place"
                                    data-confirm-class="btn-success"
                                    data-confirm-icon="fa-user-check"
                                    data-confirm-icon-bg="#ecfdf5"
                                    data-confirm-icon-color="#059669">
                                <i class="fas fa-user-check me-1"></i> Valider KYC sur Place
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <!-- 3 CARTES KPIS FINANCIÈRES CLIENT -->
        <div class="col-12 col-xl-8">
            <div class="row g-3 h-100">
                <div class="col-12 col-sm-6">
                    <div class="white-card p-3 h-100" style="background: #f0fdf4; border-color: #dcfce7;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-title text-success">Épargne & Tontine Totale</span>
                            <span class="badge bg-success text-white p-2 rounded-2"><i class="fas fa-piggy-bank"></i></span>
                        </div>
                        <div class="stat-value text-success font-numeric">{{ number_format($summary['total_balance'], 0, ',', ' ') }} <small class="fs-7 text-muted">FCFA</small></div>
                        <small class="text-muted fs-8">Épargne libre : {{ number_format($summary['total_savings'], 0, ',', ' ') }} F • Tontines : {{ number_format($summary['total_tontine'], 0, ',', ' ') }} F</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6">
                    <div class="white-card p-3 h-100" style="background: #fff1f2; border-color: #ffe4e6;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-title text-danger">Encours Crédit Restant</span>
                            <span class="badge bg-danger text-white p-2 rounded-2"><i class="fas fa-scale-balanced"></i></span>
                        </div>
                        <div class="stat-value text-danger font-numeric">{{ number_format($summary['total_loans_remaining'], 0, ',', ' ') }} <small class="fs-7 text-muted">FCFA</small></div>
                        <small class="text-muted fs-8">Total remboursé : {{ number_format($summary['total_loans_paid'], 0, ',', ' ') }} FCFA</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6">
                    <div class="white-card p-3 h-100" style="background: #eff6ff; border-color: #dbeafe;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-title text-primary">Comptes & Livrets</span>
                            <span class="badge bg-primary text-white p-2 rounded-2"><i class="fas fa-wallet"></i></span>
                        </div>
                        <div class="stat-value text-primary font-numeric">{{ $summary['total_accounts'] }}</div>
                        <small class="text-muted fs-8">{{ $summary['active_accounts'] }} compte(s) opérationnel(s)</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6">
                    <div class="white-card p-3 h-100" style="background: #fffbeb; border-color: #fef3c7;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="stat-title text-warning">Dossiers de Prêts</span>
                            <span class="badge bg-warning text-dark p-2 rounded-2"><i class="fas fa-hand-holding-dollar"></i></span>
                        </div>
                        <div class="stat-value text-dark font-numeric">{{ $summary['total_loans_count'] }}</div>
                        <small class="text-muted fs-8">{{ $client->loans->whereIn('status', ['active', 'disbursed'])->count() }} prêt(s) en cours de remboursement</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ONGLETS DOSSIER CLIENT (COMPTES, PRÊTS, TRANSACTIONS) -->
    <div class="white-card p-0 overflow-hidden">
        <!-- Navigation Onglets -->
        <div class="p-3 border-bottom bg-white">
            <ul class="nav nav-pills gap-2" id="clientDossierTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold fs-7 rounded-3 px-3 py-2" id="accounts-tab" data-bs-toggle="pill" data-bs-target="#tab-accounts" type="button">
                        <i class="fas fa-wallet me-2"></i>Comptes & Livrets ({{ $client->accounts->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold fs-7 rounded-3 px-3 py-2" id="loans-tab" data-bs-toggle="pill" data-bs-target="#tab-loans" type="button">
                        <i class="fas fa-hand-holding-dollar me-2"></i>Dossiers de Prêts ({{ $client->loans->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold fs-7 rounded-3 px-3 py-2" id="history-tab" data-bs-toggle="pill" data-bs-target="#tab-history" type="button">
                        <i class="fas fa-receipt me-2"></i>Historique des Opérations ({{ $recentTransactions->count() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="clientDossierTabContent">
            <!-- ONGLET 1: COMPTES & LIVRETS -->
            <div class="tab-pane fade show active p-0" id="tab-accounts" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-corporate mb-0">
                        <thead>
                            <tr>
                                <th>N° Compte</th>
                                <th>Type & Mise</th>
                                <th>Solde Actuel</th>
                                <th>Progression Cycle</th>
                                <th>Statut</th>
                                <th class="text-end">Actions Guichet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($client->accounts as $account)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark font-monospace fs-8">{{ $account->account_number }}</span>
                                </td>
                                <td>
                                    @if($account->tontineAccount)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-8">Tontine</span>
                                        <div class="fw-bold text-dark fs-8 mt-1">{{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }} FCFA / {{ ucfirst($account->tontineAccount->payment_frequency) }}</div>
                                    @else
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fs-8">Épargne Libre</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-success fs-7 font-numeric">
                                        {{ number_format($account->balance, 0, ',', ' ') }} FCFA
                                    </div>
                                </td>
                                <td style="min-width: 150px;">
                                    @if($account->tontineAccount)
                                        @php
                                            $tontine = $account->tontineAccount;
                                            $pct = $tontine->total_expected > 0 ? min(100, ($tontine->total_paid / $tontine->total_expected) * 100) : 0;
                                        @endphp
                                        <div class="d-flex justify-content-between fs-8 mb-1">
                                            <span class="text-muted">{{ number_format($tontine->total_paid, 0, ',', ' ') }} F</span>
                                            <span class="fw-bold text-dark">{{ number_format($pct, 0) }}%</span>
                                        </div>
                                        <div class="progress" style="height: 5px; background: #f1f5f9;">
                                            <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                                        </div>
                                    @else
                                        <span class="text-muted fs-8"><i class="fas fa-check text-success me-1"></i> Compte Actif</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account->status == 'active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8">Actif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted border px-2 py-1 rounded-pill fs-8">{{ $account->status }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('caissier.accounts.show', $account->id) }}" class="btn btn-light border text-dark" title="Détail du Compte">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($account->status == 'active')
                                            <a href="{{ route('caissier.depot') }}?search={{ $account->account_number }}" class="btn btn-light border text-success fw-bold" title="Faire un versement">
                                                <i class="fas fa-arrow-down me-1"></i> Dépôt
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-wallet fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="fw-bold text-dark mb-1">Aucun compte ouvert pour ce client.</p>
                                    <a href="{{ route('caissier.accounts.create', $client->id) }}" class="btn btn-sm btn-outline-primary fw-bold mt-2">
                                        <i class="fas fa-plus me-1"></i> Ouvrir un Compte
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ONGLET 2: DOSSIERS DE PRÊTS DU CLIENT -->
            <div class="tab-pane fade p-0" id="tab-loans" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-corporate mb-0">
                        <thead>
                            <tr>
                                <th>N° Dossier</th>
                                <th>Montant Accordé</th>
                                <th>Durée & Taux</th>
                                <th>Total Dû</th>
                                <th>Remboursé / Reste Dû</th>
                                <th>Statut</th>
                                <th class="text-end">Actions Guichet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($client->loans as $loan)
                            @php
                                $paidPct = $loan->total_amount_due > 0 ? min(100, round(($loan->total_paid / $loan->total_amount_due) * 100)) : 0;
                                $remaining = max(0, $loan->total_amount_due - $loan->total_paid);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark font-monospace fs-8">{{ $loan->loan_number }}</div>
                                    <small class="text-muted fs-8">{{ $loan->created_at->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-7 font-numeric">
                                        {{ number_format($loan->approved_amount ?? $loan->requested_amount, 0, ',', ' ') }} FCFA
                                    </div>
                                    <small class="text-muted fs-8">Mensualité : {{ number_format($loan->monthly_payment, 0, ',', ' ') }} F</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 fs-8">
                                        {{ $loan->duration_months }} mois à {{ $loan->interest_rate }}%
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-7 font-numeric">{{ number_format($loan->total_amount_due, 0, ',', ' ') }} FCFA</div>
                                </td>
                                <td style="min-width: 170px;">
                                    <div class="d-flex justify-content-between fs-8 mb-1">
                                        <span class="text-success fw-bold">{{ number_format($loan->total_paid, 0, ',', ' ') }} F</span>
                                        <span class="text-danger fw-bold">Reste : {{ number_format($remaining, 0, ',', ' ') }} F</span>
                                    </div>
                                    <div class="progress" style="height: 5px; background: #f1f5f9;">
                                        <div class="progress-bar bg-success" style="width: {{ $paidPct }}%;"></div>
                                    </div>
                                </td>
                                <td>
                                    @switch($loan->status)
                                        @case('approved')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                                Approuvé (À Décaisser)
                                            </span>
                                            @break
                                        @case('disbursed')
                                        @case('active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                                Actif / En cours
                                            </span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                                Soldé 🎉
                                            </span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill fs-8">
                                                En Attente Accord
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill fs-8">{{ ucfirst($loan->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('caissier.loans.show', $loan->id) }}" class="btn btn-light border text-dark" title="Voir Fiche Prêt & Échéancier">
                                            <i class="fas fa-eye me-1"></i> Échéancier
                                        </a>

                                        @if($loan->status === 'approved')
                                            <form action="{{ route('caissier.loans.disburse', $loan->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="button" class="btn btn-success fw-bold" title="Payer le prêt"
                                                        data-confirm="Confirmer le décaissement physique de <strong>{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</strong> pour le prêt {{ $loan->loan_number }} ?"
                                                        data-confirm-title="Décaissement de Prêt"
                                                        data-confirm-subtitle="Remise des fonds en espèces"
                                                        data-confirm-btn="Confirmer le Décaissement"
                                                        data-confirm-class="btn-success"
                                                        data-confirm-icon="fa-hand-holding-dollar"
                                                        data-confirm-icon-bg="#ecfdf5"
                                                        data-confirm-icon-color="#059669">
                                                    <i class="fas fa-hand-holding-dollar me-1"></i> Décaisser
                                                </button>
                                            </form>
                                        @elseif(in_array($loan->status, ['disbursed', 'active']))
                                            <a href="{{ route('caissier.loans.show', $loan->id) }}" class="btn btn-outline-primary fw-bold" title="Rembourser">
                                                <i class="fas fa-coins me-1"></i> Rembourser
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-hand-holding-dollar fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="fw-bold text-dark mb-1">Aucun dossier de crédit enregistré pour cet adhérent.</p>
                                    <a href="{{ route('caissier.loans.apply.form', ['client_id' => $client->id]) }}" class="btn btn-sm btn-success fw-bold mt-2">
                                        <i class="fas fa-plus-circle me-1"></i> Monter une Demande de Prêt
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ONGLET 3: HISTORIQUE DES TRANSACTIONS RÉCENTES -->
            <div class="tab-pane fade p-0" id="tab-history" role="tabpanel">
                <div class="table-responsive">
                    <table class="table-corporate mb-0">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Référence</th>
                                <th>Type Opération</th>
                                <th>Canal</th>
                                <th class="text-end">Montant</th>
                                <th class="text-end">Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                            @php
                                $isInflow = in_array($tx->transaction_type, ['deposit', 'savings_deposit', 'tontine_deposit', 'loan_repayment', 'transfer_in']);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark fs-8">{{ $tx->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted fs-8 font-monospace">{{ $tx->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fs-8">{{ $tx->transaction_reference }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $isInflow ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} fs-8 fw-bold">
                                        {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border fs-8 text-capitalize">{{ str_replace('_', ' ', $tx->payment_method) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold fs-7 {{ $isInflow ? 'text-success' : 'text-danger' }}">
                                        {{ $isInflow ? '+' : '-' }} {{ number_format($tx->amount, 0, ',', ' ') }} FCFA
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('caissier.receipt.print', $tx->id) }}" target="_blank" class="btn btn-sm btn-light border text-dark" title="Imprimer le Reçu">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="fw-bold text-dark mb-1">Aucune transaction enregistrée récemment pour ce client.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
