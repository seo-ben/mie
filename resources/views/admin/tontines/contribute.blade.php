@extends('layouts.app_admin')

@section('title', 'Nouvelle Cotisation')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-2">Nouvelle Cotisation</h1>
            <p class="text-gray-600">Enregistrer une cotisation pour le cycle actif</p>
        </div>
        <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Retour
        </a>
    </div>

    <!-- Informations du cycle actif -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-l-4 border-purple-500 rounded-lg shadow-sm p-6 mb-6">
        <h5 class="text-lg font-semibold text-gray-900 mb-4">Cycle Actif #{{ $activeCycle->cycle_number }}</h5>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <p class="text-sm text-gray-600 mb-1">Client</p>
                <p class="text-lg font-semibold text-gray-900">
                    {{ $tontine->account->client->first_name }} {{ $tontine->account->client->last_name }}
                </p>
                <p class="text-sm text-gray-500">{{ $tontine->account->account_number }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Période du Cycle</p>
                <p class="text-lg font-semibold text-gray-900">
                    {{ $activeCycle->start_date->format('d/m/Y') }} → {{ $activeCycle->end_date->format('d/m/Y') }}
                </p>
            </div>
        </div>

        @php
            $remainingAmount = $activeCycle->target_amount - $activeCycle->collected_amount;
            $progressPercent = $activeCycle->target_amount > 0
                ? round(($activeCycle->collected_amount / $activeCycle->target_amount) * 100, 2)
                : 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Objectif</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($activeCycle->target_amount, 0, ',', ' ') }} FCFA</p>
            </div>
            <div class="bg-white rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Déjà Collecté</p>
                <p class="text-xl font-bold text-purple-600">{{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} FCFA</p>
            </div>
            <div class="bg-white rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Restant</p>
                <p class="text-xl font-bold text-orange-600">{{ number_format($remainingAmount, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>

        <div>
            <div class="flex justify-between text-sm mb-2">
                <span class="font-medium text-gray-700">Progression</span>
                <span class="font-bold text-purple-600">{{ $progressPercent }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 h-3 rounded-full" style="width: {{ min($progressPercent, 100) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Formulaire de cotisation -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">Informations de Cotisation</h5>
        </div>

        <form action="{{ route('admin.tontines.contribute', $tontine->id) }}" method="POST" class="p-6">
            @csrf

            <!-- Montant -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Montant de la Cotisation <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number"
                           name="amount"
                           id="amount"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('amount') border-red-500 @enderror"
                           placeholder="Montant en FCFA"
                           value="{{ old('amount', $suggestedAmount) }}"
                           min="100"
                           max="{{ $remainingAmount }}"
                           required>
                    <span class="absolute right-4 top-3 text-gray-500 font-medium">FCFA</span>
                </div>
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Montant suggéré: <strong class="text-purple-600">{{ number_format($suggestedAmount, 0, ',', ' ') }} FCFA</strong>
                    (Maximum: {{ number_format($remainingAmount, 0, ',', ' ') }} FCFA)
                </p>
            </div>

            <!-- Méthode de paiement -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Méthode de Paiement <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors @error('payment_method') border-red-500 @else border-gray-300 @enderror">
                        <input type="radio"
                               name="payment_method"
                               value="cash"
                               class="sr-only payment-method-radio"
                               {{ old('payment_method') === 'cash' ? 'checked' : '' }}
                               required>
                        <div class="flex items-center w-full">
                            <i class="fas fa-money-bill-wave text-2xl text-green-600 mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-900">Espèces</p>
                                <p class="text-sm text-gray-500">Paiement cash</p>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-2xl text-green-600 ml-auto hidden check-icon"></i>
                    </label>

                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors @error('payment_method') border-red-500 @else border-gray-300 @enderror">
                        <input type="radio"
                               name="payment_method"
                               value="mobile_money"
                               class="sr-only payment-method-radio"
                               {{ old('payment_method') === 'mobile_money' ? 'checked' : '' }}
                               required>
                        <div class="flex items-center w-full">
                            <i class="fas fa-mobile-alt text-2xl text-blue-600 mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-900">Mobile Money</p>
                                <p class="text-sm text-gray-500">TMoney / Flooz</p>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-2xl text-blue-600 ml-auto hidden check-icon"></i>
                    </label>

                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors @error('payment_method') border-red-500 @else border-gray-300 @enderror">
                        <input type="radio"
                               name="payment_method"
                               value="bank_transfer"
                               class="sr-only payment-method-radio"
                               {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}
                               required>
                        <div class="flex items-center w-full">
                            <i class="fas fa-university text-2xl text-purple-600 mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-900">Virement</p>
                                <p class="text-sm text-gray-500">Virement bancaire</p>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-2xl text-purple-600 ml-auto hidden check-icon"></i>
                    </label>
                </div>
                @error('payment_method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Opérateur Mobile Money (conditionnel) -->
            <div id="mobile-money-operator-field" class="mb-6 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Opérateur Mobile Money <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors border-gray-300">
                        <input type="radio"
                               name="mobile_money_operator"
                               value="tmoney"
                               class="sr-only operator-radio"
                               {{ old('mobile_money_operator') === 'tmoney' ? 'checked' : '' }}>
                        <div class="flex items-center w-full">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-red-600 font-bold text-lg">T</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">TMoney</p>
                                <p class="text-sm text-gray-500">Togocom</p>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-2xl text-red-600 ml-auto hidden operator-check-icon"></i>
                    </label>

                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors border-gray-300">
                        <input type="radio"
                               name="mobile_money_operator"
                               value="flooz"
                               class="sr-only operator-radio"
                               {{ old('mobile_money_operator') === 'flooz' ? 'checked' : '' }}>
                        <div class="flex items-center w-full">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-orange-600 font-bold text-lg">F</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Flooz</p>
                                <p class="text-sm text-gray-500">Moov Africa</p>
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-2xl text-orange-600 ml-auto hidden operator-check-icon"></i>
                    </label>
                </div>
                @error('mobile_money_operator')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Référence de paiement -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Référence de Paiement
                </label>
                <input type="text"
                       name="payment_reference"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('payment_reference') border-red-500 @enderror"
                       placeholder="Numéro de transaction (optionnel)"
                       value="{{ old('payment_reference') }}">
                @error('payment_reference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Numéro de référence de la transaction mobile money ou virement
                </p>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Description / Notes
                </label>
                <textarea name="description"
                          rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="Notes ou informations complémentaires (optionnel)">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Récapitulatif -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-6">
                <h6 class="font-semibold text-gray-900 mb-3">Récapitulatif</h6>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Client:</span>
                        <span class="font-medium text-gray-900">{{ $tontine->account->client->first_name }} {{ $tontine->account->client->last_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Cycle:</span>
                        <span class="font-medium text-gray-900">#{{ $activeCycle->cycle_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Solde avant:</span>
                        <span class="font-medium text-gray-900">{{ number_format($tontine->account->balance, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Montant cotisation:</span>
                        <span class="font-bold text-purple-600" id="recap-amount">{{ number_format($suggestedAmount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="border-t border-purple-300 pt-2 mt-2">
                        <div class="flex justify-between">
                            <span class="font-semibold text-gray-900">Nouveau solde:</span>
                            <span class="font-bold text-green-600" id="recap-new-balance">{{ number_format($tontine->account->balance + $suggestedAmount, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex space-x-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-semibold">
                    <i class="fas fa-check mr-2"></i>Enregistrer la Cotisation
                </button>
                <a href="{{ route('admin.tontines.show', $tontine->id) }}" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold">
                    <i class="fas fa-times mr-2"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Gestion de la sélection de méthode de paiement
    document.querySelectorAll('.payment-method-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            // Réinitialiser tous les styles
            document.querySelectorAll('.payment-method-radio').forEach(r => {
                const label = r.closest('label');
                label.classList.remove('border-purple-500', 'bg-purple-50');
                label.classList.add('border-gray-300');
                label.querySelector('.check-icon').classList.add('hidden');
            });

            // Appliquer le style au sélectionné
            if (this.checked) {
                const label = this.closest('label');
                label.classList.remove('border-gray-300');
                label.classList.add('border-purple-500', 'bg-purple-50');
                label.querySelector('.check-icon').classList.remove('hidden');
            }

            // Afficher/masquer le champ opérateur
            const operatorField = document.getElementById('mobile-money-operator-field');
            if (this.value === 'mobile_money') {
                operatorField.classList.remove('hidden');
            } else {
                operatorField.classList.add('hidden');
            }
        });
    });

    // Gestion de la sélection d'opérateur
    document.querySelectorAll('.operator-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.operator-radio').forEach(r => {
                const label = r.closest('label');
                label.classList.remove('border-purple-500', 'bg-purple-50');
                label.classList.add('border-gray-300');
                label.querySelector('.operator-check-icon').classList.add('hidden');
            });

            if (this.checked) {
                const label = this.closest('label');
                label.classList.remove('border-gray-300');
                label.classList.add('border-purple-500', 'bg-purple-50');
                label.querySelector('.operator-check-icon').classList.remove('hidden');
            }
        });
    });

    // Mise à jour du récapitulatif en temps réel
    const amountInput = document.getElementById('amount');
    const currentBalance = {{ $tontine->account->balance }};

    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const newBalance = currentBalance + amount;

        document.getElementById('recap-amount').textContent = amount.toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('recap-new-balance').textContent = newBalance.toLocaleString('fr-FR') + ' FCFA';
    });

    // Initialiser l'état au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const checkedPaymentMethod = document.querySelector('.payment-method-radio:checked');
        if (checkedPaymentMethod) {
            checkedPaymentMethod.dispatchEvent(new Event('change'));
        }

        const checkedOperator = document.querySelector('.operator-radio:checked');
        if (checkedOperator) {
            checkedOperator.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
@endsection
