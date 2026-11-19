@extends('layouts.cashier')

@section('title', 'Inscription Express Adhérent')
@section('page-title', 'Inscription Express Adhérent')
@section('page-subtitle', 'Création de dossier client et souscription aux comptes Épargne et/ou Tontine')

@section('content')
<div class="space-y-4" style="max-width: 1150px; margin: 0 auto;">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Inscription Express Adhérent</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-8">
                    <li class="breadcrumb-item"><a href="{{ route('caissier.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('caissier.clients.index') }}" class="text-decoration-none text-muted">Clients</a></li>
                    <li class="breadcrumb-item active text-emerald-600 fw-semibold" aria-current="page">Inscription Express</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('caissier.clients.index') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-arrow-left me-1"></i> Répertoire Clients
            </a>
            <a href="{{ route('caissier.terminal') }}" class="btn btn-sm btn-light border fw-bold rounded-3 px-3 py-2 text-dark">
                <i class="fas fa-cash-register me-1 text-primary"></i> Guichet Caisse
            </a>
        </div>
    </div>

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

    <form action="{{ route('caissier.clients.register-with-tontine.store') }}" method="POST" id="registerForm">
        @csrf
        <div class="row g-4">
            <!-- COLONNE GAUCHE : IDENTITÉ DU CLIENT -->
            <div class="col-12 col-lg-6">
                <div class="white-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 44px; height: 44px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0 fs-6">Informations de l'Adhérent</h5>
                                <div class="text-muted fs-8">Coordonnées personnelles et état civil</div>
                            </div>
                        </div>
                        <span class="badge bg-emerald-50 text-emerald-700 px-3 py-2 rounded-pill fw-bold fs-9 border border-emerald-200">
                            <i class="fas fa-check-circle me-1"></i> KYC Guichet
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-secondary fw-semibold fs-8 mb-1">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control form-control-solid rounded-3 @error('first_name') is-invalid @enderror" placeholder="Ex: Jean" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-secondary fw-semibold fs-8 mb-1">Nom de famille <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control form-control-solid rounded-3 @error('last_name') is-invalid @enderror" placeholder="Ex: KOFFI" value="{{ old('last_name') }}" required>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label text-secondary fw-semibold fs-8 mb-1">Numéro de Téléphone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 fw-semibold text-muted">+228</span>
                                <input type="tel" name="phone" class="form-control form-control-solid rounded-end-3 @error('phone') is-invalid @enderror" placeholder="90 00 00 00" value="{{ old('phone') }}" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label text-secondary fw-semibold fs-8 mb-1">Genre</label>
                            <select name="gender" class="form-select form-control-solid rounded-3">
                                <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary fw-semibold fs-8 mb-1">Adresse Complète / Domicile <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control form-control-solid rounded-3 @error('address') is-invalid @enderror" rows="2" placeholder="Quartier, Rue, Repère domicile..." required>{{ old('address') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-3">
                        <i class="fas fa-info-circle text-primary fs-5"></i>
                        <div class="fs-8 text-muted">
                            Un mot de passe sécurisé temporaire par défaut (<strong class="text-dark">12@4</strong>) sera généré pour l'accès espace adhérent.
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : SÉLECTION DES COMPTES À CRÉER -->
            <div class="col-12 col-lg-6">
                <div class="white-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 44px; height: 44px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0 fs-6">Comptes & Souscriptions</h5>
                                    <div class="text-muted fs-8">Sélectionnez les services souhaités par l'adhérent</div>
                                </div>
                            </div>
                        </div>

                        <!-- 1. COMPTE ÉPARGNE (OPTIONNEL / RECOMMANDÉ) -->
                        <div class="card border rounded-3 p-3 mb-3 shadow-none" id="savingsCard" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 36px; height: 36px; background: #10b981;">
                                        <i class="fas fa-wallet fs-7"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="fw-bold text-dark mb-0 fs-7">Compte d'Épargne</h6>
                                            <span class="badge bg-emerald-100 text-emerald-800 fs-9 py-1 px-2 rounded-pill">Conseillé</span>
                                        </div>
                                        <div class="text-muted fs-9">Compte de base pour dépôts et retraits libres</div>
                                    </div>
                                </div>
                                <div class="form-check form-switch fs-5 mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="createSavingsSwitch" name="create_savings" value="1" {{ old('create_savings', '1') ? 'checked' : '' }} onchange="toggleSavingsSection()">
                                </div>
                            </div>

                            <!-- SOUS-CHAMPS ÉPARGNE -->
                            <div id="savingsFields" class="row g-2 mt-2 pt-2 border-top {{ old('create_savings', '1') ? '' : 'd-none' }}">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-secondary fw-semibold fs-8 mb-1">Frais d'Adhésion (FCFA)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0" name="savings_activation_fee" id="savings_activation_fee" class="form-control form-control-solid fw-bold text-dark" placeholder="7000" value="{{ old('savings_activation_fee', $savingsActivationFee ?? 7000) }}">
                                        <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                    </div>
                                    <span class="fs-9 text-muted">Ex: 7 000 FCFA (0 si exonéré).</span>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-secondary fw-semibold fs-8 mb-1">Premier Dépôt Épargne</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="any" min="0" name="savings_initial_deposit" id="savings_initial_deposit" class="form-control form-control-solid" placeholder="Optionnel" value="{{ old('savings_initial_deposit') }}">
                                        <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                    </div>
                                    <span class="fs-9 text-muted">Versement initial guichet.</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. COMPTE TONTINE (OPTIONNEL AVEC INTERRUPTEUR) -->
                        <div class="card border rounded-3 p-3 shadow-none" id="tontineCard" style="background: #ffffff; border-color: #cbd5e1 !important;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 36px; height: 36px; background: #0284c7;">
                                        <i class="fas fa-piggy-bank fs-7"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0 fs-7">Ouvrir également un Compte Tontine</h6>
                                        <div class="text-muted fs-9">Cochez si le client souhaite cotiser à une tontine</div>
                                    </div>
                                </div>
                                <div class="form-check form-switch fs-5 mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="createTontineSwitch" name="create_tontine" value="1" {{ old('create_tontine') ? 'checked' : '' }} onchange="toggleTontineSection()">
                                </div>
                            </div>

                            <!-- SOUS-CHAMPS TONTINE (AFFICHÉS UNIQUEMENT SI COCHÉ) -->
                            <div id="tontineFields" class="mt-3 pt-3 border-top {{ old('create_tontine') ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label text-secondary fw-semibold fs-8 mb-1">Mise de Cotisation (FCFA) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="100" name="target_amount" id="target_amount" class="form-control form-control-solid fs-5 fw-bold text-dark" placeholder="Ex: 1000" value="{{ old('target_amount', 1000) }}">
                                            <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label class="form-label text-secondary fw-semibold fs-8 mb-1">Fréquence de Mise</label>
                                        <select name="payment_frequency" id="payment_frequency" class="form-select form-control-solid rounded-3">
                                            <option value="daily" {{ old('payment_frequency') == 'daily' ? 'selected' : '' }}>Quotidienne</option>
                                            <option value="weekly" {{ old('payment_frequency') == 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                                            <option value="monthly" {{ old('payment_frequency') == 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label class="form-label text-secondary fw-semibold fs-8 mb-1">Durée du Cycle</label>
                                        <div class="input-group">
                                            <input type="number" name="cycle_duration_months" id="cycle_duration_months" class="form-control form-control-solid text-center fw-bold" min="1" max="24" value="{{ old('cycle_duration_months', 1) }}">
                                            <span class="input-group-text bg-light fw-bold text-muted">Mois</span>
                                        </div>
                                    </div>

                                    <!-- Carte de calcul estimatif -->
                                    <div class="col-12">
                                        <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fs-8 text-muted">Total Périodes :</span>
                                                <span class="fw-bold text-dark fs-8" id="total_periods">31</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fs-8 text-muted">Objectif de Fin de Cycle :</span>
                                                <span class="fw-bold text-emerald-700 fs-7" id="total_sum">31 000 FCFA</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Frais de carnet tontine & Dépôt initial tontine -->
                                    <div class="col-12 col-sm-6 mt-2">
                                        <label class="form-label text-secondary fw-semibold fs-8 mb-1">Frais de Carnet Tontine <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="0" name="carnet_fee" id="carnet_fee" class="form-control form-control-solid fw-bold text-purple-700" placeholder="300" value="{{ old('carnet_fee', $tontineCarnetFee ?? 300) }}">
                                            <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                        </div>
                                        <span class="fs-9 text-muted">Défaut institutionnel : 300 FCFA (Modifiable).</span>
                                    </div>

                                    <div class="col-12 col-sm-6 mt-2">
                                        <label class="form-label text-secondary fw-semibold fs-8 mb-1">Dépôt Initial Tontine (Optionnel)</label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="0" name="tontine_initial_deposit" id="tontine_initial_deposit" class="form-control form-control-solid" placeholder="Ex: 1000" value="{{ old('tontine_initial_deposit', old('initial_deposit')) }}">
                                            <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                        </div>
                                        <span class="fs-9 text-muted">Si renseigné, quittance générée.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOUTONS D'ACTION -->
                    <div class="d-flex align-items-center justify-content-between pt-4 mt-4 border-top">
                        <a href="{{ route('caissier.clients.index') }}" class="btn btn-light fw-bold text-muted px-3 py-2 rounded-3">
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2 rounded-3 shadow-sm" id="btnSubmit">
                            <i class="fas fa-check-double me-1"></i> Valider l'Inscription
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const savingsSwitch = document.getElementById('createSavingsSwitch');
    const tontineSwitch = document.getElementById('createTontineSwitch');
    const savingsFields = document.getElementById('savingsFields');
    const tontineFields = document.getElementById('tontineFields');
    const targetAmount = document.getElementById('target_amount');
    const frequency = document.getElementById('payment_frequency');
    const duration = document.getElementById('cycle_duration_months');
    const carnetFee = document.getElementById('carnet_fee');
    const totalSum = document.getElementById('total_sum');
    const totalPeriods = document.getElementById('total_periods');
    const registerForm = document.getElementById('registerForm');
    const btnSubmit = document.getElementById('btnSubmit');

    window.toggleSavingsSection = function() {
        if (savingsSwitch.checked) {
            savingsFields.classList.remove('d-none');
        } else {
            savingsFields.classList.add('d-none');
        }
    };

    window.toggleTontineSection = function() {
        if (tontineSwitch.checked) {
            tontineFields.classList.remove('d-none');
            targetAmount.setAttribute('required', 'required');
            duration.setAttribute('required', 'required');
            carnetFee.setAttribute('required', 'required');
            updateSimulation();
        } else {
            tontineFields.classList.add('d-none');
            targetAmount.removeAttribute('required');
            duration.removeAttribute('required');
            carnetFee.removeAttribute('required');
        }
    };

    function updateSimulation() {
        if (!targetAmount || !duration || !frequency) return;
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
        totalSum.innerText = total.toLocaleString('fr-FR') + ' FCFA';
        totalPeriods.innerText = periods;
    }

    [targetAmount, frequency, duration].forEach(el => {
        if (el) el.addEventListener('input', updateSimulation);
    });

    // Initialisation de l'état des sections
    toggleSavingsSection();
    toggleTontineSection();

    registerForm.addEventListener('submit', function(e) {
        if (!savingsSwitch.checked && !tontineSwitch.checked) {
            e.preventDefault();
            alert("Veuillez sélectionner au moins un type de compte à ouvrir (Épargne et/ou Tontine) pour cet adhérent.");
            return false;
        }

        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Traitement...';
        btnSubmit.disabled = true;
    });
});
</script>
@endpush
@endsection
