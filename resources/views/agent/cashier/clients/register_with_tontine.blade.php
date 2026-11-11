@extends('layouts.cashier')

@section('title', 'Inscription Express & Tontine')
@section('page-title', 'Inscription Express Adhérent')
@section('page-subtitle', 'Ouverture conjointe d\'un dossier adhérent et souscription tontine au guichet')

@section('content')
<div class="space-y-4" style="max-width: 1100px; margin: 0 auto;">

    <!-- EN-TÊTE DE PAGE & ACTIONS -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1 fs-4">Inscription Express Adhérent & Tontine</h2>
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
            <div class="col-12 col-lg-7">
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

            <!-- COLONNE DROITE : SOUSCRIPTION TONTINE & DÉPÔT INITIAL -->
            <div class="col-12 col-lg-5">
                <div class="white-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 44px; height: 44px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                                    <i class="fas fa-piggy-bank"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0 fs-6">Compte Tontine Associé</h5>
                                    <div class="text-muted fs-8">Paramètres de cotisation</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">Mise de Cotisation (FCFA) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="any" min="200" name="target_amount" id="target_amount" class="form-control form-control-solid fs-5 fw-bold text-dark" placeholder="Ex: 1000" value="{{ old('target_amount', 1000) }}" required>
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
                                    <input type="number" name="cycle_duration_months" id="cycle_duration_months" class="form-control form-control-solid text-center fw-bold" min="1" max="24" value="{{ old('cycle_duration_months', 1) }}" required>
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

                            <!-- Frais de carnet tontine & Dépôt initial -->
                            <div class="col-12 col-sm-6 mt-3">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">Frais de Carnet Tontine <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" name="carnet_fee" id="carnet_fee" class="form-control form-control-solid fw-bold text-purple-700" placeholder="300" value="{{ old('carnet_fee', 300) }}" required>
                                    <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                </div>
                                <span class="fs-9 text-muted">Défaut institutionnel : 300 FCFA (Modifiable).</span>
                            </div>

                            <div class="col-12 col-sm-6 mt-3">
                                <label class="form-label text-secondary fw-semibold fs-8 mb-1">Dépôt Initial au Guichet (Optionnel)</label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" name="initial_deposit" class="form-control form-control-solid" placeholder="Ex: 1000" value="{{ old('initial_deposit') }}">
                                    <span class="input-group-text bg-light fw-bold text-muted">FCFA</span>
                                </div>
                                <span class="fs-9 text-muted">Si renseigné, quittance de versement générée.</span>
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
    const targetAmount = document.getElementById('target_amount');
    const frequency = document.getElementById('payment_frequency');
    const duration = document.getElementById('cycle_duration_months');
    const totalSum = document.getElementById('total_sum');
    const totalPeriods = document.getElementById('total_periods');

    function updateSimulation() {
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
        el.addEventListener('input', updateSimulation);
    });

    updateSimulation();

    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Traitement...';
        btn.disabled = true;
    });
});
</script>
@endpush
@endsection
