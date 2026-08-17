@extends('layouts.cashier')

@section('title', 'Inscription Express & Tontine')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header d-flex align-items-center justify-content-between p-4 rounded-4 shadow-lg" style="background: linear-gradient(135deg, #1a2332 0%, #0d1b2a 100%); border: 1px solid rgba(0, 209, 178, 0.2);">
                <div class="d-flex align-items-center gap-3">
                    <div class="header-icon bg-primary-gradient rounded-3 p-3 text-white shadow-brand">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold text-white">Inscription Express</h2>
                        <p class="text-teal-accent mb-0">Créer un client et son compte tontine en une seule étape</p>
                    </div>
                </div>
                <div class="d-none d-md-block">
                    <span class="badge bg-blur-teal p-2 px-3 rounded-pill text-teal-accent border border-teal-soft">
                        <i class="fas fa-microchip me-2"></i>Traitement Instantané
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('caissier.clients.register-with-tontine.store') }}" method="POST" id="registerForm">
        @csrf
        <div class="row g-4">
            <!-- Colonne Gauche : Client -->
            <div class="col-lg-7">
                <div class="card glass-card h-100 border-0 shadow-lg">
                    <div class="card-header bg-transparent border-bottom border-white-5 p-4">
                        <h5 class="mb-0 text-white fw-bold"><i class="fas fa-id-card me-2 text-teal-accent"></i>Informations du Client</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control custom-input @error('first_name') is-invalid @enderror" placeholder="Ex: Jean" value="{{ old('first_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Nom de famille <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control custom-input @error('last_name') is-invalid @enderror" placeholder="Ex: KOFFI" value="{{ old('last_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Numéro de Téléphone <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-0 text-white-50">+228</span>
                                    <input type="tel" name="phone" class="form-control custom-input @error('phone') is-invalid @enderror" placeholder="90000000" value="{{ old('phone') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Genre</label>
                                <select name="gender" class="form-select custom-input">
                                    <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Masculin</option>
                                    <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Féminin</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-white-50 small fw-bold">Adresse Complète <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control custom-input @error('address') is-invalid @enderror" rows="2" placeholder="Quartier, Rue, Maison..." required>{{ old('address') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 p-3 rounded-4 bg-info-soft d-flex align-items-center gap-3">
                            <i class="fas fa-info-circle text-info fa-lg"></i>
                            <div class="small text-white-50">
                                Un mot de passe par défaut <strong>12@4</strong> sera attribué au client. Il pourra le modifier ultérieurement via l'application.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Tontine -->
            <div class="col-lg-5">
                <div class="card glass-card h-100 border-0 shadow-lg">
                    <div class="card-header bg-transparent border-bottom border-white-5 p-4">
                        <h5 class="mb-0 text-white fw-bold"><i class="fas fa-coins me-2 text-warning"></i>Ouverture Compte Tontine</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-white-50 small fw-bold">Mise (Montant par période) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="target_amount" id="target_amount" class="form-control custom-input @error('target_amount') is-invalid @enderror" min="100" step="50" value="{{ old('target_amount', 100) }}" required>
                                    <span class="input-group-text bg-dark border-0 text-white-50">FCFA</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Fréquence <span class="text-danger">*</span></label>
                                <select name="payment_frequency" id="payment_frequency" class="form-select custom-input" required>
                                    <option value="daily" {{ old('payment_frequency') == 'daily' ? 'selected' : '' }}>Quotidien</option>
                                    <option value="weekly" {{ old('payment_frequency', 'weekly') == 'weekly' ? 'selected' : '' }}>Hebdo</option>
                                    <option value="monthly" {{ old('payment_frequency') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small fw-bold">Durée (Mois) <span class="text-danger">*</span></label>
                                <select name="cycle_duration_months" id="cycle_duration_months" class="form-select custom-input" required>
                                    @for($i=1; $i<=24; $i++)
                                        <option value="{{ $i }}" {{ old('cycle_duration_months', 12) == $i ? 'selected' : '' }}>{{ $i }} mois</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12 mt-4">
                                <div class="summary-box p-3 rounded-4 bg-dark-soft border border-info-soft">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-white-50 small">Total à épargner :</span>
                                        <span class="text-teal-accent fw-bold h5 mb-0" id="total_sum">0 FCFA</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-white-50 small">Nombre de cotisations :</span>
                                        <span class="text-white fw-bold" id="total_periods">0</span>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="border-white-10 my-4">

                            <div class="col-12">
                                <label class="form-label text-teal-accent small fw-bold">Dépôt Initial (Optionnel)</label>
                                <div class="input-group">
                                    <input type="number" name="initial_deposit" class="form-control custom-input border-teal-soft" placeholder="Ex: 500" value="{{ old('initial_deposit') }}">
                                    <span class="input-group-text bg-dark border-0 text-teal-accent">FCFA</span>
                                </div>
                                <small class="text-white-50 opacity-50">Si rempli, une transaction de dépôt sera créée immédiatement.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="col-12">
                <div class="d-flex gap-3 justify-content-end mt-2">
                    <button type="reset" class="btn btn-outline-white btn-lg rounded-pill px-5">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary-gradient btn-lg rounded-pill px-5 shadow-brand fw-bold">
                        <i class="fas fa-check-double me-2"></i> VALIDER L'INSCRIPTION
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .glass-card {
        background: rgba(26, 35, 50, 0.7);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 24px;
    }

    .custom-input {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: white !important;
        border-radius: 12px;
        padding: 12px 16px;
        transition: all 0.3s;
    }

    .custom-input:focus {
        border-color: #00d1b2 !important;
        box-shadow: 0 0 0 4px rgba(0, 209, 178, 0.15) !important;
        background: rgba(0, 0, 0, 0.3) !important;
    }

    .bg-primary-gradient {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
    }

    .btn-primary-gradient {
        background: linear-gradient(135deg, #00d1b2, #00b4d8);
        border: none;
        color: white;
        transition: all 0.3s transform ease;
    }

    .btn-primary-gradient:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 209, 178, 0.3);
        color: white;
    }

    .shadow-brand {
        box-shadow: 0 8px 15px rgba(0, 209, 178, 0.2);
    }

    .text-teal-accent { color: #00d1b2; }
    .bg-blur-teal { background: rgba(0, 209, 178, 0.1); }
    .border-teal-soft { border-color: rgba(0, 209, 178, 0.3) !important; }
    .border-white-5 { border-color: rgba(255, 255, 255, 0.05) !important; }
    .border-white-10 { border-color: rgba(255, 255, 255, 0.1) !important; }
    .bg-info-soft { background: rgba(0, 209, 178, 0.05); }
    .bg-dark-soft { background: rgba(0, 0, 0, 0.2); }
    
    .btn-outline-white {
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .btn-outline-white:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
</style>

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

    // Feedback sur le bouton de soumission
    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> TRAITEMENT...';
        btn.disabled = true;
    });
});
</script>
@endpush
@endsection
