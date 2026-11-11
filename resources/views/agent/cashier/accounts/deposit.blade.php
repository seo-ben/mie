@extends('layouts.cashier')

@section('title', 'Guichet de Dépôt - ' . $account->account_number)
@section('page-title', 'Encaissement sur Compte')
@section('page-subtitle', $account->account_number . ' • Adhérent : ' . ($account->client->full_name ?? 'Client'))

@section('content')
<div class="space-y-4" style="max-width: 1040px; margin: 0 auto;">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Versement sur Compte</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.accounts.index') }}" class="text-decoration-none text-muted">Comptes</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.accounts.show', $account->id) }}" class="text-decoration-none text-muted">{{ $account->account_number }}</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Dépôt Guichet</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.accounts.show', $account->id) }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-arrow-left me-1"></i> Fiche Compte
            </a>
            <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-cash-register me-1 text-primary"></i> Terminal Caisse
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- COLONNE GAUCHE : IDENTITÉ DU TITULAIRE & ÉTAT COMPTE -->
        <div class="col-12 col-lg-5">
            <div class="white-card p-4 h-100">
                <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.15rem;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0 fs-6">{{ $account->account_number }}</h5>
                        <span class="badge bg-light text-dark border font-monospace fs-8">{{ strtoupper($account->account_type) }}</span>
                    </div>
                </div>

                <div class="space-y-3 fs-8 text-muted">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span><i class="fas fa-user text-muted me-2"></i>Adhérent</span>
                        <strong class="text-dark">{{ $account->client->full_name }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span><i class="fas fa-phone text-muted me-2"></i>Téléphone</span>
                        <strong class="text-dark font-monospace">{{ $account->client->phone }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span><i class="fas fa-coins text-muted me-2"></i>Solde Disponible</span>
                        <strong class="text-emerald-600 font-numeric fs-6">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</strong>
                    </div>

                    @if($account->tontineAccount)
                        @php
                            $t = $account->tontineAccount;
                            $pct = $t->total_expected > 0 ? min(100, round(($t->total_paid / $t->total_expected) * 100)) : 0;
                        @endphp
                        <div class="p-3 rounded-3 border" style="background: #f8fafc;">
                            <div class="d-flex justify-content-between fs-8 mb-1">
                                <span class="fw-bold text-dark">Mise Tontine :</span>
                                <strong class="text-dark font-numeric">{{ number_format($t->tontine_amount, 0, ',', ' ') }} F / {{ $t->payment_frequency }}</strong>
                            </div>
                            <div class="d-flex justify-content-between fs-8 mb-1">
                                <span class="text-muted">Progression :</span>
                                <span class="fw-bold text-emerald-600">{{ $pct }}%</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                                <div class="progress-bar bg-success" style="width: {{ $pct }}%;"></div>
                            </div>
                            <div class="d-flex justify-content-between fs-8 mt-2 text-muted">
                                <span>Total cotisé : {{ number_format($t->total_paid, 0, ',', ' ') }} F</span>
                                <span>Reste : {{ number_format(max(0, $t->total_expected - $t->total_paid), 0, ',', ' ') }} F</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-3 rounded-3 mt-4 border border-info-subtle" style="background: #ecfeff;">
                    <div class="d-flex align-items-center gap-2 text-cyan-800 fw-bold fs-8 mb-1">
                        <i class="fas fa-shield-check text-cyan-600"></i> Traçabilité Guichet
                    </div>
                    <p class="text-muted fs-8 mb-0">Le dépôt est immédiatement crédité et rattaché à votre session de caisse avec génération d'un reçu imprimable.</p>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE : FORMULAIRE D'ENCAISSEMENT -->
        <div class="col-12 col-lg-7">
            <div class="white-card p-4">
                <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                    <div class="rounded-3 p-3 text-emerald-600 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #ecfdf5; font-size: 1.25rem;">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0 fs-6">Saisie de l'Encaissement</h5>
                        <small class="text-muted fs-8">Complétez le montant et le mode de versement</small>
                    </div>
                </div>

                <form method="POST" action="{{ route('caissier.accounts.deposit.process', $account->id) }}" id="accountDepositForm">
                    @csrf

                    <!-- MONTANT À DÉPOSER -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Montant du Dépôt (FCFA) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="amount" id="depositAmountInput" class="form-control rounded-start-3 border fw-bold font-numeric" style="font-size: 1.3rem;" placeholder="{{ $suggestedAmount ?? 5000 }}" value="{{ old('amount', $suggestedAmount ?? '') }}" min="1" step="any" required autofocus>
                            <span class="input-group-text bg-light fw-bold text-muted border-start-0 rounded-end-3">FCFA</span>
                        </div>
                        <small class="text-muted fs-8">Montant minimum autorisé : 100 FCFA</small>

                        <!-- BOUTONS DE SÉLECTION RAPIDE DE MONTANT -->
                        <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                            @if(isset($suggestedAmount) && $suggestedAmount > 0)
                                <button type="button" class="btn btn-sm btn-outline-success fw-bold rounded-2 px-2 py-1 fs-8" onclick="setDepositAmount({{ $suggestedAmount }})">
                                    <i class="fas fa-bolt me-1"></i> 1 Mise ({{ number_format($suggestedAmount, 0, ',', ' ') }} F)
                                </button>
                                <button type="button" class="btn btn-sm btn-light border fw-bold rounded-2 px-2 py-1 fs-8" onclick="setDepositAmount({{ $suggestedAmount * 2 }})">
                                    2 Mises ({{ number_format($suggestedAmount * 2, 0, ',', ' ') }} F)
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-light border fw-bold rounded-2 px-2 py-1 fs-8" onclick="setDepositAmount(5000)">5 000 F</button>
                            <button type="button" class="btn btn-sm btn-light border fw-bold rounded-2 px-2 py-1 fs-8" onclick="setDepositAmount(10000)">10 000 F</button>
                            <button type="button" class="btn btn-sm btn-light border fw-bold rounded-2 px-2 py-1 fs-8" onclick="setDepositAmount(25000)">25 000 F</button>
                            <button type="button" class="btn btn-sm btn-light border fw-bold rounded-2 px-2 py-1 fs-8" onclick="setDepositAmount(50000)">50 000 F</button>
                        </div>
                    </div>

                    <!-- MODE DE PAIEMENT -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark fs-8 mb-2">Mode de Paiement <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked onchange="togglePaymentFields()">
                                <label class="btn btn-outline-success w-100 py-3 text-center rounded-3 fw-bold fs-7 d-flex flex-column align-items-center gap-1" for="payCash">
                                    <i class="fas fa-money-bill-wave fs-5"></i>
                                    <span>Espèces (Cash)</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" id="payTmoney" value="mobile_money" onchange="togglePaymentFields('tmoney')">
                                <label class="btn btn-outline-primary w-100 py-3 text-center rounded-3 fw-bold fs-7 d-flex flex-column align-items-center gap-1" for="payTmoney">
                                    <i class="fas fa-mobile-screen fs-5"></i>
                                    <span>T-Money</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="payment_method" id="payFlooz" value="mobile_money" onchange="togglePaymentFields('flooz')">
                                <label class="btn btn-outline-info w-100 py-3 text-center rounded-3 fw-bold fs-7 d-flex flex-column align-items-center gap-1" for="payFlooz">
                                    <i class="fas fa-signal fs-5"></i>
                                    <span>Moov Flooz</span>
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="mobile_money_operator" id="mobileOperatorInput" value="">
                    </div>

                    <!-- RÉFÉRENCE DE PAIEMENT POUR MOBILE MONEY -->
                    <div class="mb-3 d-none" id="mobileRefGroup">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Référence de la Transaction Mobile</label>
                        <input type="text" name="payment_reference" class="form-control rounded-3 border" style="padding: 10px 14px; font-size: 0.88rem;" placeholder="Ex: TMY-982347239 ou Réf SMS Moov">
                        <small class="text-muted fs-8">Numéro de confirmation du virement mobile money reçu.</small>
                    </div>

                    <!-- MOTIF / DESCRIPTION -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark fs-8 mb-1">Motif / Commentaire (Optionnel)</label>
                        <input type="text" name="description" class="form-control rounded-3 border" style="padding: 10px 14px; font-size: 0.88rem;" placeholder="Ex: Cotisation journalière au guichet...">
                    </div>

                    <!-- BOUTONS D'ACTION -->
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="{{ route('caissier.accounts.show', $account->id) }}" class="btn btn-light fw-bold text-muted px-4 py-2 rounded-3">
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2 rounded-3 shadow-sm" id="btnSubmitDeposit">
                            <i class="fas fa-check-circle me-1"></i> Valider l'Encaissement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function setDepositAmount(val) {
        const input = document.getElementById('depositAmountInput');
        if (input) {
            input.value = val;
            input.focus();
        }
    }

    function togglePaymentFields(operator = null) {
        const refGroup = document.getElementById('mobileRefGroup');
        const operatorInput = document.getElementById('mobileOperatorInput');
        if (operator) {
            if (refGroup) refGroup.classList.remove('d-none');
            if (operatorInput) operatorInput.value = operator;
        } else {
            if (refGroup) refGroup.classList.add('d-none');
            if (operatorInput) operatorInput.value = '';
        }
    }
</script>
@endsection

