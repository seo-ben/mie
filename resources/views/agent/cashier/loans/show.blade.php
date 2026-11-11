@extends('layouts.cashier')

@section('title', 'Dossier de Prêt ' . $loan->loan_number)
@section('page-title', 'Prêt ' . $loan->loan_number)
@section('page-subtitle', 'Adhérent : ' . $loan->client->full_name)

@section('content')
<div class="space-y-4">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Contrat de Prêt {{ $loan->loan_number }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.loans.index') }}" class="text-decoration-none text-muted">Prêts</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">{{ $loan->loan_number }}</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('caissier.clients.show', $loan->client_id) }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-user me-1 text-primary"></i> Fiche Client
            </a>

            @if($loan->status === 'approved')
                <form action="{{ route('caissier.loans.disburse', $loan->id) }}" method="POST">
                    @csrf
                    <button type="button" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm"
                            data-confirm="Confirmer le décaissement physique de <strong>{{ number_format($loan->approved_amount, 0, ',', ' ') }} FCFA</strong> en espèces pour l'adhérent <strong>{{ addslashes($loan->client->full_name) }}</strong> ?"
                            data-confirm-title="Décaissement de Prêt Guichet"
                            data-confirm-subtitle="Dossier N° {{ $loan->loan_number }}"
                            data-confirm-btn="Confirmer le Décaissement"
                            data-confirm-class="btn-success"
                            data-confirm-icon="fa-hand-holding-dollar"
                            data-confirm-icon-bg="#ecfdf5"
                            data-confirm-icon-color="#059669">
                        <i class="fas fa-hand-holding-dollar me-1"></i> Décaisser les Espèces
                    </button>
                </form>
            @elseif(in_array($loan->status, ['disbursed', 'active']))
                <button type="button" class="btn btn-success fw-bold px-3 py-2 rounded-3 fs-7 shadow-sm" data-bs-toggle="modal" data-bs-target="#repayModal">
                    <i class="fas fa-coins me-1"></i> Encaisser un Remboursement
                </button>
            @endif

            <a href="{{ route('caissier.loans.index') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-muted">
                <i class="fas fa-arrow-left me-1"></i> Retour aux Prêts
            </a>
        </div>
    </div>

    <!-- 4 KPIS FINANCIERS DU PRÊT -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="white-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #eff6ff; font-size: 1.25rem;">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                    <div>
                        <div class="stat-title">Capital Emprunté</div>
                        <div class="stat-value text-dark fs-5">{{ number_format($loan->approved_amount ?? $loan->requested_amount, 0, ',', ' ') }} <small class="fs-8 text-muted">FCFA</small></div>
                        <small class="text-muted fs-8">{{ $loan->duration_months }} mois à {{ $loan->interest_rate }}%</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="white-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-dark d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #f8fafc; font-size: 1.25rem;">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total à Rembourser</div>
                        <div class="stat-value text-dark fs-5">{{ number_format($loan->total_amount_due, 0, ',', ' ') }} <small class="fs-8 text-muted">FCFA</small></div>
                        <small class="text-muted fs-8">Mensualité : {{ number_format($loan->monthly_payment, 0, ',', ' ') }} F</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="white-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #f0fdf4; font-size: 1.25rem;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Déjà Remboursé</div>
                        <div class="stat-value text-success fs-5">{{ number_format($loan->total_paid, 0, ',', ' ') }} <small class="fs-8 text-muted">FCFA</small></div>
                        <small class="text-success fw-bold fs-8">
                            {{ $loan->total_amount_due > 0 ? round(($loan->total_paid / $loan->total_amount_due) * 100, 1) : 0 }}% recouvré
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="white-card p-3">
                @php
                    $remainingDue = max(0, $loan->total_amount_due - $loan->total_paid);
                @endphp
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #fff1f2; font-size: 1.25rem;">
                        <i class="fas fa-scale-balanced"></i>
                    </div>
                    <div>
                        <div class="stat-title">Reste Dû</div>
                        <div class="stat-value text-danger fs-5">{{ number_format($remainingDue, 0, ',', ' ') }} <small class="fs-8 text-muted">FCFA</small></div>
                        <small class="text-muted fs-8">Statut : {{ ucfirst($loan->status) }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DOUBLE PANNEAUX : EMPRUNTEUR & PARAMÈTRES DU PRÊT -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-5">
            <div class="white-card h-100">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-user-check text-primary"></i> Identité de l'Adhérent
                </h6>
                <div class="space-y-2 fs-8 text-muted">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>Nom & Prénoms :</span>
                        <strong class="text-dark">{{ $loan->client->full_name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>Code Adhérent :</span>
                        <strong class="text-dark font-monospace">{{ $loan->client->client_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>Téléphone :</span>
                        <strong class="text-dark font-monospace">{{ $loan->client->phone }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>Activité / Profession :</span>
                        <strong class="text-dark">{{ $loan->client->profession ?? 'Commerçant(e)' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span>Objet du financement :</span>
                        <strong class="text-dark">{{ $loan->purpose }}</strong>
                    </div>
                    @if($loan->collateral_description)
                        <div class="py-2">
                            <span class="d-block mb-1">Garantie Principale :</span>
                            <div class="p-2 rounded-2 bg-light text-dark fs-8 border">{{ $loan->collateral_description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-7">
            <div class="white-card h-100">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-file-signature text-success"></i> Modalités & Suivi Administratif
                </h6>
                <div class="row g-3 fs-8 text-muted">
                    <div class="col-6">
                        <span class="d-block mb-1">Date d'enregistrement :</span>
                        <strong class="text-dark fs-7">{{ $loan->created_at->format('d/m/Y à H:i') }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="d-block mb-1">Frais de Dossier Encaissés :</span>
                        <strong class="text-success fs-7">{{ number_format($loan->application_fee ?? 0, 0, ',', ' ') }} FCFA</strong>
                    </div>
                    <div class="col-6">
                        <span class="d-block mb-1">Statut d'approbation :</span>
                        @switch($loan->status)
                            @case('approved')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fw-bold">Approuvé (À Décaisser)</span>
                                @break
                            @case('disbursed')
                            @case('active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold">Décaissé & Actif</span>
                                @break
                            @case('completed')
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill fw-bold">Totalement Soldé 🎉</span>
                                @break
                            @default
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill">{{ ucfirst($loan->status) }}</span>
                        @endswitch
                    </div>
                    <div class="col-6">
                        <span class="d-block mb-1">Date de décaissement :</span>
                        <strong class="text-dark fs-7">{{ $loan->disbursed_at ? $loan->disbursed_at->format('d/m/Y') : 'Non décaissé' }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="d-block mb-1">Validateur / Gestionnaire :</span>
                        <strong class="text-dark fs-7">{{ $loan->approvedBy->full_name ?? 'Direction Agence' }}</strong>
                    </div>
                    <div class="col-12 pt-2 border-top">
                        @php
                            $verifProg = $loan->getVerificationsProgress();
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark">Audit des Contrôles :</span>
                            <span class="badge {{ $verifProg['is_complete'] ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-2.5 py-1">
                                {{ $verifProg['checked'] }} / {{ $verifProg['total'] }} vérifiés ({{ $verifProg['percent'] }}%)
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $verifProg['percent'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CAUTIONNAIRES SOLIDAIRES (3 GARANTS) -->
    <div class="white-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: #eff6ff; font-size: 1.2rem;">
                    <i class="fas fa-users-viewfinder"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0 fs-6">Cautionnaires Solidaires du Prêt ({{ $loan->guarantors->count() }} Caution(s))</h5>
                    <small class="text-muted fs-8">Garanties personnelles et engagements de solvabilité souscrits</small>
                </div>
            </div>
            <span class="badge bg-slate-100 text-slate-700 px-3 py-1.5 rounded-pill fs-8 fw-bold">
                <i class="fas fa-shield-halved text-primary me-1"></i> Engagement Solidaire
            </span>
        </div>

        @if($loan->guarantors->isEmpty())
            <div class="p-4 text-center bg-light rounded-3 text-muted">
                <i class="fas fa-user-slash fs-2 mb-2 text-slate-300"></i>
                <p class="fs-8 mb-0">Aucun cautionnaire solidaire enregistré dans ce dossier.</p>
            </div>
        @else
            <div class="row g-3">
                @foreach($loan->guarantors as $g)
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded-3 border bg-white h-100 d-flex flex-column justify-content-between shadow-xs">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 fs-9">
                                    Cautionnaire {{ $g->guarantor_order }}
                                </span>
                                @if($g->is_verified)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 fs-9">
                                        <i class="fas fa-check-circle me-1"></i> Accord Validé
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5 fs-9">
                                        <i class="fas fa-clock me-1"></i> À Vérifier
                                    </span>
                                @endif
                            </div>

                            <h6 class="fw-black text-dark mb-1 fs-7">{{ $g->full_name }}</h6>
                            <p class="fs-8 text-muted mb-2">
                                <i class="fas fa-phone me-1 text-slate-400"></i><strong class="text-dark font-monospace">{{ $g->phone }}</strong>
                            </p>

                            <div class="space-y-1 fs-8 text-muted border-top pt-2">
                                <div class="d-flex justify-content-between">
                                    <span>Pièce CNI :</span>
                                    <strong class="text-dark font-monospace">{{ $g->id_card_number ?? '—' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Relation :</span>
                                    <strong class="text-dark">{{ $g->relationship ?? 'Non spécifié' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Profession :</span>
                                    <strong class="text-dark">{{ $g->profession ?? '—' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Adresse :</span>
                                    <strong class="text-dark">{{ $g->address ?? '—' }}</strong>
                                </div>
                            </div>
                        </div>

                        @if($g->collateral_description || $g->collateral_value)
                        <div class="mt-3 pt-2 border-top bg-light p-2 rounded-2 fs-8">
                            <span class="text-muted d-block fs-9 text-uppercase fw-bold">Garantie souscrite :</span>
                            <div class="fw-bold text-dark">{{ $g->collateral_description ?? 'Engagement personnel solidaire' }}</div>
                            @if($g->collateral_value > 0)
                                <div class="text-success fw-black font-monospace mt-0.5">Valeur : {{ number_format($g->collateral_value, 0, ',', ' ') }} FCFA</div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- GRILLE D'AUDIT & CHECKLIST DES VÉRIFICATIONS (INTERACTIVE) -->
    <div class="white-card p-4 mb-4">
        <form action="{{ route('caissier.loans.verifications.update', $loan->id) }}" method="POST">
            @csrf

            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 text-success d-flex align-items-center justify-center" style="width: 44px; height: 44px; background: #ecfdf5; font-size: 1.2rem;">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0 fs-6">Grille d'Audit & Checklist des Vérifications Prêt</h5>
                        <small class="text-muted fs-8">Cochez et mettez à jour les contrôles réglementaires effectués sur ce prêt</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-success fw-bold px-3 py-2 rounded-3 fs-8 shadow-sm">
                    <i class="fas fa-floppy-disk me-1"></i> Enregistrer les Vérifications
                </button>
            </div>

            <div class="row g-3">
                @php
                    $items = \App\Models\Loan::VERIFICATION_ITEMS;
                    $categories = [
                        'Identité & Localisation' => 'border-primary',
                        'Activité & Solvabilité' => 'border-success',
                        'Cautions & Cautionnaires' => 'border-info',
                        'Conformité & Juridique' => 'border-dark',
                    ];
                @endphp

                @foreach($items as $k => $item)
                @php
                    $isChecked = $loan->isVerificationItemChecked($k);
                @endphp
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3 border {{ $isChecked ? 'bg-success-subtle border-success-subtle' : 'bg-light border-slate-200' }} d-flex align-items-center justify-content-between gap-3">
                        <div class="form-check d-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input" type="checkbox" name="verifications[{{ $k }}]" value="1" id="show_verif_{{ $k }}" {{ $isChecked ? 'checked' : '' }}>
                            <label class="form-check-label fs-8 fw-semibold text-dark cursor-pointer" for="show_verif_{{ $k }}">
                                <i class="fas {{ $item['icon'] }} me-1.5 text-muted"></i> {{ $item['label'] }}
                            </label>
                        </div>
                        <span class="badge {{ $isChecked ? 'bg-success' : 'bg-secondary' }} rounded-pill fs-9 px-2">
                            {{ $isChecked ? 'Conforme' : 'Non vérifié' }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- VALIDATION DES CAUTIONS PAR CHECKBOX -->
            @if($loan->guarantors->isNotEmpty())
            <div class="mt-4 pt-3 border-top">
                <span class="fs-8 fw-bold text-muted text-uppercase d-block mb-2">Accord formel des Cautionnaires :</span>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($loan->guarantors as $g)
                    <div class="form-check fs-8 bg-slate-50 p-2 px-3 rounded-2 border">
                        <input class="form-check-input" type="checkbox" name="guarantor_verified[{{ $g->id }}]" value="1" id="gv_{{ $g->id }}" {{ $g->is_verified ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="gv_{{ $g->id }}">
                            {{ $g->full_name }} (Caution {{ $g->guarantor_order }})
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </form>
    </div>

    <!-- TABLEAU D'AMORTISSEMENT / ÉCHÉANCIER COMPLET -->
    <div class="white-card p-0 overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-calendar-check text-primary"></i>
                Échéancier de Remboursement Mensuel ({{ $loan->payments->count() }} mensualités)
            </h6>
            @if(in_array($loan->status, ['disbursed', 'active']))
                <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-1 rounded-2" data-bs-toggle="modal" data-bs-target="#repayModal">
                    <i class="fas fa-coins me-1"></i> Rembourser une Échéance
                </button>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table-corporate mb-0">
                <thead>
                    <tr>
                        <th>N° Échéance</th>
                        <th>Date Limite</th>
                        <th>Montant Attendu</th>
                        <th>Montant Payé</th>
                        <th>Pénalité</th>
                        <th>Statut</th>
                        <th>Date Paiement</th>
                        <th class="text-end">Réf. Quittance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->payments as $p)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace fs-8">Échéance {{ $p->payment_number }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-8">{{ \Carbon\Carbon::parse($p->due_date)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark fs-8 font-numeric">{{ number_format($p->expected_amount, 0, ',', ' ') }} FCFA</div>
                            </td>
                            <td>
                                <div class="fw-bold text-success fs-8 font-numeric">{{ number_format($p->paid_amount, 0, ',', ' ') }} FCFA</div>
                            </td>
                            <td>
                                @if($p->penalty_amount > 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-8 font-numeric">{{ number_format($p->penalty_amount, 0, ',', ' ') }} F</span>
                                @else
                                    <span class="text-muted fs-8">-</span>
                                @endif
                            </td>
                            <td>
                                @if($p->status === 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                        <i class="fas fa-check me-1"></i> Réglée
                                    </span>
                                @elseif($p->status === 'partial')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill fs-8 fw-bold">
                                        Partielle
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill fs-8">
                                        À échoir
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="fs-8 text-muted">{{ $p->paid_date ? \Carbon\Carbon::parse($p->paid_date)->format('d/m/Y') : '-' }}</span>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-light text-dark border font-monospace fs-8">{{ $p->payment_reference ?? '-' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-calendar-xmark fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h6 class="fw-bold text-dark mb-1">Échéancier non encore généré</h6>
                                    <p class="fs-8 text-muted mb-0">Le tableau d'amortissement sera formalisé automatiquement dès le décaissement physique du prêt.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL REMBOURSEMENT DIRECT AU GUICHET -->
<div class="modal fade" id="repayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="{{ route('caissier.loans.repay', $loan->id) }}">
                @csrf
                <div class="modal-header border-bottom p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success-subtle p-2 text-success fs-4">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0">Remboursement d'Échéance</h5>
                            <small class="text-muted">Prêt {{ $loan->loan_number }} • {{ $loan->client->full_name }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 space-y-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Montant à Encaisser (FCFA)</label>
                        <div class="input-group">
                            <input type="number" name="amount" class="form-control rounded-start-3 border fw-bold" style="padding: 10px 14px; font-size: 1.1rem;" value="{{ round($loan->monthly_payment) }}" min="1" step="any" required>
                            <span class="input-group-text bg-light fw-bold text-muted border-start-0 rounded-end-3">FCFA</span>
                        </div>
                        <small class="text-muted fs-8">Mensualité contractuelle : {{ number_format($loan->monthly_payment, 0, ',', ' ') }} FCFA</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Mode d'Encaissement</label>
                        <select name="payment_method" class="form-select rounded-3 border fw-bold" style="padding: 10px 14px; font-size: 0.88rem;">
                            <option value="cash" selected>Espèces au Guichet</option>
                            <option value="mobile_money">Mobile Money (T-Money / Flooz)</option>
                            <option value="bank_transfer">Virement Bancaire</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Référence / Note Interne (Optionnel)</label>
                        <input type="text" name="notes" class="form-control rounded-3 border" style="padding: 10px 14px; font-size: 0.88rem;" placeholder="Ex: Quittance N°...">
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light fw-bold text-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success fw-bold px-4 py-2 rounded-3 fs-7 shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> Valider l'Encaissement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('repay') === '1') {
        const modalEl = document.getElementById('repayModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
});
</script>
@endpush
@endsection
