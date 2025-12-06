@extends('layouts.agent')

@section('title', 'Effectuer un Dépôt')

@section('content')
<div class="py-4 container-fluid">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('agent.dashboard') }}">
                    <i class="fas fa-home me-1"></i>Tableau de bord
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('agent.accounts.index') }}">Comptes Tontine</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('agent.accounts.show', $account->id) }}">{{ $account->account_number }}</a>
            </li>
            <li class="breadcrumb-item active">Dépôt</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        Effectuer un Dépôt
                    </h2>
                    <p class="mb-0 text-muted">
                        Sur le compte : <span class="badge bg-purple-soft text-purple">{{ $account->account_number }}</span>
                    </p>
                </div>
                <a href="{{ route('agent.accounts.show', $account->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Messages flash -->
    @if(session('error'))
        <div class="shadow-sm alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erreur !</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="shadow-sm alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erreurs de validation :</strong>
            <ul class="mt-2 mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Formulaire de dépôt -->
        <div class="col-lg-8">
            <div class="border-0 shadow-sm card">
                <div class="py-3 bg-white card-header border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave text-success me-2"></i>
                        Informations du Dépôt
                    </h5>
                </div>
                <div class="p-4 card-body">
                    <form method="POST" action="{{ route('agent.accounts.deposit.process', $account->id) }}" id="depositForm">
                        @csrf

                        <!-- Montant -->
                        <div class="mb-4">
                            <label for="amount" class="form-label fw-bold">
                                <i class="fas fa-coins text-success me-2"></i>
                                Montant du Dépôt <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="number"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       id="amount"
                                       name="amount"
                                       min="100"
                                       step="100"
                                       placeholder="{{ number_format($suggestedAmount, 0, ',', ' ') }}"
                                       value="{{ old('amount', $suggestedAmount) }}"
                                       required
                                       autofocus>
                                <span class="bg-white input-group-text">FCFA</span>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Montant minimum : 100 FCFA
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="suggestAmount">
                                    Montant suggéré : {{ number_format($suggestedAmount, 0, ',', ' ') }} FCFA
                                </button>
                            </div>
                        </div>

                        <!-- Méthode de paiement -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-credit-card text-primary me-2"></i>
                                Méthode de Paiement <span class="text-danger">*</span>
                            </label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check payment-method-card">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="payment_method"
                                               id="payment_cash"
                                               value="cash"
                                               {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}
                                               required>
                                        <label class="form-check-label w-100" for="payment_cash">
                                            <div class="p-3 text-center">
                                                <i class="mb-2 fas fa-money-bill-wave fa-2x text-success"></i>
                                                <div class="fw-bold">Espèces</div>
                                                <small class="text-muted">Paiement en cash</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check payment-method-card">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="payment_method"
                                               id="payment_mobile"
                                               value="mobile_money"
                                               {{ old('payment_method') === 'mobile_money' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="payment_mobile">
                                            <div class="p-3 text-center">
                                                <i class="mb-2 fas fa-mobile-alt fa-2x text-primary"></i>
                                                <div class="fw-bold">Mobile Money</div>
                                                <small class="text-muted">TMoney / Flooz</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check payment-method-card">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="payment_method"
                                               id="payment_bank"
                                               value="bank_transfer"
                                               {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="payment_bank">
                                            <div class="p-3 text-center">
                                                <i class="mb-2 fas fa-university fa-2x text-info"></i>
                                                <div class="fw-bold">Virement</div>
                                                <small class="text-muted">Transfert bancaire</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @error('payment_method')
                                <div class="mt-2 text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Opérateur Mobile Money (conditionnel) -->
                        <div class="mb-4" id="mobile_money_section" style="display: none;">
                            <label for="mobile_money_operator" class="form-label fw-bold">
                                <i class="fas fa-sim-card text-primary me-2"></i>
                                Opérateur Mobile Money <span class="text-danger">*</span>
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check operator-card">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="mobile_money_operator"
                                               id="operator_tmoney"
                                               value="tmoney"
                                               {{ old('mobile_money_operator') === 'tmoney' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="operator_tmoney">
                                            <div class="p-3 d-flex align-items-center">
                                                <i class="fas fa-mobile-alt fa-2x text-warning me-3"></i>
                                                <div>
                                                    <div class="fw-bold">TMoney</div>
                                                    <small class="text-muted">Togocom</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check operator-card">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="mobile_money_operator"
                                               id="operator_flooz"
                                               value="flooz"
                                               {{ old('mobile_money_operator') === 'flooz' ? 'checked' : '' }}>
                                        <label class="form-check-label w-100" for="operator_flooz">
                                            <div class="p-3 d-flex align-items-center">
                                                <i class="fas fa-mobile-alt fa-2x text-danger me-3"></i>
                                                <div>
                                                    <div class="fw-bold">Flooz</div>
                                                    <small class="text-muted">Moov Africa</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @error('mobile_money_operator')
                                <div class="mt-2 text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Référence de paiement -->
                        <div class="mb-4">
                            <label for="payment_reference" class="form-label fw-bold">
                                <i class="fas fa-hashtag text-primary me-2"></i>
                                Référence de Paiement
                                <small class="text-muted">(optionnel)</small>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg @error('payment_reference') is-invalid @enderror"
                                   id="payment_reference"
                                   name="payment_reference"
                                   placeholder="Ex: REF-123456 ou numéro de transaction"
                                   value="{{ old('payment_reference') }}">
                            @error('payment_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Numéro de transaction mobile money ou référence bancaire
                            </small>
                        </div>

                        <!-- Description (optionnel) -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">
                                <i class="fas fa-comment text-primary me-2"></i>
                                Description
                                <small class="text-muted">(optionnel)</small>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Note ou commentaire sur ce dépôt..."
                                      maxlength="500">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted float-end" id="charCount">0 / 500</small>
                        </div>

                        <hr class="my-4">

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('agent.accounts.show', $account->id) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="shadow-sm btn btn-success btn-lg" id="submitBtn">
                                <i class="fas fa-check-circle me-2"></i>Valider le Dépôt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panneau d'informations -->
        <div class="col-lg-4">
            <!-- Informations du compte -->
            <div class="mb-4 border-0 shadow-sm card">
                <div class="py-3 text-white border-0 card-header bg-gradient-primary">
                    <h6 class="mb-0">
                        <i class="fas fa-wallet me-2"></i>
                        Informations du Compte
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Titulaire</small>
                        <div class="fw-bold">{{ $account->client->first_name }} {{ $account->client->last_name }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Numéro de compte</small>
                        <div class="font-monospace">{{ $account->account_number }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Solde actuel</small>
                        <h4 class="mb-0 text-primary fw-bold">
                            {{ number_format($account->balance, 0, ',', ' ') }} <small class="text-muted">FCFA</small>
                        </h4>
                    </div>
                    <div>
                        <small class="mb-1 text-muted d-block">Statut</small>
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Actif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Progression tontine -->
            @if($activeCycle)
                <div class="mb-4 border-0 border-4 shadow-sm card border-start border-primary">
                    <div class="card-body">
                        <h6 class="mb-3 text-primary">
                            <i class="fas fa-sync-alt me-2"></i>
                            Cycle Actif (#{{ $activeCycle->cycle_number }})
                        </h6>
                        @php
                            $cycleProgress = $activeCycle->target_amount > 0
                                ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 1)
                                : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="mb-2 d-flex justify-content-between">
                                <small class="text-muted">Progression</small>
                                <small class="fw-bold text-primary">{{ $cycleProgress }}%</small>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary"
                                     style="width: {{ $cycleProgress }}%"></div>
                            </div>
                        </div>
                        <div class="small">
                            <div class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">Collecté</span>
                                <span class="fw-bold text-success">
                                    {{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">Objectif</span>
                                <span class="fw-bold">
                                    {{ number_format($activeCycle->target_amount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Restant</span>
                                <span class="fw-bold text-warning">
                                    {{ number_format($remainingAmount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Résumé global -->
            <div class="mb-4 border-0 shadow-sm card">
                <div class="py-3 card-header bg-light border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        Résumé Tontine
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $totalProgress = $account->tontineAccount->total_expected > 0
                            ? round(($account->tontineAccount->total_paid / $account->tontineAccount->total_expected) * 100, 1)
                            : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="mb-1 d-flex justify-content-between">
                            <small class="text-muted">Progression totale</small>
                            <small class="fw-bold">{{ $totalProgress }}%</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-gradient-primary"
                                 style="width: {{ $totalProgress }}%"></div>
                        </div>
                    </div>
                    <div class="small">
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Total payé</span>
                            <span class="fw-bold text-success">
                                {{ number_format($account->tontineAccount->total_paid, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Total attendu</span>
                            <span class="fw-bold">
                                {{ number_format($account->tontineAccount->total_expected, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Restant global</span>
                            <span class="fw-bold text-warning">
                                {{ number_format($totalRemaining, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aide -->
            <div class="border-0 border-4 shadow-sm card border-start border-success">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-lightbulb text-success me-2"></i>
                        Bon à savoir
                    </h6>
                    <ul class="mb-0 small ps-3">
                        <li class="mb-2">Le dépôt sera automatiquement réparti sur les cycles</li>
                        <li class="mb-2">Un cycle sera complété automatiquement si l'objectif est atteint</li>
                        <li class="mb-2">Un nouveau cycle démarre automatiquement si nécessaire</li>
                        <li>Le solde du compte sera mis à jour immédiatement</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .payment-method-card,
    .operator-card {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .payment-method-card:hover,
    .operator-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }

    .payment-method-card .form-check-input:checked ~ .form-check-label,
    .operator-card .form-check-input:checked ~ .form-check-label {
        border-color: #667eea;
        background-color: #f0f4ff;
    }

    .payment-method-card .form-check-input,
    .operator-card .form-check-input {
        position: absolute;
        opacity: 0;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-purple-soft {
        background-color: #f3e8ff;
    }

    .text-purple {
        color: #9333ea;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
    }

    .border-4 {
        border-width: 4px !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
    const mobileMoneySection = document.getElementById('mobile_money_section');
    const amountInput = document.getElementById('amount');
    const suggestAmountBtn = document.getElementById('suggestAmount');
    const descriptionTextarea = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const form = document.getElementById('depositForm');

    // Gérer l'affichage de la section Mobile Money
    function toggleMobileMoneySection() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (selectedMethod && selectedMethod.value === 'mobile_money') {
            mobileMoneySection.style.display = 'block';
        } else {
            mobileMoneySection.style.display = 'none';
            document.querySelectorAll('input[name="mobile_money_operator"]').forEach(input => {
                input.checked = false;
            });
        }
    }

    paymentMethodInputs.forEach(input => {
        input.addEventListener('change', toggleMobileMoneySection);
    });

    toggleMobileMoneySection();

    // Montant suggéré
    suggestAmountBtn.addEventListener('click', function() {
        amountInput.value = {{ $suggestedAmount }};
        amountInput.focus();
    });

    // Compteur de caractères
    descriptionTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length + ' / 500';
    });

    // Formater le montant
    amountInput.addEventListener('blur', function() {
        const value = parseFloat(this.value) || 0;
        if (value > 0) {
            this.value = Math.round(value);
        }
    });

    // Validation du formulaire
    form.addEventListener('submit', function(e) {
        const amount = parseFloat(amountInput.value) || 0;

        if (amount < 100) {
            e.preventDefault();
            alert('Le montant minimum est de 100 FCFA');
            amountInput.focus();
            return false;
        }

        const maxAmount = {{ $totalRemaining }};
        if (amount > maxAmount) {
            if (!confirm(`Le montant dépasse le restant (${maxAmount.toLocaleString()} FCFA). Il sera ajusté automatiquement. Continuer ?`)) {
                e.preventDefault();
                return false;
            }
        }

        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            e.preventDefault();
            alert('Veuillez sélectionner une méthode de paiement');
            return false;
        }

        if (selectedMethod.value === 'mobile_money') {
            const selectedOperator = document.querySelector('input[name="mobile_money_operator"]:checked');
            if (!selectedOperator) {
                e.preventDefault();
                alert('Veuillez sélectionner un opérateur Mobile Money');
                return false;
            }
        }

        // Confirmation
        if (!confirm(`Confirmer le dépôt de ${amount.toLocaleString()} FCFA ?`)) {
            e.preventDefault();
            return false;
        }

        // Désactiver le bouton
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
    });
});
</script>
@endpush

@endsection
