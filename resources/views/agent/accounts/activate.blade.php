@extends('layouts.agent')

@section('title', 'Activer le Compte Tontine')

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
            <li class="breadcrumb-item active">Activation</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-play-circle text-warning me-2"></i>
                        Activer le Compte Tontine
                    </h2>
                    <p class="mb-0 text-muted">
                        Compte : <span class="badge bg-purple-soft text-purple">{{ $account->account_number }}</span>
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
        <!-- Formulaire d'activation -->
        <div class="col-lg-8">
            <div class="border-0 shadow-sm card">
                <div class="py-3 bg-white card-header border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                        Informations d'Activation
                    </h5>
                </div>
                <div class="p-4 card-body">
                    <form method="POST" action="{{ route('agent.accounts.activate', $account->id) }}" id="activationForm">
                        @csrf

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

                        <hr class="my-4">

                        <!-- Dépôt initial (optionnel) -->
                        <div class="mb-4">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <label for="initial_deposit" class="mb-0 form-label fw-bold">
                                    <i class="fas fa-coins text-success me-2"></i>
                                    Dépôt Initial
                                    <small class="text-muted">(optionnel)</small>
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="enable_initial_deposit"
                                           {{ old('initial_deposit') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enable_initial_deposit">
                                        Ajouter un dépôt initial
                                    </label>
                                </div>
                            </div>
                            <div id="initial_deposit_section" style="display: {{ old('initial_deposit') ? 'block' : 'none' }};">
                                <div class="input-group input-group-lg">
                                    <input type="number"
                                           class="form-control @error('initial_deposit') is-invalid @enderror"
                                           id="initial_deposit"
                                           name="initial_deposit"
                                           min="0"
                                           step="100"
                                           placeholder="0"
                                           value="{{ old('initial_deposit') }}">
                                    <span class="bg-white input-group-text">FCFA</span>
                                    @error('initial_deposit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Le client peut effectuer son premier dépôt lors de l'activation
                                </small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('agent.accounts.show', $account->id) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="shadow-sm btn btn-warning btn-lg" id="submitBtn">
                                <i class="fas fa-play-circle me-2"></i>Activer le Compte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Récapitulatif -->
        <div class="col-lg-4">
            <!-- Informations du compte -->
            <div class="mb-4 border-0 shadow-sm card">
                <div class="py-3 text-white border-0 card-header bg-gradient-primary">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
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
                        <small class="mb-1 text-muted d-block">Téléphone</small>
                        <div><i class="fas fa-phone me-2"></i>{{ $account->client->phone }}</div>
                    </div>
                    <div>
                        <small class="mb-1 text-muted d-block">Statut actuel</small>
                        <span class="badge bg-warning">
                            <i class="fas fa-pause-circle me-1"></i>Suspendu
                        </span>
                    </div>
                </div>
            </div>

            <!-- Détails de la tontine -->
            @if($account->tontineAccount)
                <div class="mb-4 border-0 shadow-sm card">
                    <div class="py-3 card-header bg-light border-bottom">
                        <h6 class="mb-0">
                            <i class="fas fa-users text-primary me-2"></i>
                            Configuration Tontine
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="p-3 mb-3 rounded bg-light">
                            <small class="mb-1 text-muted d-block">Montant par période</small>
                            <h4 class="mb-0 text-primary fw-bold">
                                {{ number_format($account->tontineAccount->tontine_amount, 0, ',', ' ') }}
                                <small class="text-muted fs-6">FCFA</small>
                            </h4>
                        </div>
                        <div class="mb-3">
                            <small class="mb-1 text-muted d-block">Fréquence</small>
                            <div class="fw-bold">
                                @switch($account->tontineAccount->payment_frequency)
                                    @case('daily')
                                        <i class="fas fa-sun text-warning me-2"></i>Quotidien
                                        @break
                                    @case('weekly')
                                        <i class="fas fa-calendar-week text-info me-2"></i>Hebdomadaire
                                        @break
                                    @case('monthly')
                                        <i class="fas fa-calendar text-primary me-2"></i>Mensuel
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="mb-1 text-muted d-block">Durée</small>
                            <div class="fw-bold">
                                <i class="fas fa-clock text-success me-2"></i>
                                {{ $account->tontineAccount->cycle_duration_months }} mois
                            </div>
                        </div>
                        <div class="p-3 text-white rounded bg-gradient-primary">
                            <small class="mb-1 opacity-75 d-block">Total attendu</small>
                            <h4 class="mb-0 fw-bold">
                                {{ number_format($account->tontineAccount->total_expected, 0, ',', ' ') }}
                            </h4>
                            <small class="opacity-75">FCFA</small>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Informations importantes -->
            <div class="border-0 border-4 shadow-sm card border-start border-warning">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Après l'activation
                    </h6>
                    <ul class="mb-0 small ps-3">
                        <li class="mb-2">Le <strong>premier cycle</strong> sera créé automatiquement</li>
                        <li class="mb-2">Le client pourra effectuer ses <strong>cotisations</strong></li>
                        <li class="mb-2">Le compte passera en statut <strong class="text-success">Actif</strong></li>
                        <li class="mb-2">Les cycles seront gérés <strong>automatiquement</strong></li>
                        <li>Le dépôt initial sera comptabilisé dans le premier cycle</li>
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
    const mobileMoneySect ion = document.getElementById('mobile_money_section');
    const enableInitialDepositCheckbox = document.getElementById('enable_initial_deposit');
    const initialDepositSection = document.getElementById('initial_deposit_section');
    const initialDepositInput = document.getElementById('initial_deposit');
    const form = document.getElementById('activationForm');

    // Gérer l'affichage de la section Mobile Money
    function toggleMobileMoneySection() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (selectedMethod && selectedMethod.value === 'mobile_money') {
            mobileMoney Section.style.display = 'block';
        } else {
            mobileMoney Section.style.display = 'none';
            // Décocher les opérateurs si on change de méthode
            document.querySelectorAll('input[name="mobile_money_operator"]').forEach(input => {
                input.checked = false;
            });
        }
    }

    paymentMethodInputs.forEach(input => {
        input.addEventListener('change', toggleMobileMoneySection);
    });

    // Initialiser l'affichage au chargement
    toggleMobileMoneySection();

    // Gérer le dépôt initial
    enableInitialDepositCheckbox.addEventListener('change', function() {
        if (this.checked) {
            initialDepositSection.style.display = 'block';
            initialDepositInput.focus();
        } else {
            initialDepositSection.style.display = 'none';
            initialDepositInput.value = '';
        }
    });

    // Validation du formulaire
    form.addEventListener('submit', function(e) {
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
        if (!confirm('Êtes-vous sûr de vouloir activer ce compte ?')) {
            e.preventDefault();
            return false;
        }

        // Désactiver le bouton pour éviter les double-soumissions
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Activation en cours...';
    });

    // Formater automatiquement le montant
    initialDepositInput.addEventListener('blur', function() {
        const value = parseFloat(this.value) || 0;
        if (value > 0) {
            this.value = Math.round(value);
        }
    });
});
</script>
@endpush

@endsection
