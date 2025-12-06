@extends('layouts.agent')

@section('title', 'Activer les Comptes')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Activation des Comptes</h2>
                    <p class="text-muted mb-0">{{ $client->first_name }} {{ $client->last_name }} - {{ $client->client_number }}</p>
                </div>
                <a href="{{ route('agent.clients.show', $client->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Informations client -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        @if($client->profile_photo_url)
                            <img src="{{ Storage::url($client->profile_photo_url) }}"
                                 class="rounded-circle me-3"
                                 style="width: 60px; height: 60px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3"
                                 style="width: 60px; height: 60px; font-size: 1.5rem;">
                                {{ substr($client->first_name, 0, 1) }}{{ substr($client->last_name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h5 class="mb-0">{{ $client->first_name }} {{ $client->last_name }}</h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-phone me-2"></i>{{ $client->phone }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire d'activation -->
            <form action="{{ route('agent.clients.activate-accounts', $client->id) }}" method="POST" id="activationForm">
                @csrf

                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-warning text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>Comptes en Attente d'Activation
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Sélectionnez les comptes à activer et renseignez les frais d'ouverture et le dépôt initial pour chaque compte.
                        </div>

                        @foreach($client->accounts as $index => $account)
                        <div class="card border mb-3 account-card">
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input account-checkbox" type="checkbox"
                                           name="account_selected[{{ $index }}]"
                                           id="account_{{ $index }}"
                                           value="1">
                                    <label class="form-check-label fw-bold" for="account_{{ $index }}">
                                        {{ $account->account_number }} -
                                        @if($account->account_type == 'savings')
                                            Compte d'Épargne
                                        @elseif($account->account_type == 'tontine')
                                            Compte Tontine
                                        @else
                                            {{ ucfirst($account->account_type) }}
                                        @endif
                                    </label>
                                </div>

                                <input type="hidden" name="accounts[{{ $index }}][account_id]" value="{{ $account->id }}">

                                <div class="account-details" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Frais d'Ouverture (FCFA) <span class="text-danger">*</span></label>
                                            <input type="number"
                                                   class="form-control opening-fee"
                                                   name="accounts[{{ $index }}][amount_paid]"
                                                   placeholder="Ex: 1000"
                                                   min="0"
                                                   step="100"
                                                   data-index="{{ $index }}">
                                            <small class="text-muted">Frais standard: 1,000 FCFA</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Dépôt Initial (FCFA)</label>
                                            <input type="number"
                                                   class="form-control initial-deposit"
                                                   name="accounts[{{ $index }}][initial_deposit]"
                                                   placeholder="Ex: 5000"
                                                   min="0"
                                                   step="100"
                                                   data-index="{{ $index }}">
                                            <small class="text-muted">Optionnel</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mode de Paiement <span class="text-danger">*</span></label>
                                            <select class="form-select payment-method"
                                                    name="accounts[{{ $index }}][payment_method]">
                                                <option value="">Sélectionner...</option>
                                                <option value="cash">Espèces</option>
                                                <option value="mobile_money">Mobile Money</option>
                                                <option value="bank_transfer">Virement Bancaire</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Référence de Paiement</label>
                                            <input type="text"
                                                   class="form-control"
                                                   name="accounts[{{ $index }}][payment_reference]"
                                                   placeholder="Numéro de transaction">
                                            <small class="text-muted">Si paiement mobile/bancaire</small>
                                        </div>

                                        <div class="col-12">
                                            <div class="alert alert-secondary">
                                                <strong>Total à encaisser:</strong>
                                                <span class="total-amount" data-index="{{ $index }}">0</span> FCFA
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        @if($client->accounts->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun compte en attente d'activation pour ce client.
                        </div>
                        @endif
                    </div>
                </div>

                @if($client->accounts->count() > 0)
                <!-- Résumé -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-calculator me-2"></i>Résumé de l'Encaissement
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <small class="text-muted d-block">Comptes Sélectionnés</small>
                                    <h3 class="mb-0" id="selectedCount">0</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <small class="text-muted d-block">Frais d'Ouverture</small>
                                    <h3 class="mb-0 text-primary" id="totalFees">0 FCFA</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <small class="text-muted d-block">Dépôts Initiaux</small>
                                    <h3 class="mb-0 text-success" id="totalDeposits">0 FCFA</h3>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h4 class="mb-0">
                                <strong>Total à Encaisser:</strong>
                                <span class="text-danger" id="grandTotal">0 FCFA</span>
                            </h4>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-success btn-lg w-100 mb-2" id="submitBtn">
                            <i class="fas fa-check-circle me-2"></i>Activer les Comptes Sélectionnés
                        </button>
                        <a href="{{ route('agent.clients.show', $client->id) }}" class="btn btn-secondary btn-lg w-100">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('activationForm');
    const submitBtn = document.getElementById('submitBtn');

    // Gestion des cases à cocher
    document.querySelectorAll('.account-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.account-card');
            const details = card.querySelector('.account-details');
            const inputs = details.querySelectorAll('input, select');

            if (this.checked) {
                details.style.display = 'block';
                inputs.forEach(input => {
                    if (input.classList.contains('opening-fee') || input.classList.contains('payment-method')) {
                        input.required = true;
                    }
                });
            } else {
                details.style.display = 'none';
                inputs.forEach(input => {
                    input.required = false;
                    if (input.type === 'number') {
                        input.value = '';
                    }
                });
            }

            updateSummary();
        });
    });

    // Calcul automatique des totaux
    document.querySelectorAll('.opening-fee, .initial-deposit').forEach(input => {
        input.addEventListener('input', function() {
            const index = this.dataset.index;
            updateAccountTotal(index);
            updateSummary();
        });
    });

    function updateAccountTotal(index) {
        const feeInput = document.querySelector(`.opening-fee[data-index="${index}"]`);
        const depositInput = document.querySelector(`.initial-deposit[data-index="${index}"]`);
        const totalSpan = document.querySelector(`.total-amount[data-index="${index}"]`);

        const fee = parseFloat(feeInput.value) || 0;
        const deposit = parseFloat(depositInput.value) || 0;
        const total = fee + deposit;

        totalSpan.textContent = total.toLocaleString('fr-FR');
    }

    function updateSummary() {
        let selectedCount = 0;
        let totalFees = 0;
        let totalDeposits = 0;

        document.querySelectorAll('.account-checkbox:checked').forEach(checkbox => {
            selectedCount++;
            const card = checkbox.closest('.account-card');
            const feeInput = card.querySelector('.opening-fee');
            const depositInput = card.querySelector('.initial-deposit');

            totalFees += parseFloat(feeInput.value) || 0;
            totalDeposits += parseFloat(depositInput.value) || 0;
        });

        const grandTotal = totalFees + totalDeposits;

        document.getElementById('selectedCount').textContent = selectedCount;
        document.getElementById('totalFees').textContent = totalFees.toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('totalDeposits').textContent = totalDeposits.toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('grandTotal').textContent = grandTotal.toLocaleString('fr-FR') + ' FCFA';

        // Désactiver le bouton si aucun compte sélectionné
        if (submitBtn) {
            submitBtn.disabled = selectedCount === 0;
        }
    }

    // Validation du formulaire
    if (form) {
        form.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.account-checkbox:checked');

            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un compte à activer.');
                return false;
            }

            // Valider les champs requis pour chaque compte sélectionné
            let valid = true;
            checkedBoxes.forEach(checkbox => {
                const card = checkbox.closest('.account-card');
                const feeInput = card.querySelector('.opening-fee');
                const methodSelect = card.querySelector('.payment-method');

                if (!feeInput.value || parseFloat(feeInput.value) <= 0) {
                    valid = false;
                    feeInput.classList.add('is-invalid');
                } else {
                    feeInput.classList.remove('is-invalid');
                }

                if (!methodSelect.value) {
                    valid = false;
                    methodSelect.classList.add('is-invalid');
                } else {
                    methodSelect.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires pour les comptes sélectionnés.');
                return false;
            }

            // Confirmation
            const grandTotal = document.getElementById('grandTotal').textContent;
            if (!confirm(`Confirmer l'encaissement de ${grandTotal} et l'activation de ${checkedBoxes.length} compte(s) ?`)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Initialisation
    updateSummary();
});
</script>
@endpush

@push('styles')
<style>
    .account-card {
        transition: all 0.3s ease;
    }

    .account-card:has(.account-checkbox:checked) {
        border-color: #28a745 !important;
        background-color: #f8fff9;
    }

    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>
@endpush
