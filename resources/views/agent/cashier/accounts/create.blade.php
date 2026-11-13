@extends('layouts.cashier')

@section('title', 'Ouverture de Compte - ' . $client->full_name)
@section('page-title', 'Ouverture de Compte au Guichet')
@section('page-subtitle', 'Création d\'un compte d\'épargne ou tontine pour un adhérent')

@section('content')
<div class="space-y-4" style="max-width: 1100px; margin: 0 auto;">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Ouverture d'un Nouveau Compte</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.clients.index') }}" class="text-decoration-none text-muted">Clients</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.clients.show', $client->id) }}" class="text-decoration-none text-muted">{{ $client->full_name }}</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Nouveau Compte</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.clients.show', $client->id) }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-arrow-left me-1"></i> Dossier Client
            </a>
            <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-cash-register me-1 text-primary"></i> Guichet Caisse
            </a>
        </div>
    </div>

    <!-- Alertes & Messages flash -->
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4 shadow-sm py-3 px-4 mb-4">
            <div class="d-flex align-items-center gap-2 fw-bold">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm py-3 px-4 mb-4">
            <div class="d-flex align-items-center gap-2 mb-1 fw-bold">
                <i class="fas fa-exclamation-triangle"></i> Veuillez corriger les erreurs ci-dessous :
            </div>
            <ul class="mb-0 ps-3 fs-8">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- COLONNE GAUCHE : IDENTITÉ DU CLIENT & COMPTES EXISTANTS -->
        <div class="col-12 col-lg-4">
            <div class="white-card p-4 mb-4">
                <div class="d-flex flex-column items-center text-center pb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm mx-auto mb-3" style="width: 64px; height: 64px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); font-size: 24px;">
                        {{ strtoupper(substr($client->first_name, 0, 1) . substr($client->last_name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold text-dark mb-1 fs-6">{{ $client->full_name }}</h5>
                    <span class="badge bg-light text-muted border font-monospace px-2 py-1 fs-8 mb-2">{{ $client->client_number }}</span>
                    <div>
                        <span class="badge {{ $client->kyc_status === 'approved' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} px-3 py-1 rounded-pill fw-semibold fs-8">
                            <i class="fas {{ $client->kyc_status === 'approved' ? 'fa-check-circle' : 'fa-clock' }} me-1"></i>
                            KYC {{ $client->kyc_status === 'approved' ? 'Vérifié' : 'En attente' }}
                        </span>
                    </div>
                </div>

                <div class="pt-3 space-y-2 fs-8">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Téléphone :</span>
                        <strong class="text-dark">{{ $client->phone }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Agence :</span>
                        <strong class="text-dark">{{ $client->agency->name ?? 'Agence Principale' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Adresse :</span>
                        <span class="text-dark text-truncate" style="max-width: 160px;" title="{{ $client->address }}">{{ $client->address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Inventaire des comptes déjà ouverts -->
            <div class="white-card p-4">
                <h6 class="fw-bold text-dark mb-3 fs-7 text-uppercase tracking-wider">
                    <i class="fas fa-wallet text-primary me-2"></i>Comptes Existants ({{ $client->accounts->count() }})
                </h6>
                @forelse($client->accounts as $acc)
                    <div class="p-2.5 rounded-3 border bg-light-subtle mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-8 font-monospace text-dark">{{ $acc->account_number }}</div>
                            <small class="badge {{ $acc->account_type === 'savings' ? 'bg-primary-subtle text-primary' : 'bg-purple-subtle text-purple' }} fs-9">
                                {{ $acc->account_type === 'savings' ? 'Épargne' : 'Tontine' }}
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-8 text-emerald-600">{{ number_format($acc->balance, 0, ',', ' ') }} F</div>
                            <span class="badge {{ $acc->status === 'active' ? 'bg-success' : 'bg-secondary' }} rounded-circle p-1" style="width: 8px; height: 8px; display: inline-block;"></span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3 text-muted fs-8">
                        Aucun compte existant pour ce client.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- COLONNE DROITE : CHOIX DU TYPE DE COMPTE & PARAMÈTRES -->
        <div class="col-12 col-lg-8">
            <form action="{{ route('caissier.accounts.store', $client->id) }}" method="POST" id="createAccountForm">
                @csrf

                <div class="white-card p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3 fs-6">
                        <i class="fas fa-layer-group text-emerald-600 me-2"></i>Sélectionnez le Type de Compte
                    </h5>

                    <div class="row g-3 mb-4">
                        <!-- Option Épargne -->
                        <div class="col-12 col-md-6">
                            <div class="account-type-card p-3 rounded-4 border-2 h-100 position-relative transition-all {{ $hasSavingsAccount ? 'border-light bg-light opacity-50' : (old('account_type', $selectedType) === 'savings' ? 'border-primary bg-primary-subtle' : 'border-light-subtle bg-white hover-border-primary') }}" id="card_savings">
                                <div class="form-check p-0">
                                    <input class="form-check-input visually-hidden" type="radio" name="account_type" id="type_savings" value="savings" {{ $hasSavingsAccount ? 'disabled' : (old('account_type', $selectedType) === 'savings' ? 'checked' : '') }}>
                                    <label class="form-check-label d-block cursor-pointer" for="type_savings">
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div class="rounded-3 p-2 bg-primary text-white fs-5" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-vault"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0 fs-6">Compte d'Épargne</h6>
                                                <span class="fs-8 text-muted">Compte de réserve liquide</span>
                                            </div>
                                        </div>
                                        <p class="fs-8 text-secondary mb-2">
                                            Dépôts et retraits libres au guichet. Frais d'ouverture institutionnels configurables.
                                        </p>
                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-8">
                                                Frais par défaut : {{ number_format($savingsActivationFee, 0, ',', ' ') }} FCFA
                                            </span>
                                            @if($hasSavingsAccount)
                                                <span class="badge bg-danger text-white fs-9">Déjà actif</span>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Option Tontine -->
                        <div class="col-12 col-md-6">
                            @php
                                $tontineDisabled = !$canOpenTontine || $hasUnfinishedCycle;
                            @endphp
                            <div class="account-type-card p-3 rounded-4 border-2 h-100 position-relative transition-all {{ $tontineDisabled ? 'border-light bg-light opacity-50' : (old('account_type', $selectedType) === 'tontine' ? 'border-success bg-success-subtle' : 'border-light-subtle bg-white hover-border-success') }}" id="card_tontine">
                                <div class="form-check p-0">
                                    <input class="form-check-input visually-hidden" type="radio" name="account_type" id="type_tontine" value="tontine" {{ $tontineDisabled ? 'disabled' : (old('account_type', $selectedType) === 'tontine' ? 'checked' : '') }}>
                                    <label class="form-check-label d-block cursor-pointer" for="type_tontine">
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div class="rounded-3 p-2 bg-emerald-600 text-white fs-5" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-piggy-bank"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0 fs-6">Compte Tontine</h6>
                                                <span class="fs-8 text-muted">Carnet de cotisations périodiques</span>
                                            </div>
                                        </div>
                                        <p class="fs-8 text-secondary mb-2">
                                            Souscription par carnet de 31 jours. Retrait au terme du cycle.
                                        </p>
                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                                Frais carnet : {{ number_format($tontineCarnetFee, 0, ',', ' ') }} FCFA
                                            </span>
                                            @if($tontineDisabled)
                                                <span class="badge bg-warning text-dark fs-9">Quota atteint</span>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BLOC CHAMPS COMPTE ÉPARGNE -->
                    <div id="section_savings" class="border rounded-4 p-4 bg-light-subtle mb-4" style="display: {{ old('account_type', $selectedType) === 'savings' ? 'block' : 'none' }};">
                        <div class="d-flex align-items-center gap-2 mb-3 text-primary fw-bold fs-7 pb-2 border-bottom">
                            <i class="fas fa-coins"></i> Paramètres Financiers - Compte d'Épargne
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">
                                    Frais d'Ouverture / Adhésion <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" name="activation_fee" id="savings_activation_fee" class="form-control form-control-solid fw-bold text-primary fs-6" value="{{ old('activation_fee', $savingsActivationFee) }}" required>
                                    <span class="input-group-text bg-white fw-bold text-muted">FCFA</span>
                                </div>
                                <span class="fs-8 text-muted mt-1 d-block">
                                    <i class="fas fa-info-circle text-primary me-1"></i>Montant par défaut : <strong>{{ number_format($savingsActivationFee, 0, ',', ' ') }} FCFA</strong>. Modifiable selon le profil du client.
                                </span>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">
                                    Premier Versement au Guichet (Optionnel)
                                </label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" name="initial_deposit" id="savings_initial_deposit" class="form-control form-control-solid fw-bold text-success fs-6" placeholder="Ex: 5000" value="{{ old('initial_deposit') }}">
                                    <span class="input-group-text bg-white fw-bold text-muted">FCFA</span>
                                </div>
                                <span class="fs-8 text-muted mt-1 d-block">
                                    Dépôt en espèces crédité immédiatement sur le nouveau compte.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- BLOC CHAMPS COMPTE TONTINE -->
                    <div id="section_tontine" class="border rounded-4 p-4 bg-light-subtle mb-4" style="display: {{ old('account_type', $selectedType) === 'tontine' ? 'block' : 'none' }};">
                        <div class="d-flex align-items-center gap-2 mb-3 text-emerald-700 fw-bold fs-7 pb-2 border-bottom">
                            <i class="fas fa-rotate"></i> Paramètres de Cotisation - Compte Tontine
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">
                                    Frais de Carnet de Tontine <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" name="tontine_carnet_fee" id="tontine_carnet_fee" class="form-control form-control-solid fw-bold text-purple-700 fs-6" value="{{ old('tontine_carnet_fee', $tontineCarnetFee) }}">
                                    <span class="input-group-text bg-white fw-bold text-muted">FCFA</span>
                                </div>
                                <span class="fs-8 text-muted mt-1 d-block">
                                    Défaut système : <strong>{{ number_format($tontineCarnetFee, 0, ',', ' ') }} FCFA</strong> (Modifiable).
                                </span>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">
                                    Mise de Cotisation par Période <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" step="any" min="100" name="target_amount" id="target_amount" class="form-control form-control-solid fw-bold text-dark fs-6" value="{{ old('target_amount', 1000) }}">
                                    <span class="input-group-text bg-white fw-bold text-muted">FCFA</span>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">Fréquence de Mise</label>
                                <select name="payment_frequency" id="payment_frequency" class="form-select form-control-solid rounded-3">
                                    <option value="daily" {{ old('payment_frequency') == 'daily' ? 'selected' : '' }}>Quotidienne (31 jours / mois)</option>
                                    <option value="weekly" {{ old('payment_frequency') == 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                                    <option value="monthly" {{ old('payment_frequency') == 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">Durée du Cycle (Mois)</label>
                                <div class="input-group">
                                    <input type="number" name="cycle_duration_months" id="cycle_duration_months" class="form-control form-control-solid text-center fw-bold" min="1" max="24" value="{{ old('cycle_duration_months', 1) }}">
                                    <span class="input-group-text bg-white fw-bold text-muted">Mois</span>
                                </div>
                            </div>

                            <!-- Simulation Tontine -->
                            <div class="col-12">
                                <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fs-8 text-muted">Total Périodes à cotiser :</span>
                                        <span class="fw-bold text-dark fs-8" id="total_periods">31</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fs-8 text-muted">Montant Total Estimé du Cycle :</span>
                                        <span class="fw-bold text-emerald-700 fs-7" id="total_sum">31 000 FCFA</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">
                                    Premier Versement Tontine (Optionnel)
                                </label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" name="tontine_initial_deposit" id="tontine_initial_deposit" class="form-control form-control-solid" placeholder="Ex: 1000" value="{{ old('tontine_initial_deposit') }}">
                                    <span class="input-group-text bg-white fw-bold text-muted">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOUTON DE VALIDATION -->
                    <div class="d-flex align-items-center justify-content-between pt-4 border-top">
                        <a href="{{ route('caissier.clients.show', $client->id) }}" class="btn btn-light fw-bold text-muted px-4 py-2 rounded-3">
                            <i class="fas fa-times me-1"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2.5 rounded-3 shadow-sm" id="btnSubmit">
                            <i class="fas fa-check-double me-1"></i> Valider l'Ouverture & Encaisser
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioSavings = document.getElementById('type_savings');
    const radioTontine = document.getElementById('type_tontine');
    const cardSavings = document.getElementById('card_savings');
    const cardTontine = document.getElementById('card_tontine');
    const sectionSavings = document.getElementById('section_savings');
    const sectionTontine = document.getElementById('section_tontine');

    const targetAmount = document.getElementById('target_amount');
    const frequency = document.getElementById('payment_frequency');
    const duration = document.getElementById('cycle_duration_months');
    const totalSum = document.getElementById('total_sum');
    const totalPeriods = document.getElementById('total_periods');

    function toggleAccountSections() {
        if (radioSavings && radioSavings.checked) {
            sectionSavings.style.display = 'block';
            sectionTontine.style.display = 'none';
            cardSavings.classList.add('border-primary', 'bg-primary-subtle');
            cardTontine.classList.remove('border-success', 'bg-success-subtle');
            
            // Ajuster les champs required
            document.getElementById('savings_activation_fee').setAttribute('required', 'required');
            if (targetAmount) targetAmount.removeAttribute('required');
        } else if (radioTontine && radioTontine.checked) {
            sectionSavings.style.display = 'none';
            sectionTontine.style.display = 'block';
            cardTontine.classList.add('border-success', 'bg-success-subtle');
            cardSavings.classList.remove('border-primary', 'bg-primary-subtle');
            
            document.getElementById('savings_activation_fee').removeAttribute('required');
            if (targetAmount) targetAmount.setAttribute('required', 'required');
            updateTontineSimulation();
        }
    }

    if (radioSavings) radioSavings.addEventListener('change', toggleAccountSections);
    if (radioTontine) radioTontine.addEventListener('change', toggleAccountSections);

    function updateTontineSimulation() {
        if (!targetAmount) return;
        const amount = parseFloat(targetAmount.value) || 0;
        const dur = parseInt(duration.value) || 0;
        const freq = frequency.value;
        
        let periods = 0;
        switch(freq) {
            case 'daily': periods = dur * 31; break;
            case 'weekly': periods = Math.round((dur * 52) / 12); break;
            case 'monthly': periods = dur; break;
        }

        const total = amount * periods;
        if (totalSum) totalSum.innerText = total.toLocaleString('fr-FR') + ' FCFA';
        if (totalPeriods) totalPeriods.innerText = periods;
    }

    if (targetAmount) {
        [targetAmount, frequency, duration].forEach(el => {
            el.addEventListener('input', updateTontineSimulation);
        });
    }

    toggleAccountSections();

    // Gestion de la soumission pour mapper les champs appropriés
    document.getElementById('createAccountForm').addEventListener('submit', function(e) {
        const isTontine = radioTontine && radioTontine.checked;
        if (isTontine) {
            // Transférer les valeurs tontine dans les champs form standard
            const carnetFeeVal = document.getElementById('tontine_carnet_fee').value;
            const tontineInitDepVal = document.getElementById('tontine_initial_deposit').value;
            document.getElementById('savings_activation_fee').value = carnetFeeVal;
            document.getElementById('savings_initial_deposit').value = tontineInitDepVal;
        }

        const btn = document.getElementById('btnSubmit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Ouverture en cours...';
        btn.disabled = true;
    });
});
</script>
@endpush
@endsection

