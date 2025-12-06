@extends('layouts.agent')

@section('title', 'Créer un Compte Tontine')

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
                <a href="{{ route('agent.clients.show', $client->id) }}">
                    {{ $client->first_name }} {{ $client->last_name }}
                </a>
            </li>
            <li class="breadcrumb-item active">Créer un compte tontine</li>
        </ol>
    </nav>

    <!-- En-tête -->
    <div class="mb-4 row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Créer un Compte Tontine
                    </h2>
                    <p class="mb-0 text-muted">
                        Pour {{ $client->first_name }} {{ $client->last_name }}
                        <span class="badge bg-info-soft text-info ms-2">{{ $client->client_number }}</span>
                    </p>
                </div>
                <a href="{{ route('agent.clients.show', $client->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au client
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
        <!-- Formulaire principal -->
        <div class="col-lg-8">
            <div class="border-0 shadow-sm card">
                <div class="py-3 bg-white card-header border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        Informations du Compte
                    </h5>
                </div>
                <div class="p-4 card-body">
                    <form method="POST" action="{{ route('agent.accounts.store', $client->id) }}" id="createAccountForm">
                        @csrf

                        <!-- Montant cible -->
                        <div class="mb-4">
                            <label for="target_amount" class="form-label fw-bold">
                                <i class="fas fa-bullseye text-primary me-2"></i>
                                Montant Cible par Période <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="number"
                                       class="form-control @error('target_amount') is-invalid @enderror"
                                       id="target_amount"
                                       name="target_amount"
                                       min="100"
                                       step="50"
                                       value="{{ old('target_amount', 200) }}"
                                       placeholder="1000"
                                       required>
                                <span class="bg-white input-group-text">FCFA</span>
                                @error('target_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Montant minimum : 100 FCFA
                            </small>
                        </div>

                        <!-- Durée du cycle -->
                        <div class="mb-4">
                            <label for="cycle_duration_months" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                Durée du Cycle (en mois) <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg @error('cycle_duration_months') is-invalid @enderror"
                                    id="cycle_duration_months"
                                    name="cycle_duration_months"
                                    required>
                                <option value="">-- Sélectionnez la durée --</option>
                                @for($i = 1; $i <= 24; $i++)
                                    <option value="{{ $i }}" {{ old('cycle_duration_months', 12) == $i ? 'selected' : '' }}>
                                        {{ $i }} mois
                                    </option>
                                @endfor
                            </select>
                            @error('cycle_duration_months')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Durée totale de la tontine (1 à 24 mois)
                            </small>
                        </div>

                        <!-- Fréquence de paiement -->
                        <div class="mb-4">
                            <label for="payment_frequency" class="form-label fw-bold">
                                <i class="fas fa-sync-alt text-primary me-2"></i>
                                Fréquence de Paiement <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg @error('payment_frequency') is-invalid @enderror"
                                    id="payment_frequency"
                                    name="payment_frequency"
                                    required>
                                <option value="">-- Sélectionnez la fréquence --</option>
                                <option value="daily" {{ old('payment_frequency') == 'daily' ? 'selected' : '' }}>
                                    <i class="fas fa-sun"></i> Quotidien (chaque jour)
                                </option>
                                <option value="weekly" {{ old('payment_frequency', 'weekly') == 'weekly' ? 'selected' : '' }}>
                                    <i class="fas fa-calendar-week"></i> Hebdomadaire (chaque semaine)
                                </option>
                                <option value="monthly" {{ old('payment_frequency') == 'monthly' ? 'selected' : '' }}>
                                    <i class="fas fa-calendar"></i> Mensuel (chaque mois)
                                </option>
                            </select>
                            @error('payment_frequency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                À quelle fréquence le client effectuera ses cotisations
                            </small>
                        </div>

                        <!-- Ligne de séparation -->
                        <hr class="my-4">

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('agent.clients.show', $client->id) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="shadow-sm btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Créer le Compte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panneau d'informations et simulation -->
        <div class="col-lg-4">
            <!-- Informations client -->
            <div class="mb-4 border-0 shadow-sm card">
                <div class="py-3 text-white border-0 card-header bg-gradient-primary">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Informations Client
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Nom complet</small>
                        <div class="fw-bold">{{ $client->first_name }} {{ $client->last_name }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Numéro client</small>
                        <div class="font-monospace">{{ $client->client_number }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Téléphone</small>
                        <div><i class="fas fa-phone me-2"></i>{{ $client->phone }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="mb-1 text-muted d-block">Statut KYC</small>
                        @if($client->kyc_status === 'approved')
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Approuvé
                            </span>
                        @else
                            <span class="badge bg-warning">
                                <i class="fas fa-clock me-1"></i>{{ ucfirst($client->kyc_status) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <small class="mb-1 text-muted d-block">Comptes existants</small>
                        <div class="fw-bold text-primary">{{ $client->accounts->count() }} compte(s)</div>
                    </div>
                </div>
            </div>

            <!-- Simulateur en temps réel -->
            <div class="mb-4 border-0 shadow-sm card">
                <div class="py-3 card-header bg-light border-bottom">
                    <h6 class="mb-0">
                        <i class="fas fa-calculator text-primary me-2"></i>
                        Simulation
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 border-0 alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Les valeurs se mettent à jour automatiquement</small>
                    </div>

                    <div class="p-3 mb-3 rounded bg-light">
                        <small class="mb-1 text-muted d-block">Nombre de périodes</small>
                        <h4 class="mb-0 text-primary fw-bold" id="total_periods">-</h4>
                        <small class="text-muted" id="period_label">périodes</small>
                    </div>

                    <div class="p-3 mb-3 rounded bg-light">
                        <small class="mb-1 text-muted d-block">Montant par période</small>
                        <h4 class="mb-0 text-success fw-bold" id="amount_per_period">-</h4>
                        <small class="text-muted">FCFA</small>
                    </div>

                    <div class="p-3 text-white rounded bg-gradient-primary">
                        <small class="mb-1 opacity-75 d-block">Total attendu</small>
                        <h3 class="mb-0 fw-bold" id="total_expected">-</h3>
                        <small class="opacity-75">FCFA</small>
                    </div>

                    <hr class="my-3">

                    <div class="small text-muted">
                        <div class="mb-2">
                            <i class="fas fa-calendar-day me-2 text-primary"></i>
                            <span>Début : <strong id="start_date">Aujourd'hui</strong></span>
                        </div>
                        <div>
                            <i class="fas fa-calendar-check me-2 text-success"></i>
                            <span>Fin estimée : <strong id="end_date">-</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aide -->
            <div class="border-0 border-4 shadow-sm card border-start border-primary">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-question-circle text-primary me-2"></i>
                        Aide
                    </h6>
                    <ul class="mb-0 small ps-3">
                        <li class="mb-2">Le compte sera créé en statut <strong>suspendu</strong></li>
                        <li class="mb-2">Vous devrez l'<strong>activer</strong> avant utilisation</li>
                        <li class="mb-2">Le premier cycle démarre à l'activation</li>
                        <li class="mb-2">Les cotisations peuvent être effectuées à tout moment</li>
                        <li>Le système gère automatiquement les cycles multiples</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-info-soft {
        background-color: #dbeafe;
    }

    .text-info {
        color: #3b82f6;
    }

    .font-monospace {
        font-family: 'Courier New', monospace;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    .border-4 {
        border-width: 4px !important;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetAmountInput = document.getElementById('target_amount');
    const cycleDurationSelect = document.getElementById('cycle_duration_months');
    const paymentFrequencySelect = document.getElementById('payment_frequency');

    // Éléments de simulation
    const totalPeriodsEl = document.getElementById('total_periods');
    const periodLabelEl = document.getElementById('period_label');
    const amountPerPeriodEl = document.getElementById('amount_per_period');
    const totalExpectedEl = document.getElementById('total_expected');
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');

    function updateSimulation() {
        const targetAmount = parseFloat(targetAmountInput.value) || 0;
        const cycleDuration = parseInt(cycleDurationSelect.value) || 0;
        const frequency = paymentFrequencySelect.value;

        if (targetAmount <= 0 || cycleDuration <= 0 || !frequency) {
            resetSimulation();
            return;
        }

        // Calculer le nombre de périodes
        let totalPeriods = 0;
        let periodLabel = 'périodes';

        switch(frequency) {
            case 'daily':
                totalPeriods = cycleDuration * 30; // Approximation
                periodLabel = 'jours';
                break;
            case 'weekly':
                totalPeriods = Math.floor(cycleDuration * 4.33); // Approximation
                periodLabel = 'semaines';
                break;
            case 'monthly':
                totalPeriods = cycleDuration;
                periodLabel = 'mois';
                break;
        }

        const totalExpected = targetAmount * totalPeriods;

        // Mettre à jour l'affichage
        totalPeriodsEl.textContent = totalPeriods.toLocaleString('fr-FR');
        periodLabelEl.textContent = periodLabel;
        amountPerPeriodEl.textContent = targetAmount.toLocaleString('fr-FR');
        totalExpectedEl.textContent = totalExpected.toLocaleString('fr-FR');

        // Calculer les dates
        const today = new Date();
        const endDate = new Date();
        endDate.setMonth(endDate.getMonth() + cycleDuration);

        startDateEl.textContent = formatDate(today);
        endDateEl.textContent = formatDate(endDate);
    }

    function resetSimulation() {
        totalPeriodsEl.textContent = '-';
        periodLabelEl.textContent = 'périodes';
        amountPerPeriodEl.textContent = '-';
        totalExpectedEl.textContent = '-';
        startDateEl.textContent = 'Aujourd\'hui';
        endDateEl.textContent = '-';
    }

    function formatDate(date) {
        const options = { day: '2-digit', month: 'long', year: 'numeric' };
        return date.toLocaleDateString('fr-FR', options);
    }

    // Écouter les changements
    targetAmountInput.addEventListener('input', updateSimulation);
    cycleDurationSelect.addEventListener('change', updateSimulation);
    paymentFrequencySelect.addEventListener('change', updateSimulation);

    // Simulation initiale
    updateSimulation();

    // Validation du formulaire
    const form = document.getElementById('createAccountForm');
    form.addEventListener('submit', function(e) {
        const targetAmount = parseFloat(targetAmountInput.value) || 0;

        if (targetAmount < 200) {
            e.preventDefault();
            alert('Le montant cible doit être d\'au moins 200 FCFA');
            targetAmountInput.focus();
            return false;
        }
    });

    // Formater automatiquement le montant
    targetAmountInput.addEventListener('blur', function() {
        const value = parseFloat(this.value) || 0;
        if (value > 0) {
            this.value = Math.round(value);
        }
    });
});
</script>
@endpush

@endsection
