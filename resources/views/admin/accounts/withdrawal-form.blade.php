@extends('layouts.app_admin')

@section('title', 'Retrait de Fonds')

@section('content')
<div class="max-w-4xl px-4 mx-auto sm:px-6 lg:px-8">
    <!-- En-tête -->
    <div class="mb-6">
        <div class="flex items-center mb-4">
            <a href="{{ route('admin.accounts.show', $account->id) }}"
               class="mr-4 text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Retrait de Fonds</h1>
                <p class="text-gray-600">Compte: {{ $account->account_number }}</p>
            </div>
        </div>
    </div>

    <!-- Informations du compte -->
    <div class="mb-6 overflow-hidden bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700">
            <h3 class="text-lg font-semibold text-white">Informations du Compte</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Client</label>
                    <p class="font-semibold text-gray-900">
                        {{ $account->client->first_name }} {{ $account->client->last_name }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $account->client->client_number }}</p>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Type de Compte</label>
                    @if($account->account_type === 'savings')
                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-cyan-100 text-cyan-800">
                            <i class="mr-1 fas fa-piggy-bank"></i> Épargne
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-purple-800 bg-purple-100 rounded-full">
                            <i class="mr-1 fas fa-users"></i> Tontine
                        </span>
                    @endif
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Solde Actuel du Compte</label>
                    <p class="text-2xl font-bold text-green-600">
                        {{ number_format($account->balance, 0, ',', ' ') }} FCFA
                    </p>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Montant Max. à Remettre au Client</label>
                    <p class="text-2xl font-bold text-blue-600">
                        {{ number_format($maxWithdrawal, 0, ',', ' ') }} FCFA
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        (Solde ÷ 1.01 pour couvrir les frais de 1%)
                    </p>
                    @if($minimumBalance > 0)
                        <p class="mt-1 text-xs text-orange-600">
                            <i class="fas fa-info-circle"></i> Solde minimum requis: {{ number_format($minimumBalance, 0, ',', ' ') }} FCFA
                        </p>
                    @endif
                </div>
            </div>

            <!-- Explication du calcul -->
            <div class="p-4 mt-4 border-l-4 border-blue-400 rounded bg-blue-50">
                <div class="flex">
                    <i class="mt-1 mr-3 text-blue-400 fas fa-info-circle"></i>
                    <div>
                        <p class="font-semibold text-blue-800">Comment fonctionne le retrait ?</p>
                        <ul class="mt-1 text-sm text-blue-700 list-disc list-inside">
                            <li>Vous saisissez le <strong>montant que le client va recevoir</strong></li>
                            <li>Les frais de retrait (1% par défaut) sont <strong>ajoutés</strong> à ce montant</li>
                            <li>Le compte est débité du <strong>total (montant + frais)</strong></li>
                        </ul>
                    </div>
                </div>
            </div>

            @if($account->account_type === 'tontine')
                <div class="p-4 mt-4 border-l-4 border-yellow-400 rounded bg-yellow-50">
                    <div class="flex">
                        <i class="mt-1 mr-3 text-yellow-400 fas fa-exclamation-triangle"></i>
                        <div>
                            <p class="font-semibold text-yellow-800">Attention - Compte Tontine</p>
                            <p class="mt-1 text-sm text-yellow-700">
                                Si le retrait vide complètement le compte, celui-ci sera automatiquement suspendu.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Formulaire de retrait -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Informations du Retrait</h3>
        </div>

        <form action="{{ route('admin.accounts.withdrawal.process', $account->id) }}" method="POST" id="withdrawalForm">
            @csrf
            <div class="p-6 space-y-6">
                <!-- Montant à remettre au client -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Montant à Remettre au Client <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               name="amount"
                               id="amount"
                               class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Montant que le client recevra"
                               min="100"
                               max="{{ $maxWithdrawal }}"
                               step="1"
                               value="{{ old('amount') }}"
                               required>
                        <span class="absolute font-medium text-gray-500 right-4 top-3">FCFA</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Min: 100 FCFA | Max: {{ number_format($maxWithdrawal, 0, ',', ' ') }} FCFA
                    </p>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Frais de retrait -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Frais de Retrait
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="withdrawal_fee"
                                   id="withdrawal_fee"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Par défaut: 1% du montant"
                                   min="0"
                                   step="50"
                                   value="{{ old('withdrawal_fee') }}">
                            <span class="absolute text-gray-500 right-4 top-2">FCFA</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Laissez vide pour appliquer 1% automatiquement</p>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Frais Calculés
                        </label>
                        <div class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg">
                            <span id="calculated_fee" class="text-lg font-semibold text-orange-600">0 FCFA</span>
                        </div>
                    </div>
                </div>

                <!-- Alerte solde insuffisant -->
                <div id="insufficient_balance_alert" class="hidden p-4 border-l-4 border-red-500 rounded bg-red-50">
                    <div class="flex">
                        <i class="mt-1 mr-3 text-red-500 fas fa-times-circle"></i>
                        <div>
                            <p class="font-semibold text-red-800">Solde Insuffisant !</p>
                            <p class="mt-1 text-sm text-red-700" id="insufficient_message"></p>
                        </div>
                    </div>
                </div>

                <!-- Méthode de paiement -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">
                        Méthode de Paiement <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer payment-method-label hover:border-blue-500">
                            <input type="radio" name="payment_method" value="cash" class="mr-3" required checked>
                            <div class="flex items-center">
                                <i class="mr-3 text-2xl text-green-600 fas fa-money-bill-wave"></i>
                                <span class="font-medium">Espèces</span>
                            </div>
                        </label>

                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer payment-method-label hover:border-blue-500">
                            <input type="radio" name="payment_method" value="bank_transfer" class="mr-3" required>
                            <div class="flex items-center">
                                <i class="mr-3 text-2xl text-blue-600 fas fa-university"></i>
                                <span class="font-medium">Virement</span>
                            </div>
                        </label>

                        <label class="relative flex items-center p-4 transition-colors border-2 border-gray-300 rounded-lg cursor-pointer payment-method-label hover:border-blue-500">
                            <input type="radio" name="payment_method" value="mobile_money" class="mr-3" required>
                            <div class="flex items-center">
                                <i class="mr-3 text-2xl text-purple-600 fas fa-mobile-alt"></i>
                                <span class="font-medium">Mobile Money</span>
                            </div>
                        </label>
                    </div>
                    @error('payment_method')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Informations du bénéficiaire -->
                <div class="pt-6 border-t">
                    <h4 class="mb-4 font-semibold text-gray-900">Informations du Bénéficiaire</h4>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">
                                Nom Complet <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="recipient_name"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Nom du bénéficiaire"
                                   value="{{ old('recipient_name', $account->client->first_name . ' ' . $account->client->last_name) }}"
                                   required>
                            @error('recipient_name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Téléphone</label>
                            <input type="text"
                                   name="recipient_phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Numéro de téléphone"
                                   value="{{ old('recipient_phone', $account->client->phone) }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Pièce d'Identité</label>
                            <input type="text"
                                   name="recipient_id"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Numéro de pièce d'identité"
                                   value="{{ old('recipient_id') }}">
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">Description / Motif (optionnel)</label>
                    <textarea name="description"
                              rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Motif du retrait...">{{ old('description') }}</textarea>
                </div>

                <!-- Récapitulatif -->
                <div class="pt-6 border-t">
                    <div class="p-5 border border-gray-200 rounded-lg bg-gradient-to-r from-gray-50 to-blue-50">
                        <h4 class="flex items-center mb-4 font-semibold text-gray-900">
                            <i class="mr-2 text-blue-600 fas fa-calculator"></i>
                            Récapitulatif du Retrait
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">💰 Montant remis au client:</span>
                                <span id="summary_amount" class="text-lg font-bold text-green-600">0 FCFA</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700">💳 Frais de retrait:</span>
                                <span id="summary_fee" class="font-semibold text-orange-600">+ 0 FCFA</span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-300">
                                <span class="font-semibold text-gray-900">📊 Total débité du compte:</span>
                                <span id="summary_total_debit" class="text-xl font-bold text-red-600">0 FCFA</span>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-gray-300 border-dashed">
                                <span class="text-gray-700">💼 Nouveau solde après retrait:</span>
                                <span id="summary_new_balance" class="text-lg font-bold">{{ number_format($account->balance, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200 bg-gray-50">
                <a href="{{ route('admin.accounts.show', $account->id) }}"
                   class="px-6 py-2 text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-100">
                    <i class="mr-2 fas fa-times"></i>Annuler
                </a>
                <button type="submit"
                        id="submitBtn"
                        class="px-6 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="mr-2 fas fa-check"></i>Confirmer le Retrait
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const feeInput = document.getElementById('withdrawal_fee');
    const submitBtn = document.getElementById('submitBtn');
    const insufficientAlert = document.getElementById('insufficient_balance_alert');
    const insufficientMessage = document.getElementById('insufficient_message');
    
    const currentBalance = {{ $account->balance }};
    const maxWithdrawal = {{ $maxWithdrawal }};
    const minimumBalance = {{ $minimumBalance }};

    function formatNumber(num) {
        return num.toLocaleString('fr-FR');
    }

    function calculateAmounts() {
        const amountToGive = parseFloat(amountInput.value) || 0;
        
        // Calculer les frais (personnalisés ou 1% par défaut)
        let fee;
        if (feeInput.value !== '' && feeInput.value !== null) {
            fee = parseFloat(feeInput.value) || 0;
        } else {
            fee = Math.round(amountToGive * 0.01);
        }

        // Total à débiter = montant + frais
        const totalDebit = amountToGive + fee;
        
        // Nouveau solde après retrait
        const newBalance = currentBalance - totalDebit;

        // Mise à jour des affichages
        document.getElementById('calculated_fee').textContent = formatNumber(fee) + ' FCFA';
        document.getElementById('summary_amount').textContent = formatNumber(amountToGive) + ' FCFA';
        document.getElementById('summary_fee').textContent = '+ ' + formatNumber(fee) + ' FCFA';
        document.getElementById('summary_total_debit').textContent = formatNumber(totalDebit) + ' FCFA';
        document.getElementById('summary_new_balance').textContent = formatNumber(newBalance) + ' FCFA';

        // Vérifier si le solde est suffisant
        const availableForWithdrawal = currentBalance - minimumBalance;
        let isValid = true;

        if (totalDebit > availableForWithdrawal) {
            insufficientAlert.classList.remove('hidden');
            insufficientMessage.innerHTML = 
                'Le solde disponible (' + formatNumber(availableForWithdrawal) + ' FCFA) ne couvre pas le montant total requis.<br>' +
                '<strong>Montant demandé:</strong> ' + formatNumber(amountToGive) + ' FCFA<br>' +
                '<strong>Frais de retrait:</strong> ' + formatNumber(fee) + ' FCFA<br>' +
                '<strong>Total nécessaire:</strong> ' + formatNumber(totalDebit) + ' FCFA';
            submitBtn.disabled = true;
            isValid = false;
        } else {
            insufficientAlert.classList.add('hidden');
            submitBtn.disabled = false;
        }

        // Style du nouveau solde
        const newBalanceEl = document.getElementById('summary_new_balance');
        newBalanceEl.classList.remove('text-red-600', 'text-orange-600', 'text-green-600');
        
        if (newBalance < 0) {
            newBalanceEl.classList.add('text-red-600');
        } else if (newBalance === 0) {
            newBalanceEl.classList.add('text-orange-600');
        } else {
            newBalanceEl.classList.add('text-green-600');
        }

        return isValid;
    }

    // Événements
    amountInput.addEventListener('input', calculateAmounts);
    feeInput.addEventListener('input', calculateAmounts);

    // Style des méthodes de paiement
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-label').forEach(label => {
                label.classList.remove('border-blue-500', 'bg-blue-50');
                label.classList.add('border-gray-300');
            });
            if (this.checked) {
                this.closest('label').classList.add('border-blue-500', 'bg-blue-50');
                this.closest('label').classList.remove('border-gray-300');
            }
        });
    });

    // Valider la sélection initiale
    const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
    if (checkedRadio) {
        checkedRadio.closest('label').classList.add('border-blue-500', 'bg-blue-50');
        checkedRadio.closest('label').classList.remove('border-gray-300');
    }

    // Validation avant soumission
    document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
        const amountToGive = parseFloat(amountInput.value) || 0;
        let fee = feeInput.value !== '' ? parseFloat(feeInput.value) : Math.round(amountToGive * 0.01);
        const totalDebit = amountToGive + fee;

        if (amountToGive < 100) {
            e.preventDefault();
            alert('Le montant minimum est de 100 FCFA');
            return false;
        }

        if (amountToGive > maxWithdrawal) {
            e.preventDefault();
            alert('Le montant maximum retirable est de ' + formatNumber(maxWithdrawal) + ' FCFA');
            return false;
        }

        if (totalDebit > (currentBalance - minimumBalance)) {
            e.preventDefault();
            alert('Solde insuffisant pour couvrir le montant et les frais de retrait.');
            return false;
        }

        @if($account->account_type === 'tontine')
        const newBalance = currentBalance - totalDebit;
        if (newBalance === 0) {
            if (!confirm('⚠️ ATTENTION: Ce retrait videra complètement le compte de tontine.\nLe compte sera automatiquement suspendu.\n\nVoulez-vous continuer ?')) {
                e.preventDefault();
                return false;
            }
        }
        @endif

        const confirmMsg = '📋 CONFIRMATION DU RETRAIT\n\n' +
            '💰 Montant à remettre: ' + formatNumber(amountToGive) + ' FCFA\n' +
            '💳 Frais de retrait: ' + formatNumber(fee) + ' FCFA\n' +
            '📊 Total débité: ' + formatNumber(totalDebit) + ' FCFA\n\n' +
            'Confirmer cette opération ?';

        return confirm(confirmMsg);
    });

    // Calcul initial
    calculateAmounts();
});
</script>
@endpush
@endsection