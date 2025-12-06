@extends('layouts.app_admin')

@section('title', $account->account_type === 'tontine' ? 'Nouvelle Cotisation' : 'Nouveau Dépôt')

@section('content')
<div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="mb-2 text-2xl font-semibold text-gray-900">
                @if($account->account_type === 'tontine')
                    Nouvelle Cotisation Tontine
                @else
                    Nouveau Dépôt Épargne
                @endif
            </h1>
            <p class="text-gray-600">
                @if($account->account_type === 'tontine')
                    Enregistrer une cotisation pour le cycle actif
                @else
                    Effectuer un dépôt sur le compte d'épargne
                @endif
            </p>
        </div>
        <a href="{{ route('admin.accounts.show', $account->id) }}"
           class="px-4 py-2 text-white transition-colors bg-gray-600 rounded-lg hover:bg-gray-700">
            <i class="mr-2 fas fa-arrow-left"></i>Retour
        </a>
    </div>

    <!-- Informations du compte -->
    @if($account->account_type === 'tontine' && $activeCycle)
        <!-- Carte Cycle Tontine CORRIGÉE -->
        <div class="p-6 mb-6 border-l-4 border-purple-500 rounded-lg shadow-sm bg-gradient-to-r from-purple-50 to-pink-50">
            <div class="flex items-start justify-between mb-4">
                <h5 class="text-lg font-semibold text-gray-900">
                    <i class="mr-2 text-purple-600 fas fa-users"></i>
                    Cycle #{{ $activeCycle->cycle_number }}
                </h5>
                <span class="px-3 py-1 text-xs font-semibold text-white bg-purple-600 rounded-full">
                    {{ strtoupper($account->tontineAccount->payment_frequency) }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-6 mb-4 md:grid-cols-2">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Client</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $account->client->first_name }} {{ $account->client->last_name }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $account->account_number }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-gray-600">Période du Cycle</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $activeCycle->start_date->format('d/m/Y') }} → {{ $activeCycle->end_date->format('d/m/Y') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        @php
                            $daysRemaining = now()->diffInDays($activeCycle->end_date, false);
                        @endphp
                        @if($daysRemaining > 0)
                            <i class="mr-1 fas fa-clock"></i>{{ $daysRemaining }} jour(s) restant(s)
                        @else
                            <i class="mr-1 text-orange-600 fas fa-exclamation-triangle"></i>Cycle terminé
                        @endif
                    </p>
                </div>
            </div>

            <!-- AFFICHAGE CORRIGÉ : Montant à payer CE cycle -->
            <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                <div class="p-4 bg-white border-2 border-purple-200 rounded-lg">
                    <p class="mb-1 text-sm text-gray-600">À Payer Ce Cycle</p>
                    <p class="text-xl font-bold text-purple-900">
                        {{ number_format($activeCycle->target_amount, 0, ',', ' ') }} FCFA
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Objectif du cycle</p>
                </div>
                <div class="p-4 bg-white rounded-lg">
                    <p class="mb-1 text-sm text-gray-600">Déjà Payé</p>
                    <p class="text-xl font-bold text-green-600">
                        {{ number_format($activeCycle->collected_amount, 0, ',', ' ') }} FCFA
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Pour ce cycle</p>
                </div>
                <div class="p-4 bg-white rounded-lg">
                    <p class="mb-1 text-sm text-gray-600">Reste à Payer</p>
                    <p class="text-xl font-bold text-orange-600">
                        {{ number_format($remainingAmount, 0, ',', ' ') }} FCFA
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Pour compléter ce cycle</p>
                </div>
            </div>



            <!-- Info Tontine Globale -->
            <div class="p-4 bg-purple-100 rounded-lg">
                <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                    <div>
                        <p class="mb-1 text-gray-600">Fréquence</p>
                        <p class="font-semibold text-gray-900">
                            @switch($account->tontineAccount->payment_frequency)
                                @case('daily') Quotidien @break
                                @case('weekly') Hebdomadaire @break
                                @case('monthly') Mensuel @break
                            @endswitch
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-600">Durée totale</p>
                        <p class="font-semibold text-gray-900">
                            {{ $account->tontineAccount->cycle_duration_months }} mois
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-600">Total payé</p>
                        <p class="font-semibold text-green-600">
                            {{ number_format($account->tontineAccount->total_paid, 0, ',', ' ') }} FCFA
                        </p>
                    </div>
                    <div>
                        <p class="mb-1 text-gray-600">Total attendu</p>
                        <p class="font-semibold text-purple-900">
                            {{ number_format($account->tontineAccount->total_expected, 0, ',', ' ') }} FCFA
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Carte Compte Épargne (inchangée) -->
        <div class="p-6 mb-6 border-l-4 rounded-lg shadow-sm bg-gradient-to-r from-cyan-50 to-blue-50 border-cyan-500">
            <h5 class="mb-4 text-lg font-semibold text-gray-900">
                <i class="mr-2 fas fa-piggy-bank text-cyan-600"></i>
                Compte d'Épargne
            </h5>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <p class="mb-1 text-sm text-gray-600">Client</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $account->client->first_name }} {{ $account->client->last_name }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $account->client->client_number }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-gray-600">Numéro de Compte</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $account->account_number }}</p>
                </div>
                <div>
                    <p class="mb-1 text-sm text-gray-600">Solde Actuel</p>
                    <p class="text-2xl font-bold text-cyan-600">
                        {{ number_format($account->balance, 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>

            @if($account->savingsAccount)
                <div class="pt-4 mt-4 border-t border-cyan-200">
                    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                        <div>
                            <span class="text-gray-600">Taux d'intérêt:</span>
                            <span class="ml-2 font-semibold text-gray-900">
                                {{ $account->savingsAccount->interest_rate }}%
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">Solde minimum:</span>
                            <span class="ml-2 font-semibold text-gray-900">
                                {{ number_format($account->savingsAccount->minimum_balance, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600">Frais mensuels:</span>
                            <span class="ml-2 font-semibold text-gray-900">
                                {{ number_format($account->savingsAccount->monthly_fee, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Formulaire de dépôt (reste identique) -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-900">
                Informations du {{ $account->account_type === 'tontine' ? 'Paiement' : 'Dépôt' }}
            </h5>
        </div>

        <form action="{{ route('admin.accounts.deposit.process', $account->id) }}"
              method="POST"
              id="depositForm">
            @csrf

            <div class="p-6 space-y-6">
                <!-- Montant -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Montant {{ $account->account_type === 'tontine' ? 'du Paiement' : 'du Dépôt' }}
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               name="amount"
                               id="amount"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500 focus:border-transparent @error('amount') border-red-500 @enderror"
                               placeholder="Entrez le montant en FCFA"
                               value="{{ old('amount', $suggestedAmount) }}"
                               min="100"
                               @if($account->account_type === 'tontine' && $activeCycle)
                                   max=""
                               @endif
                               step="100"
                               required>
                        <span class="absolute font-medium text-gray-500 right-4 top-3">FCFA</span>
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if($account->account_type === 'tontine' && $activeCycle)
                        <div class="p-3 mt-2 border border-purple-200 rounded-lg bg-purple-50">
                            <p class="text-sm text-purple-900">
                                <i class="mr-1 fas fa-info-circle"></i>
                                <strong>Montant suggéré:</strong>
                                <span class="font-bold text-purple-600">{{ number_format($suggestedAmount, 0, ',', ' ') }} FCFA</span>
                            </p>

                        </div>
                    @else
                        <p class="mt-2 text-sm text-gray-600">
                            <i class="mr-1 fas fa-info-circle text-cyan-600"></i>
                            Montant minimum: <strong class="text-cyan-600">100 FCFA</strong>
                        </p>
                    @endif
                </div>

                <!-- Méthode de paiement -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Méthode de Paiement <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 payment-method-label">
                            <input type="radio"
                                   name="payment_method"
                                   value="cash"
                                   class="sr-only payment-method-radio"
                                   {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}
                                   required>
                            <div class="flex items-center w-full">
                                <i class="mr-3 text-2xl text-green-600 fas fa-money-bill-wave"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Espèces</p>
                                    <p class="text-sm text-gray-500">Paiement cash</p>
                                </div>
                            </div>
                            <i class="hidden ml-auto text-2xl text-green-600 fas fa-check-circle check-icon"></i>
                        </label>

                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 payment-method-label">
                            <input type="radio"
                                   name="payment_method"
                                   value="mobile_money"
                                   class="sr-only payment-method-radio"
                                   {{ old('payment_method') === 'mobile_money' ? 'checked' : '' }}
                                   required>
                            <div class="flex items-center w-full">
                                <i class="mr-3 text-2xl text-blue-600 fas fa-mobile-alt"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Mobile Money</p>
                                    <p class="text-sm text-gray-500">TMoney / Flooz</p>
                                </div>
                            </div>
                            <i class="hidden ml-auto text-2xl text-blue-600 fas fa-check-circle check-icon"></i>
                        </label>

                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 payment-method-label">
                            <input type="radio"
                                   name="payment_method"
                                   value="bank_transfer"
                                   class="sr-only payment-method-radio"
                                   {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}
                                   required>
                            <div class="flex items-center w-full">
                                <i class="mr-3 text-2xl text-purple-600 fas fa-university"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Virement</p>
                                    <p class="text-sm text-gray-500">Virement bancaire</p>
                                </div>
                            </div>
                            <i class="hidden ml-auto text-2xl text-purple-600 fas fa-check-circle check-icon"></i>
                        </label>
                    </div>
                    @error('payment_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Opérateur Mobile Money (conditionnel) -->
                <div id="mobile-money-operator-field" class="hidden">
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Opérateur Mobile Money <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 operator-label">
                            <input type="radio"
                                   name="mobile_money_operator"
                                   value="tmoney"
                                   class="sr-only operator-radio"
                                   {{ old('mobile_money_operator') === 'tmoney' ? 'checked' : '' }}>
                            <div class="flex items-center w-full">
                                <div class="flex items-center justify-center w-12 h-12 mr-3 bg-red-100 rounded-lg">
                                    <span class="text-lg font-bold text-red-600">T</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">TMoney</p>
                                    <p class="text-sm text-gray-500">Togocom</p>
                                </div>
                            </div>
                            <i class="hidden ml-auto text-2xl text-red-600 fas fa-check-circle operator-check-icon"></i>
                        </label>

                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 operator-label">
                            <input type="radio"
                                   name="mobile_money_operator"
                                   value="flooz"
                                   class="sr-only operator-radio"
                                   {{ old('mobile_money_operator') === 'flooz' ? 'checked' : '' }}>
                            <div class="flex items-center w-full">
                                <div class="flex items-center justify-center w-12 h-12 mr-3 bg-orange-100 rounded-lg">
                                    <span class="text-lg font-bold text-orange-600">F</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Flooz</p>
                                    <p class="text-sm text-gray-500">Moov Africa</p>
                                </div>
                            </div>
                            <i class="hidden ml-auto text-2xl text-orange-600 fas fa-check-circle operator-check-icon"></i>
                        </label>
                    </div>
                    @error('mobile_money_operator')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Référence de paiement -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Référence de Paiement
                    </label>
                    <input type="text"
                           name="payment_reference"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500 focus:border-transparent @error('payment_reference') border-red-500 @enderror"
                           placeholder="Numéro de transaction (optionnel)"
                           value="{{ old('payment_reference') }}">
                    @error('payment_reference')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        <i class="mr-1 fas fa-info-circle"></i>
                        Numéro de référence de la transaction mobile money ou virement
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Description / Notes
                    </label>
                    <textarea name="description"
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500 focus:border-transparent @error('description') border-red-500 @enderror"
                              placeholder="Notes ou informations complémentaires (optionnel)">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Récapitulatif CORRIGÉ -->
                <div class="bg-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-50 border border-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-200 rounded-lg p-6">
                    <h6 class="mb-3 font-semibold text-gray-900">
                        <i class="mr-2 fas fa-clipboard-check"></i>Récapitulatif
                    </h6>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Client:</span>
                            <span class="font-medium text-gray-900">
                                {{ $account->client->first_name }} {{ $account->client->last_name }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Type de compte:</span>
                            <span class="font-medium text-gray-900">
                                @if($account->account_type === 'tontine')
                                    <i class="mr-1 text-purple-600 fas fa-users"></i>Tontine
                                @else
                                    <i class="mr-1 fas fa-piggy-bank text-cyan-600"></i>Épargne
                                @endif
                            </span>
                        </div>
                        @if($account->account_type === 'tontine' && $activeCycle)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Cycle actuel:</span>
                                <span class="font-medium text-gray-900">#{{ $activeCycle->cycle_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">À payer ce cycle:</span>
                                <span class="font-medium text-purple-900">
                                    {{ number_format($activeCycle->target_amount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-600">Solde compte avant:</span>
                            <span class="font-medium text-gray-900" id="recap-balance-before">
                                {{ number_format($account->balance, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Montant {{ $account->account_type === 'tontine' ? 'paiement' : 'dépôt' }}:</span>
                            <span class="font-bold text-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-600" id="recap-amount">
                                {{ number_format($suggestedAmount, 0, ',', ' ') }} FCFA
                            </span>
                        </div>
                        <div class="border-t border-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-300 pt-2 mt-2">
                            <div class="flex justify-between">
                                <span class="font-semibold text-gray-900">Nouveau solde compte:</span>
                                <span class="text-lg font-bold text-green-600" id="recap-new-balance">
                                    {{ number_format($account->balance + $suggestedAmount, 0, ',', ' ') }} FCFA
                                </span>
                            </div>
                        </div>
                        @if($account->account_type === 'tontine' && $activeCycle)
                            <div class="pt-2 mt-2 border-t border-purple-300">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Payé ce cycle:</span>
                                    <span class="font-semibold text-green-600" id="recap-cycle-collected">
                                        {{ number_format($activeCycle->collected_amount + $suggestedAmount, 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Reste à payer ce cycle:</span>
                                    <span class="font-semibold text-orange-600" id="recap-cycle-remaining">
                                        {{ number_format(max(0, $remainingAmount - $suggestedAmount), 0, ',', ' ') }} FCFA
                                    </span>
                                </div>
                                @if(($activeCycle->collected_amount + $suggestedAmount) >= $activeCycle->target_amount)
                                    <div class="p-3 mt-2 bg-green-100 border border-green-300 rounded-lg">
                                        <p class="text-sm font-semibold text-green-800">
                                            <i class="mr-1 fas fa-check-circle"></i>
                                            Ce paiement complètera le cycle #{{ $activeCycle->cycle_number }} !
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex px-6 py-4 space-x-3 border-t border-gray-200 bg-gray-50">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-600 text-white rounded-lg hover:bg-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-700 transition-colors font-semibold">
                    <i class="mr-2 fas fa-check"></i>
                    Enregistrer {{ $account->account_type === 'tontine' ? 'le Paiement' : 'le Dépôt' }}
                </button>
                <a href="{{ route('admin.accounts.show', $account->id) }}"
                   class="px-6 py-3 font-semibold text-gray-700 transition-colors bg-gray-300 rounded-lg hover:bg-gray-400">
                    <i class="mr-2 fas fa-times"></i>Annuler
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const accountType = '{{ $account->account_type }}';
    const currentBalance = {{ $account->balance }};
    @if($account->account_type === 'tontine' && $activeCycle)
        const cycleCollected = {{ $activeCycle->collected_amount }};
        const cycleTarget = {{ $activeCycle->target_amount }};
        const remainingAmount = {{ $remainingAmount }};
        const suggestedAmount = {{ $suggestedAmount }};
    @else
        const suggestedAmount = {{ $suggestedAmount }};
    @endif

    // === 1. Gestion visuelle des méthodes de paiement ===
    document.querySelectorAll('.payment-method-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-label').forEach(label => {
                label.classList.remove('border-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500', 'bg-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-50');
                label.classList.add('border-gray-300');
                label.querySelector('.check-icon').classList.add('hidden');
            });

            const selectedLabel = this.closest('.payment-method-label');
            selectedLabel.classList.remove('border-gray-300');
            selectedLabel.classList.add('border-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-500', 'bg-{{ $account->account_type === 'tontine' ? 'purple' : 'cyan' }}-50');
            selectedLabel.querySelector('.check-icon').classList.remove('hidden');

            // Afficher/masquer opérateur Mobile Money
            const mobileMoneyField = document.getElementById('mobile-money-operator-field');
            if (this.value === 'mobile_money') {
                mobileMoneyField.classList.remove('hidden');
                mobileMoneyField.classList.add('block');
            } else {
                mobileMoneyField.classList.add('hidden');
                mobileMoneyField.classList.remove('block');
            }
        });
    });

    // === 2. Gestion visuelle des opérateurs Mobile Money ===
    document.querySelectorAll('.operator-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.operator-label').forEach(label => {
                label.classList.remove('border-orange-500', 'bg-orange-50');
                label.classList.add('border-gray-300');
                label.querySelector('.operator-check-icon').classList.add('hidden');
            });

            const selectedLabel = this.closest('.operator-label');
            const isTmoney = this.value === 'tmoney';
            selectedLabel.classList.remove('border-gray-300');
            selectedLabel.classList.add(isTmoney ? 'border-red-500' : 'border-orange-500', isTmoney ? 'bg-red-50' : 'bg-orange-50');
            selectedLabel.querySelector('.operator-check-icon').classList.remove('hidden');
        });
    });

    // === 3. Mise à jour en temps réel du récapitulatif ===
    const amountInput = document.getElementById('amount');
    const recapAmount = document.getElementById('recap-amount');
    const recapNewBalance = document.getElementById('recap-new-balance');

    @if($account->account_type === 'tontine' && $activeCycle)
        const recapCycleCollected = document.getElementById('recap-cycle-collected');
        const recapCycleRemaining = document.getElementById('recap-cycle-remaining');
        const completionMessage = document.querySelector('.bg-green-100');
    @endif

    function updateRecap() {
        const amount = parseFloat(amountInput.value) || 0;

        // Mise à jour du montant
        recapAmount.textContent = formatNumber(amount) + ' FCFA';

        // Mise à jour du nouveau solde
        const newBalance = currentBalance + amount;
        recapNewBalance.textContent = formatNumber(newBalance) + ' FCFA';

        @if($account->account_type === 'tontine' && $activeCycle)
            // Mise à jour cycle
            const newCollected = cycleCollected + amount;
            const newRemaining = Math.max(0, cycleTarget - newCollected);

            recapCycleCollected.textContent = formatNumber(newCollected) + ' FCFA';
            recapCycleRemaining.textContent = formatNumber(newRemaining) + ' FCFA';

            // Afficher message de complétion
            if (completionMessage) {
                if (newCollected >= cycleTarget) {
                    completionMessage.classList.remove('hidden');
                } else {
                    completionMessage.classList.add('hidden');
                }
            }

            // Désactiver le bouton si montant > restant
            const submitBtn = document.querySelector('button[type="submit"]');
            if (amount > remainingAmount) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        @endif
    }

    // Fonction utilitaire de formatage
    function formatNumber(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    // Écouteur sur le champ montant
    amountInput.addEventListener('input', updateRecap);

    // Initialisation au chargement
    document.addEventListener('DOMContentLoaded', function() {
        // Simuler le changement de méthode de paiement au chargement (pour old())
        const checkedRadio = document.querySelector('.payment-method-radio:checked');
        if (checkedRadio) {
            checkedRadio.dispatchEvent(new Event('change'));
        }

        // Simuler opérateur Mobile Money si déjà sélectionné
        const checkedOperator = document.querySelector('.operator-radio:checked');
        if (checkedOperator) {
            checkedOperator.dispatchEvent(new Event('change'));
        }

        // Mettre à jour le récapitulatif
        updateRecap();
    });

    // === 4. Validation côté client (bonus) ===
    document.getElementById('depositForm').addEventListener('submit', function(e) {
        const amount = parseFloat(amountInput.value) || 0;
        const paymentMethod = document.querySelector('.payment-method-radio:checked')?.value;

        // if (accountType === 'tontine' && amount > remainingAmount) {
        //     e.preventDefault();
        //     alert('Le montant ne peut pas dépasser le reste à payer pour ce cycle (' + formatNumber(remainingAmount) + ' FCFA).');
        //     return;
        // }

        if (paymentMethod === 'mobile_money') {
            const operator = document.querySelector('.operator-radio:checked');
            if (!operator) {
                e.preventDefault();
                alert('Veuillez sélectionner un opérateur Mobile Money.');
                return;
            }
        }
    });
</script>
@endpush
@endsection
