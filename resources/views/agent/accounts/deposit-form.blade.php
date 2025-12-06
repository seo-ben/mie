@extends('layouts.agent')

@section('title', 'Collecte Tontine')

@section('content')
<div class="py-4 container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="border-0 shadow-sm card">
                <div class="text-white card-header bg-success">
                    <h4 class="mb-0 text-center">
                        <i class="fas fa-hand-holding-usd me-2"></i>
                        Nouvelle Collecte
                    </h4>
                </div>

                <div class="card-body">
                    <!-- Informations du compte -->
                    <div class="p-3 mb-4 rounded bg-light">
                        <div class="text-center row text-sm-start">
                            <div class="mb-2 col-sm-6">
                                <small class="text-muted d-block">Compte</small>
                                <strong>#{{ $account->account_number }}</strong>
                            </div>
                            <div class="mb-2 col-sm-6">
                                <small class="text-muted d-block">Client</small>
                                <strong>{{ $account->client->first_name }} {{ $account->client->last_name }}</strong>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Fréquence</small>
                                <strong class="badge bg-info">
                                    {{ ucfirst($tontine->payment_frequency) }}
                                </strong>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Cotisation</small>
                                <strong>{{ number_format($tontine->expected_monthly_payment ?? $tontine->tontine_amount, 0, ',', ' ') }} FCFA</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de dépôt -->
                    <form action="{{ route('agent.accounts.process-deposit', $account->id) }}" method="POST">
                        @csrf

                        <!-- Montant reçu -->
                        <div class="mb-3">
                            <label for="amount" class="form-label fw-bold">
                                <i class="fas fa-coins text-primary me-1"></i> Montant reçu du client (FCFA)
                            </label>
                            <input type="number"
                                   name="amount"
                                   id="amount"
                                   class="form-control form-control-lg text-center @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}"
                                   min="100"
                                   step="100"
                                   placeholder="Ex: 3 000"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <strong>Cotisation quotidienne :</strong>
                                <span class="text-info fw-bold">
                                    {{ number_format($tontine->expected_monthly_payment ?? $tontine->tontine_amount, 0, ',', ' ') }} FCFA
                                </span>
                                <br>
                                <small class="text-success">
                                    Paiement d’avance illimité : 3000 FCFA → 15 jours couverts
                                </small>
                            </div>
                        </div>

                        <!-- Méthode de paiement -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-credit-card text-info me-1"></i> Méthode de paiement
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash"
                                               {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="cash">
                                            <i class="fas fa-money-bill-wave text-success"></i> Espèces
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="mobile_money" value="mobile_money"
                                               {{ old('payment_method') === 'mobile_money' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="mobile_money">
                                            <i class="fas fa-mobile-alt text-primary"></i> Mobile Money
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @error('payment_method')
                                <div class="mt-1 text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Opérateur Mobile Money -->
                        <div id="mobileMoneyFields" style="display: none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="mobile_money_operator" class="form-label">Opérateur</label>
                                    <select name="mobile_money_operator" id="mobile_money_operator"
                                            class="form-select @error('mobile_money_operator') is-invalid @enderror">
                                        <option value="">-- Choisir --</option>
                                        <option value="tmoney" {{ old('mobile_money_operator') === 'tmoney' ? 'selected' : '' }}>T-Money</option>
                                        <option value="flooz" {{ old('mobile_money_operator') === 'flooz' ? 'selected' : '' }}>Flooz</option>
                                    </select>
                                    @error('mobile_money_operator')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_reference" class="form-label">Référence</label>
                                    <input type="text"
                                           name="payment_reference"
                                           id="payment_reference"
                                           class="form-control @error('payment_reference') is-invalid @enderror"
                                           value="{{ old('payment_reference') }}"
                                           placeholder="Ex: 123456789">
                                    @error('payment_reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Description (optionnel)</label>
                            <textarea name="description"
                                      id="description"
                                      rows="2"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Ex: Paiement d’avance pour 15 jours">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('agent.accounts.show', $account->id) }}"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const mobileFields = document.getElementById('mobileMoneyFields');
            if (this.value === 'mobile_money') {
                mobileFields.style.display = 'block';
                document.getElementById('mobile_money_operator').setAttribute('required', 'required');
            } else {
                mobileFields.style.display = 'none';
                document.getElementById('mobile_money_operator').removeAttribute('required');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const checked = document.querySelector('input[name="payment_method"]:checked');
        if (checked && checked.value === 'mobile_money') {
            document.getElementById('mobileMoneyFields').style.display = 'block';
        }
    });
</script>
@endpush
